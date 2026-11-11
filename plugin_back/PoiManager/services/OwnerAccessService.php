<?php

namespace PoiManager\Services;

defined('ROOT') or exit('Access denied!');

/**
 * OwnerAccessService
 *
 * Vérifie les droits du propriétaire d'un POI et calcule ses quotas selon son abonnement.
 * Les limites par plan sont définies dans param/config.json du plugin.
 */
class OwnerAccessService
{
    private \PDO    $pdo;
    private array   $limits;

    public function __construct()
    {
        $this->pdo = \Core\Database::getInstance()->getPdo();

        // Charger les limites depuis la config du plugin
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('PoiManager');
        $this->limits = $plugin ? ($plugin->getConfigVal('limits') ?? []) : [];

        // Valeurs par défaut si config absente
        if (empty($this->limits)) {
            $this->limits = [
                'free'     => ['max_photos' => 3,  'max_links' => 2],
                'pro'      => ['max_photos' => 10, 'max_links' => 5],
                'pro_plus' => ['max_photos' => 20, 'max_links' => 10],
            ];
        }
    }

    // =========================================================
    // PROPRIÉTÉ
    // =========================================================

    /**
     * Vérifie si l'utilisateur est le propriétaire principal du POI (claimed_by).
     */
    public function isPrimaryOwner(int $userId, int $poiId): bool
    {
        $stmt = $this->pdo->prepare('SELECT claimed_by FROM poi_public WHERE id = ?');
        $stmt->execute([$poiId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return ($row && (int)$row['claimed_by'] === $userId);
    }

    // =========================================================
    // PLAN D'ABONNEMENT
    // =========================================================

    /**
     * Retourne le plan d'abonnement de l'utilisateur : 'free', 'pro', 'pro_plus'.
     * Vérifie d'abord si l'abonnement est actif via le plugin subscription.
     * Utilise un cache session de 15 minutes pour optimiser les performances.
     */
    public function getPlan(int $userId): string
    {
        if (!isset($_SESSION)) session_start();
        
        $cacheKey = 'sub_plan_cache_' . $userId;
        $now = time();
        
        // 1. Check Cache
        if (isset($_SESSION[$cacheKey])) {
            $cache = $_SESSION[$cacheKey];
            if (($now - $cache['timestamp']) < 900) { // 15 min
                return $cache['plan'];
            }
        }

        // 2. Fetch via Hook (Decoupled)
        // Hook 'subscription:status' should return 'pro', 'pro_plus' or 'free'.
        $activePlan = \Core\Core::getInstance()->callHook('subscription:status', ['user_id' => $userId]);

        if (empty($activePlan)) {
            $plan = 'free';
        } else {
            $plan = in_array($activePlan, ['pro', 'pro_plus'], true) ? $activePlan : 'free';
        }

        // 3. Update Cache
        $_SESSION[$cacheKey] = [
            'plan' => $plan,
            'timestamp' => $now
        ];

        return $plan;
    }

    /**
     * Invalide le cache de plan pour un utilisateur.
     * Appelé via hook subscription:updated.
     */
    public static function clearCache(array $data): void
    {
        if (!isset($_SESSION)) session_start();
        $userId = (int)($data['user_id'] ?? 0);
        if ($userId > 0) {
            unset($_SESSION['sub_plan_cache_' . $userId]);
        }
    }

    /**
     * Retourne le nombre maximum de photos autorisées selon le plan.
     */
    public function getMaxPhotos(string $plan): int
    {
        return (int)($this->limits[$plan]['max_photos'] ?? 3);
    }

    /**
     * Retourne le nombre maximum de liens autorisés selon le plan.
     */
    public function getMaxLinks(string $plan): int
    {
        return (int)($this->limits[$plan]['max_links'] ?? 2);
    }

    /**
     * Retourne la limite de caractères pour la description courte (160 pour tous).
     */
    public function getMaxShortDescLimit(): int
    {
        return 160;
    }

    /**
     * Retourne la limite de caractères pour la description longue.
     * 255 pour le plan 'free', illimité (0) pour les autres.
     */
    public function getMaxLongDescLimit(string $plan): int
    {
        return ($plan === 'free') ? 800 : 0;
    }

    // =========================================================
    // QUOTAS
    // =========================================================

    /**
     * Vérifie si le propriétaire peut encore uploader une photo.
     */
    public function canUploadPhoto(int $userId, int $poiId): bool
    {
        $plan = $this->getPlan($userId);
        $max  = $this->getMaxPhotos($plan);

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM poi_photos WHERE poi_id = ?');
        $stmt->execute([$poiId]);
        $current = (int)$stmt->fetchColumn();

        return $current < $max;
    }

    /**
     * Vérifie si le propriétaire peut encore ajouter un lien.
     */
    public function canAddLink(int $userId, int $poiId): bool
    {
        $plan = $this->getPlan($userId);
        $max  = $this->getMaxLinks($plan);

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM poi_links WHERE poi_id = ?');
        $stmt->execute([$poiId]);
        $current = (int)$stmt->fetchColumn();

        return $current < $max;
    }

    /**
     * Retourne un tableau résumé des quotas pour l'affichage dans le template.
     */
    /**
     * Retourne le nombre maximum de photos qu'un membre (non propriétaire) peut suggérer.
     * Basé sur le palier de gamification.
     */
    public function getMemberPhotoLimit(int $userId, int $poiId = 0): int
    {
        // ROLE OVERLAP LOGIC: If user is the primary owner of this POI, 
        // they use their Plan quota even in the suggestion flow.
        if ($poiId > 0 && $this->isPrimaryOwner($userId, $poiId)) {
            return $this->getMaxPhotos($this->getPlan($userId));
        }

        require_once PLUGINS . 'gamification/services/PointsService.php';
        $pointsService = new \Gamification\Services\PointsService();
        $palier = $pointsService->getUserPalier($userId);
        
        $palierId = (int)($palier['id'] ?? 1);

        // Map palier ID to photo count for standard members
        if ($palierId <= 1) return 0;
        if ($palierId == 2) return 1;
        if ($palierId == 3) return 2;
        return 3;
    }

    public function getQuotaSummary(int $userId, int $poiId): array
    {
        $plan = $this->getPlan($userId);

        $stmtP = $this->pdo->prepare('SELECT COUNT(*) FROM poi_photos WHERE poi_id = ?');
        $stmtP->execute([$poiId]);
        $photoCount = (int)$stmtP->fetchColumn();

        $stmtL = $this->pdo->prepare('SELECT COUNT(*) FROM poi_links WHERE poi_id = ?');
        $stmtL->execute([$poiId]);
        $linkCount = (int)$stmtL->fetchColumn();

        return [
            'plan'          => $plan,
            'photo_current' => $photoCount,
            'photo_max'     => $this->getMaxPhotos($plan),
            'photo_can_add' => $photoCount < $this->getMaxPhotos($plan),
            'link_current'  => $linkCount,
            'link_max'      => $this->getMaxLinks($plan),
            'link_can_add'  => $linkCount < $this->getMaxLinks($plan),
        ];
    }
}
