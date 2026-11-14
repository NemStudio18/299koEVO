<?php

namespace Gamification\Services;

use Core\Database;
use Core\Auth\User;
use Utils\Util;

class PointsService
{
    private $db;
    private $paliersFile;
    private $dataDir;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->dataDir = DATA_PLUGIN . 'gamification' . DS;
        $this->paliersFile = $this->dataDir . 'paliers.json';
        
        if (!is_dir($this->dataDir)) {
            @mkdir($this->dataDir, 0755, true);
        }
        
        $this->initTables();
    }
    
    private function initTables()
    {
        // Table des transactions de points
        $sql = "CREATE TABLE IF NOT EXISTS gamification_points (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            points INT NOT NULL,
            reason VARCHAR(255) NOT NULL,
            meta_data TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $this->db->query($sql);
    }

    public function getUserPoints(int $userId): int
    {
        $sql = "SELECT SUM(points) as total FROM gamification_points WHERE user_id = ?";
        $rows = $this->db->query($sql, [$userId]);
        $res = $rows[0] ?? [];
        return (int)($res['total'] ?? 0);
    }

    public function addPoints(int $userId, int $points, string $reason, array $meta = []): bool
    {
        // 1. Check Daily Quota
        $actionType = $this->mapReasonToActionType($reason);
        if ($actionType && $this->isQuotaReached($userId, $actionType)) {
            return false;
        }

        $sql = "INSERT INTO gamification_points (user_id, points, reason, meta_data) VALUES (?, ?, ?, ?)";
        $success = $this->db->execute($sql, [$userId, $points, $reason, json_encode($meta)]);
        
        if ($success) {
            $this->checkPalierUpgrade($userId);
        }
        
        return (bool)$success;
    }

    /**
     * Mappe la raison interne vers le type d'action configuré pour les limites.
     */
    private function mapReasonToActionType(string $reason): ?string
    {
        $map = [
            'review_added' => 'review',
            'proposal_submitted' => 'poi_creation',
            'suggestion_submitted' => 'edit_poi',
            'contribution_accepted' => 'contribution'
        ];
        return $map[$reason] ?? null;
    }

    /**
     * Vérifie si le quota quotidien est atteint pour une action donnée.
     */
    public function isQuotaReached(int $userId, string $actionType): bool
    {
        $quota = $this->getDailyQuota($userId, $actionType);
        if ($quota === -1) return false; // Illimité

        $today = date('Y-m-d');
        // On compte les occurences dans gamification_points pour aujourd'hui
        // Note: il faut mapper l'actionType inversement ou verifier les raisons liées
        $reasons = array_keys(array_filter([
            'review_added' => $actionType === 'review',
            'proposal_submitted' => $actionType === 'poi_creation',
            'suggestion_submitted' => $actionType === 'edit_poi',
            'contribution_accepted' => $actionType === 'contribution'
        ]));

        if (empty($reasons)) return false;

        $in = implode(',', array_fill(0, count($reasons), '?'));
        $sql = "SELECT COUNT(*) as count FROM gamification_points 
                WHERE user_id = ? AND reason IN ($in) AND DATE(created_at) = ?";
        
        $params = array_merge([$userId], $reasons, [$today]);
        $rows = $this->db->query($sql, $params);
        $count = (int)($rows[0]['count'] ?? 0);

        return ($count >= $quota);
    }

    /**
     * Récupère le quota quotidien pour un utilisateur et une action.
     */
    public function getDailyQuota(int $userId, string $actionType): int
    {
        $configFile = DATA_PLUGIN . 'gamification' . DS . 'gamification_conf.json';
        $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
        $limits = $config['daily_limits'][$actionType] ?? null;

        if (!$limits) return -1;

        $tier = $this->hasBooster($userId) ? 'booster' : 'standard';
        return (int)($limits[$tier] ?? -1);
    }

    /**
     * Vérifie si l'utilisateur a le booster de gamification actif.
     */
    public function hasBooster(int $userId): bool
    {
        $pdo = $this->db->getPdo();
        
        // 1. Trouver l'ID de l'addon "Gamification Booster"
        $stmt = $pdo->prepare("SELECT id FROM subscription_addons WHERE name LIKE ? LIMIT 1");
        $stmt->execute(['%Booster%']);
        $addonId = $stmt->fetchColumn();

        if (!$addonId) return false;

        // 2. Vérifier si l'utilisateur a cet addon actif
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM subscription_user_addons 
            WHERE user_id = ? AND addon_id = ? AND status = 'active'
        ");
        $stmt->execute([$userId, $addonId]);
        
        return ($stmt->fetchColumn() > 0);
    }

    public function getPaliers(): array
    {
        if (file_exists($this->paliersFile)) {
            $content = file_get_contents($this->paliersFile);
            $paliers = json_decode($content, true);
            return is_array($paliers) ? $paliers : [];
        }
        return [];
    }

    public function getUserPalier(int $userId): ?array
    {
        $points = $this->getUserPoints($userId);
        $paliers = $this->getPaliers();
        
        // Trier par points requis décroissant pour trouver le plus haut atteint
        usort($paliers, function($a, $b) {
            return $b['points_required'] <=> $a['points_required'];
        });

        foreach ($paliers as $palier) {
            if ($points >= $palier['points_required']) {
                return $palier;
            }
        }
        
        // Retourner le premier palier (Débutant) par défaut si aucun atteint
        return !empty($paliers) ? end($paliers) : null;
    }

    public function ensureUserHasPalier(int $userId): void
    {
        // Cette méthode semble être un legacy ou une vérification safe.
        // Avec la logique dynamique de getUserPalier, c'est implicite.
        // On peut s'en servir pour initialiser des données si besoin.
    }

    public function getPointsForAction(string $action): int
    {
        $configFile = DATA_PLUGIN . 'gamification' . DS . 'gamification_conf.json';
        $config = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];
        if (!is_array($config)) {
            $config = [];
        }
        
        $key = 'points_' . $action;
        return (int)($config[$key] ?? 0);
    }

    public function getUserTransactions(int $userId, int $limit = 50): array
    {
        $limit = (int)$limit;
        $sql = "SELECT * FROM gamification_points WHERE user_id = ? ORDER BY created_at DESC LIMIT $limit";
        return $this->db->query($sql, [$userId]);
    }
    
    public function getUserStats(int $userId): array
    {
        $points = $this->getUserPoints($userId);
        $palier = $this->getUserPalier($userId);
        $paliers = $this->getPaliers();
        
        // Trouver le prochain palier
        $nextPalier = null;
        usort($paliers, function($a, $b) { return $a['points_required'] <=> $b['points_required']; });
        
        foreach ($paliers as $p) {
            if ($p['points_required'] > $points) {
                $nextPalier = $p;
                break;
            }
        }
        
        // Stats du jour
        $today = date('Y-m-d');
        $sqlToday = "SELECT SUM(points) as pts, COUNT(*) as acts FROM gamification_points WHERE user_id = ? AND DATE(created_at) = ?";
        $rowsToday = $this->db->query($sqlToday, [$userId, $today]);
        $resToday = $rowsToday[0] ?? [];
        
        $todayMetrics = [
            'points_today' => (int)($resToday['pts'] ?? 0),
            'actions_today' => (int)($resToday['acts'] ?? 0),
            'proposals_today' => 0, // À implémenter si besoin de détails
            'contributions_today' => 0,
            'photos_today' => 0
        ];

        // Compte par type d'action pour le graphe
        // Compte par type d'action pour le graphe
        $sqlCounts = "SELECT reason, COUNT(*) as count FROM gamification_points WHERE user_id = ? GROUP BY reason";
        $counts = $this->db->query($sqlCounts, [$userId]);
        $actionsCount = [];
        foreach ($counts as $row) {
            $actionsCount[$row['reason']] = $row['count'];
        }

        return [
            'total_points' => $points,
            'current_palier' => $palier,
            'next_palier' => $nextPalier,
            'today_metrics' => $todayMetrics,
            'actions_count' => $actionsCount,
            'total_transactions' => array_sum($actionsCount)
        ];
    }
    
    private function checkPalierUpgrade(int $userId)
    {
        // Vérifier si un nouveau palier est atteint et déclencher un hook/notif
        // (Simplifié pour l'instant)
        // Pour faire bien, il faudrait stocker le dernier palier connu de l'user
    }

    public function getLeaderboard(int $limit = 100): array
    {
        $limit = (int)$limit;
        // Récupérer les points groupés par user
        $sql = "SELECT user_id, SUM(points) as total_points FROM gamification_points GROUP BY user_id ORDER BY total_points DESC LIMIT $limit";
        $rows = $this->db->query($sql);
        
        $leaderboard = [];
        foreach ($rows as $row) {
            $user = User::findPK($row['user_id']);
            if ($user) {
                $palier = $this->getUserPalier($row['user_id']);
                $leaderboard[] = [
                    'user_id' => $row['user_id'],
                    'total_points' => $row['total_points'],
                    'current_palier' => $palier,
                    'user' => $user
                ];
            }
        }
        
        return $leaderboard;
    }
}
