<?php
namespace BusinessDashboard\Services;

use Core\Database;

class BusinessDashboardService {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getPdo();
    }

    /**
     * Vérifie si l'utilisateur a l'addon "Espace Pro" actif.
     */
    public function isAddonActive(int $userId): bool {
        // 1. Trouver l'ID de l'addon "Espace Pro" ou "Business Dashboard"
        $stmt = $this->pdo->prepare("SELECT id FROM subscription_addons WHERE name LIKE ? OR name LIKE ? LIMIT 1");
        $stmt->execute(['%Espace Pro%', '%Business Dashboard%']);
        $addonId = $stmt->fetchColumn();

        if (!$addonId) return false;

        // 2. Vérifier si l'utilisateur a cet addon actif
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM subscription_user_addons 
            WHERE user_id = ? AND addon_id = ? AND status = 'active'
        ");
        $stmt->execute([$userId, $addonId]);
        
        return ($stmt->fetchColumn() > 0);
    }

    /**
     * Récupère les statistiques pour une liste de POIs.
     */
    public function getStats(array $poiIds): array {
        if (empty($poiIds)) {
            return [
                'total' => [],
                'daily' => []
            ];
        }

        $inQuery = implode(',', array_fill(0, count($poiIds), '?'));
        
        // Totals
        $sqlTotal = "
            SELECT event_type, COUNT(*) as count FROM (
                SELECT event_type FROM poi_analytics WHERE poi_id IN ($inQuery)
                UNION ALL
                SELECT 'view' as event_type FROM search_poi_visits WHERE poi_id IN ($inQuery)
            ) as combined
            GROUP BY event_type";
            
        $stmt = $this->pdo->prepare($sqlTotal);
        // We need to pass $poiIds twice for the UNION ALL subqueries
        $params = array_merge($poiIds, $poiIds);
        $stmt->execute($params);
        $totalsRaw = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $totals = [];
        foreach ($totalsRaw as $row) {
            $totals[$row['event_type']] = $row['count'];
        }
        
        // Daily History (Last 30 days)
        $sqlDaily = "
            SELECT event_type, date, COUNT(*) as count FROM (
                SELECT event_type, DATE(created_at) as date FROM poi_analytics 
                WHERE poi_id IN ($inQuery) AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                UNION ALL
                SELECT 'view' as event_type, DATE(created_at) as date FROM search_poi_visits 
                WHERE poi_id IN ($inQuery) AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ) as combined
            GROUP BY event_type, date
            ORDER BY date ASC";
            
        $stmt = $this->pdo->prepare($sqlDaily);
        $stmt->execute($params);
        $dailyRaw = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return [
            'total' => $totals,
            'daily' => $dailyRaw
        ];
    }
}
