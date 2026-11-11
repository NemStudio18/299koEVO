<?php

namespace LightStats\Lib;

use PDO;
use Exception;

defined('ROOT') or exit('Access denied!');

/**
 * Service centralisé pour l'analytics unifié (Mode C) :
 * - Suivi individuel des parcours (visitor_id, session_id, event_id)
 * - Répartition d'usage par Module (Search, POI, Agglobus, Météo, Agenda, Blog, QR)
 * - Métriques QR Codes (impressions, téléchargements, scans physiques, taux de conversion)
 * - Stockage SQLite souverain hors webroot avec purge auto (30 jours)
 */
class LightStatsAnalyticsService
{
    private static ?self $instance = null;
    protected ?PDO $db = null;

    private array $columnCache = [];

    public function setDb(PDO $db): void
    {
        $this->db = $db;
        $this->columnCache = [];
    }

    protected function hasColumn(string $table, string $column): bool
    {
        $cacheKey = $table . '.' . $column;
        if (isset($this->columnCache[$cacheKey])) {
            return $this->columnCache[$cacheKey];
        }
        try {
            $check = $this->db->query("SELECT `{$column}` FROM `{$table}` LIMIT 0");
            $this->columnCache[$cacheKey] = ($check !== false);
        } catch (\Throwable $e) {
            $this->columnCache[$cacheKey] = false;
        }
        return $this->columnCache[$cacheKey];
    }

    /**
     * Liste blanche explicite des micro-événements métier autorisés à qualifier l'engagement
     * et à contribuer au calcul de la durée réelle de visite :
     * - Interactions POI / commerces : appel téléphonique, itinéraire, site web externe
     * - Recherche utilisateur locale : recherche textuelle ou navigation thématique
     * - Actions directes : scan / impression QR code, consultation météo, itinéraire bus, partage, contact
     *
     * Tout événement technique ou inconnu (ex: 'unknown', 'heartbeat', 'ping', 'perf_timing', 'error_log')
     * est rigoureusement ignoré pour les métriques de durée et d'engagement.
     */
    public const VALID_BUSINESS_EVENT_TYPES = [
        'call_click'        => true,
        'phone_click'       => true,
        'phone_call'        => true,
        'directions_click'  => true,
        'itinerary_click'   => true,
        'website_click'     => true,
        'search_query'      => true,
        'search_local'      => true,
        'search'            => true,
        'qr_impression'     => true,
        'qr_scan'           => true,
        'meteo_open'        => true,
        'route_lookup'      => true,
        'share_click'       => true,
        'email_click'       => true,
        'contact_click'     => true,
        'rdv_booked'        => true,
        'rdv_confirmed'     => true,
    ];

    /**
     * Événements d'interaction volontaire qualifiant l'engagement et la durée mesurée.
     * Exclut explicitement :
     * - 'qr_impression' (rendu DOM automatique de l'image QR, non action utilisateur)
     * - 'qr_scan' (redirection serveur isolée sans garantie d'attachement navigateur)
     */
    public const ENGAGEMENT_INTERACTION_TYPES = [
        'call_click'        => true,
        'phone_click'       => true,
        'phone_call'        => true,
        'directions_click'  => true,
        'itinerary_click'   => true,
        'website_click'     => true,
        'search_query'      => true,
        'search_local'      => true,
        'search'            => true,
        'meteo_open'        => true,
        'route_lookup'      => true,
        'share_click'       => true,
        'email_click'       => true,
        'contact_click'     => true,
        'rdv_booked'        => true,
        'rdv_confirmed'     => true,
    ];

    public static function isRecognizedBusinessEvent(string $eventType): bool
    {
        return isset(self::VALID_BUSINESS_EVENT_TYPES[strtolower(trim($eventType))]);
    }

    public static function isEngagementInteraction(string $eventType): bool
    {
        return isset(self::ENGAGEMENT_INTERACTION_TYPES[strtolower(trim($eventType))]);
    }

    private function __construct()
    {
        $this->initDb();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initDb(): void
    {
        try {
            // 1. Priorité MySQL via Core\Database
            $this->db = \Core\Database::getInstance()->getPdo();
        } catch (\Throwable $e) {
            // 2. Fallback SQLite si MySQL indisponible
            $dir = DATA_PLUGIN . 'lightstats/';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $dbPath = $dir . 'analytics.sqlite';
            $this->db = new PDO('sqlite:' . $dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        $this->ensureSchema();
    }

    private function ensureSchema(): void
    {
        try {
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $this->db->exec("
                    CREATE TABLE IF NOT EXISTS ls_visitors (
                        visitor_id TEXT PRIMARY KEY,
                        first_seen DATETIME NOT NULL,
                        last_seen DATETIME NOT NULL,
                        visit_count INTEGER NOT NULL DEFAULT 1,
                        consent_given INTEGER NOT NULL DEFAULT 0
                    );
                    CREATE TABLE IF NOT EXISTS ls_sessions (
                        session_id TEXT PRIMARY KEY,
                        visitor_id TEXT NULL,
                        ip_hash TEXT NOT NULL,
                        started_at DATETIME NOT NULL,
                        ended_at DATETIME NOT NULL,
                        duration_seconds INTEGER NOT NULL DEFAULT 0,
                        page_count INTEGER NOT NULL DEFAULT 1,
                        entry_page TEXT NOT NULL,
                        exit_page TEXT NOT NULL,
                        device_type TEXT NOT NULL DEFAULT 'desktop',
                        referer TEXT NULL
                    );
                    CREATE TABLE IF NOT EXISTS ls_events (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        session_id TEXT NOT NULL,
                        visitor_id TEXT NULL,
                        module TEXT NOT NULL,
                        event_type TEXT NOT NULL,
                        page TEXT NOT NULL,
                        event_data TEXT NULL,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                    );
                ");
            } else {
                // MySQL
                $this->db->exec("
                    CREATE TABLE IF NOT EXISTS ls_visitors (
                        visitor_id VARCHAR(64) PRIMARY KEY,
                        first_seen DATETIME NOT NULL,
                        last_seen DATETIME NOT NULL,
                        visit_count INT NOT NULL DEFAULT 1,
                        consent_given TINYINT NOT NULL DEFAULT 0
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                    CREATE TABLE IF NOT EXISTS ls_sessions (
                        session_id VARCHAR(64) PRIMARY KEY,
                        visitor_id VARCHAR(64) NULL,
                        ip_hash VARCHAR(64) NOT NULL,
                        started_at DATETIME NOT NULL,
                        ended_at DATETIME NOT NULL,
                        duration_seconds INT NOT NULL DEFAULT 0,
                        page_count INT NOT NULL DEFAULT 1,
                        entry_page VARCHAR(255) NOT NULL,
                        exit_page VARCHAR(255) NOT NULL,
                        device_type VARCHAR(20) NOT NULL DEFAULT 'desktop',
                        referer TEXT NULL,
                        INDEX idx_sessions_visitor (visitor_id),
                        INDEX idx_sessions_started (started_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                    CREATE TABLE IF NOT EXISTS ls_events (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        session_id VARCHAR(64) NOT NULL,
                        visitor_id VARCHAR(64) NULL,
                        module VARCHAR(40) NOT NULL,
                        event_type VARCHAR(50) NOT NULL,
                        page VARCHAR(255) NOT NULL,
                        event_data TEXT NULL,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_events_session (session_id),
                        INDEX idx_events_module (module),
                        INDEX idx_events_type (event_type),
                        INDEX idx_events_created (created_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");
            }
        } catch (\Throwable $e) {}
    }

    /**
     * Catégorise automatiquement une URL dans un Module métier de Vivre-Bourges
     */
    public static function classifyModule(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '/';
        $path = rtrim($path, '/');
        if ($path === '' || $path === '/') {
            return 'home';
        }
        if (str_starts_with($path, '/agglobus') || str_starts_with($path, '/Agglobus')) {
            return 'agglobus';
        }
        if (str_starts_with($path, '/meteo')) {
            return 'meteo';
        }
        if (str_starts_with($path, '/SearchUi') || str_starts_with($path, '/recherche')) {
            return 'search';
        }
        if (str_starts_with($path, '/agenda')) {
            return 'agenda';
        }
        if (str_starts_with($path, '/blog')) {
            return 'blog';
        }
        if (str_starts_with($path, '/qr/poi') || str_starts_with($path, '/qr-poi')) {
            return 'qr_poi';
        }
        if (preg_match('#^/([a-z0-9-]+)-(\d+)$#i', $path) || preg_match('#^/SearchUi/poi/#i', $path)) {
            return 'poi';
        }
        if (str_starts_with($path, '/comment-revendiquer') || str_starts_with($path, '/contact') || str_starts_with($path, '/dons')) {
            return 'services';
        }
        return 'other';
    }

    /**
     * Détecte le type d'appareil (Desktop, Mobile, Tablette)
     */
    public static function detectDeviceType(?string $ua): string
    {
        if (empty($ua)) return 'desktop';
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $ua)) {
            return 'tablet';
        }
        if (preg_match('/Mobile|iP(hone|od)|Android|BlackBerry|IEMobile|Kindle|Silk-Accelerated|(hpw|web)OS|Opera M(obi|ini)/i', $ua)) {
            return 'mobile';
        }
        return 'desktop';
    }

    /**
     * Enregistre ou met à jour une visite (pageview)
     */
    public function recordPageview(
        string $page,
        string $ipHash,
        ?string $visitorId = null,
        ?string $sessionId = null,
        ?string $referer = null,
        ?string $userAgent = null,
        array $extraData = [],
        ?string $requestId = null
    ): array {
        try {
            $now = date('Y-m-d H:i:s');
            $module = self::classifyModule($page);
            $deviceType = self::detectDeviceType($userAgent);

            // Gérer le visiteur si ID présent (consentement accordé)
            if (!empty($visitorId)) {
                $stmt = $this->db->prepare("SELECT visitor_id, visit_count, last_seen FROM ls_visitors WHERE visitor_id = ?");
                $stmt->execute([$visitorId]);
                $visitor = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($visitor) {
                    // Si la dernière visite date de plus de 4 heures, incrémenter le compteur de visites
                    $lastSeenTs = strtotime($visitor['last_seen']);
                    $isNewDay = (time() - $lastSeenTs) > (4 * 3600);
                    $newCount = $isNewDay ? ($visitor['visit_count'] + 1) : $visitor['visit_count'];

                    $up = $this->db->prepare("UPDATE ls_visitors SET last_seen = ?, visit_count = ?, consent_given = 1 WHERE visitor_id = ?");
                    $up->execute([$now, $newCount, $visitorId]);
                } else {
                    $ins = $this->db->prepare("INSERT INTO ls_visitors (visitor_id, first_seen, last_seen, visit_count, consent_given) VALUES (?, ?, ?, 1, 1)");
                    $ins->execute([$visitorId, $now, $now]);
                }
            }

            // Gérer la session
            if (empty($sessionId)) {
                $sessionId = bin2hex(random_bytes(16));
            }

            $stmt = $this->db->prepare("SELECT session_id, started_at, page_count FROM ls_sessions WHERE session_id = ?");
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($session) {
                $startTs = strtotime($session['started_at']);
                $duration = max(0, time() - $startTs);
                $up = $this->db->prepare("UPDATE ls_sessions SET ended_at = ?, duration_seconds = ?, page_count = page_count + 1, exit_page = ?, visitor_id = COALESCE(visitor_id, ?) WHERE session_id = ?");
                $up->execute([$now, $duration, $page, $visitorId, $sessionId]);
            } else {
                $ins = $this->db->prepare("INSERT INTO ls_sessions (session_id, visitor_id, ip_hash, started_at, ended_at, duration_seconds, page_count, entry_page, exit_page, device_type, referer) VALUES (?, ?, ?, ?, ?, 0, 1, ?, ?, ?, ?)");
                $ins->execute([$sessionId, $visitorId, $ipHash, $now, $now, $page, $page, $deviceType, $referer]);
            }

            // Enregistrer l'événement pageview HTTP avec son request_id
            if (empty($requestId)) {
                $requestId = 'req_' . bin2hex(random_bytes(16));
            }
            $extraData['request_id'] = $requestId;
            $eventDataJson = !empty($extraData) ? json_encode($extraData, JSON_UNESCAPED_UNICODE) : null;
            if ($this->hasColumn('ls_events', 'request_id')) {
                $insEv = $this->db->prepare("INSERT INTO ls_events (request_id, session_id, visitor_id, module, event_type, page, event_data, created_at) VALUES (?, ?, ?, ?, 'pageview', ?, ?, ?)");
                $insEv->execute([$requestId, $sessionId, $visitorId, $module, $page, $eventDataJson, $now]);
            } else {
                $insEv = $this->db->prepare("INSERT INTO ls_events (session_id, visitor_id, module, event_type, page, event_data, created_at) VALUES (?, ?, ?, 'pageview', ?, ?, ?)");
                $insEv->execute([$sessionId, $visitorId, $module, $page, $eventDataJson, $now]);
            }

            return ['session_id' => $sessionId, 'visitor_id' => $visitorId, 'request_id' => $requestId];
        } catch (Exception $e) {
            error_log('[LightStatsAnalytics] Erreur recordPageview: ' . $e->getMessage());
            return ['session_id' => $sessionId ?? '', 'visitor_id' => $visitorId ?? '', 'request_id' => $requestId ?? ''];
        }
    }

    /**
     * Enregistre un micro-événement (clic, action, QR code scan/impression, certification, etc.)
     * Idempotent pour les doublons de clés uniques sans masquer les erreurs réelles
     */
    public function recordEvent(
        string $sessionId,
        ?string $visitorId,
        string $module,
        string $eventType,
        string $page,
        array $eventData = [],
        ?string $eventId = null,
        ?string $requestId = null
    ): array {
        // Protection 1 : event_id (généré automatiquement si absent pour rétrocompatibilité avec la collecte souveraine)
        if (empty($eventId)) {
            $eventId = 'evt_' . bin2hex(random_bytes(16));
        } elseif (!preg_match('/^evt_[a-f0-9\-]{16,64}$/', $eventId)) {
            return [
                'status' => 'invalid',
                'message' => 'Événement rejeté : event_id manquant ou format invalide',
                'code' => 400
            ];
        }

        // Protection 2 : Une certification view_certified requiert obligatoirement un request_id valide
        // Format standard strict : req_ suivi de 32 caractères hexadécimaux
        if ($eventType === 'view_certified') {
            if (empty($requestId) || !preg_match('/^req_[a-f0-9]{32}$/', $requestId)) {
                return [
                    'status' => 'invalid',
                    'message' => 'Certification rejetée : request_id manquant ou format invalide',
                    'code' => 400
                ];
            }
        }

        try {
            $now = date('Y-m-d H:i:s');
            if (!empty($eventId)) {
                $eventData['event_id'] = $eventId;
            }
            if (!empty($requestId)) {
                $eventData['request_id'] = $requestId;
            }
            $dataJson = !empty($eventData) ? json_encode($eventData, JSON_UNESCAPED_UNICODE) : null;
            $hasEvt = $this->hasColumn('ls_events', 'event_id');
            $hasReq = $this->hasColumn('ls_events', 'request_id');
            if ($hasEvt && $hasReq) {
                $stmt = $this->db->prepare("
                    INSERT INTO ls_events (event_id, request_id, session_id, visitor_id, module, event_type, page, event_data, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$eventId, $requestId, $sessionId, $visitorId, $module, $eventType, $page, $dataJson, $now]);
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO ls_events (session_id, visitor_id, module, event_type, page, event_data, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$sessionId, $visitorId, $module, $eventType, $page, $dataJson, $now]);
            }
            return ['status' => 'ok', 'code' => 200];
        } catch (\PDOException $e) {
            $driverCode = (int) ($e->errorInfo[1] ?? 0);
            
            // Détection stricte et exclusive du code 1062 (ER_DUP_ENTRY sur index unique)
            if ($driverCode === 1062) {
                $errorMsg = $e->getMessage();
                if (str_contains($errorMsg, 'uniq_page_certification')) {
                    return [
                        'status' => 'duplicate',
                        'conflict' => 'certification',
                        'message' => 'Certification déjà enregistrée pour cette requête (idempotent)',
                        'code' => 200
                    ];
                }
                return [
                    'status' => 'duplicate',
                    'conflict' => 'event',
                    'message' => 'Événement déjà enregistré (réémission ignorée)',
                    'code' => 200
                ];
            }

            // Tout autre code d'erreur (y compris autres violations d'intégrité SQLSTATE 23000 non-1062) :
            // remonte comme une véritable erreur technique 500 et est loggé.
            error_log('[LightStatsAnalytics] Erreur SQL recordEvent (Driver Code ' . $driverCode . '): ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Erreur technique base de données',
                'code' => 500
            ];
        } catch (\Throwable $e) {
            error_log('[LightStatsAnalytics] Erreur recordEvent: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Erreur interne',
                'code' => 500
            ];
        }
    }

    /**
     * Statistiques globales par Module (Pages et Événements)
     */
    public function getModuleStats(string $dateStart, string $dateEnd): array
    {
        try {
            $start = $dateStart . ' 00:00:00';
            $end = $dateEnd . ' 23:59:59';

            $stmt = $this->db->prepare("
                SELECT module, COUNT(*) as hits
                FROM ls_events
                WHERE created_at BETWEEN ? AND ?
                GROUP BY module
                ORDER BY hits DESC
            ");
            $stmt->execute([$start, $end]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $total = 0;
            foreach ($rows as $r) {
                $total += (int)$r['hits'];
            }

            $labels = [
                'search'   => 'Recherche & Filtres',
                'poi'      => 'Fiches POI / Commerces',
                'agglobus' => 'Agglobus (Transports)',
                'agenda'   => 'Agenda des Sorties',
                'meteo'    => 'Météo',
                'blog'     => 'Blog / Actualités',
                'qr_poi'   => 'QR Codes (Scans)',
                'home'     => 'Page d\'Accueil',
                'services' => 'Services & Contact',
                'other'    => 'Autres'
            ];

            $results = [];
            foreach ($rows as $r) {
                $m = $r['module'];
                $count = (int)$r['hits'];
                $percent = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                $results[$m] = [
                    'module'  => $m,
                    'label'   => $labels[$m] ?? ucfirst($m),
                    'hits'    => $count,
                    'percent' => $percent
                ];
            }

            return ['total' => $total, 'modules' => $results];
        } catch (Exception $e) {
            error_log('[LightStatsAnalytics] Erreur getModuleStats: ' . $e->getMessage());
            return ['total' => 0, 'modules' => []];
        }
    }

    /**
     * Statistiques complètes sur les QR Codes (Impressions, Téléchargements, Scans, Taux)
     */
    public function getQrCodeStats(string $dateStart, string $dateEnd): array
    {
        try {
            $start = $dateStart . ' 00:00:00';
            $end = $dateEnd . ' 23:59:59';

            // 1. Scans physiques
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total_scans, COUNT(DISTINCT visitor_id) as unique_scanners
                FROM ls_events
                WHERE event_type = 'qr_scan' AND created_at BETWEEN ? AND ?
            ");
            $stmt->execute([$start, $end]);
            $scans = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_scans' => 0, 'unique_scanners' => 0];

            // 2. Impressions web (vues de QR code sur la fiche)
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total_impressions
                FROM ls_events
                WHERE event_type = 'qr_impression' AND created_at BETWEEN ? AND ?
            ");
            $stmt->execute([$start, $end]);
            $impressions = (int)($stmt->fetchColumn() ?: 0);

            // 3. Téléchargements pour impression physique
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total_downloads
                FROM ls_events
                WHERE event_type = 'qr_download' AND created_at BETWEEN ? AND ?
            ");
            $stmt->execute([$start, $end]);
            $downloads = (int)($stmt->fetchColumn() ?: 0);

            // 4. Vues totales des fiches POI (pour calculer le taux de scan par rapport à la fréquentation)
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM ls_events
                WHERE module = 'poi' AND event_type = 'pageview' AND created_at BETWEEN ? AND ?
            ");
            $stmt->execute([$start, $end]);
            $poiViews = (int)($stmt->fetchColumn() ?: 0);

            $scanRate = $poiViews > 0 ? round(((int)$scans['total_scans'] / $poiViews) * 100, 2) : 0.0;

            // 5. Top POIs scannés
            $stmt = $this->db->prepare("
                SELECT event_data, COUNT(*) as scan_count
                FROM ls_events
                WHERE event_type = 'qr_scan' AND created_at BETWEEN ? AND ?
                GROUP BY event_data
                ORDER BY scan_count DESC
                LIMIT 10
            ");
            $stmt->execute([$start, $end]);
            $topPois = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $decoded = json_decode($row['event_data'] ?? '{}', true) ?: [];
                $topPois[] = [
                    'poi_id' => $decoded['poi_id'] ?? 0,
                    'name'   => $decoded['name'] ?? ('POI #' . ($decoded['poi_id'] ?? '?')),
                    'scans'  => (int)$row['scan_count']
                ];
            }

            return [
                'total_scans'       => (int)$scans['total_scans'],
                'unique_scanners'   => (int)$scans['unique_scanners'],
                'total_impressions' => $impressions,
                'total_downloads'   => $downloads,
                'poi_views'         => $poiViews,
                'scan_rate'         => $scanRate,
                'top_pois'          => $topPois
            ];
        } catch (Exception $e) {
            error_log('[LightStatsAnalytics] Erreur getQrCodeStats: ' . $e->getMessage());
            return [
                'total_scans' => 0, 'unique_scanners' => 0, 'total_impressions' => 0,
                'total_downloads' => 0, 'poi_views' => 0, 'scan_rate' => 0.0, 'top_pois' => []
            ];
        }
    }

    /**
     * Récupère la liste des derniers parcours visiteurs pour l'onglet « Parcours »
     */
    public function getRecentJourneys(int $limit = 30): array
    {
        try {
            $cleanLimit = max(1, min(100, (int)$limit));
            $stmt = $this->db->query("
                SELECT s.*, v.visit_count, v.first_seen, v.consent_given
                FROM ls_sessions s
                LEFT JOIN ls_visitors v ON s.visitor_id = v.visitor_id
                ORDER BY s.started_at DESC
                LIMIT {$cleanLimit}
            ");
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($sessions as &$sess) {
                // Récupérer les événements chronologiques de la session
                $evStmt = $this->db->prepare("
                    SELECT module, event_type, page, event_data, created_at
                    FROM ls_events
                    WHERE session_id = ?
                    ORDER BY id ASC
                ");
                $evStmt->execute([$sess['session_id']]);
                $sess['events'] = $evStmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($sess['events'] as &$ev) {
                    $ev['data'] = json_decode($ev['event_data'] ?? '{}', true) ?: [];
                }
            }
            return $sessions;
        } catch (Exception $e) {
            error_log('[LightStatsAnalytics] Erreur getRecentJourneys: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Résout proprement les bornes SQL temporelles (format Y-m-d H:i:s)
     * Rétrocompatible avec les entiers $days et conforme pour les périodes absolues YYYY-MM-DD.
     */
    public static function resolveDateBounds($dateStart, ?string $dateEnd = null, int $defaultDays = 30): array
    {
        if (is_numeric($dateStart) && $dateEnd === null) {
            $days = max(1, (int)$dateStart);
            return [
                date('Y-m-d 00:00:00', strtotime("-{$days} days")),
                date('Y-m-d 23:59:59')
            ];
        }

        if (!empty($dateStart)) {
            $startStr = (string)$dateStart;
            $endStr = !empty($dateEnd) ? (string)$dateEnd : $startStr;
            return [
                date('Y-m-d 00:00:00', strtotime($startStr)),
                date('Y-m-d 23:59:59', strtotime($endStr))
            ];
        }

        return [
            date('Y-m-d 00:00:00', strtotime("-{$defaultDays} days")),
            date('Y-m-d 23:59:59')
        ];
    }

    /**
     * Top recherches les plus fréquentes sur la période sélectionnée
     * @param int $limit Nombre maximal de résultats
     * @param string|int|null $dateStart Date de début 'YYYY-MM-DD' (ou int $days pour rétrocompatibilité)
     * @param string|null $dateEnd Date de fin 'YYYY-MM-DD'
     */
    public function getTopSearches(int $limit = 15, $dateStart = null, ?string $dateEnd = null): array
    {
        try {
            [$startBound, $endBound] = $this->resolveDateBounds($dateStart, $dateEnd, 30);
            $cleanLimit = max(1, min(100, (int)$limit));
            $stmt = $this->db->prepare("
                SELECT 
                    query_norm,
                    COUNT(*) as search_count,
                    ROUND(AVG(nb_results), 1) as avg_results,
                    MAX(created_at) as last_searched
                FROM search_logs
                WHERE created_at BETWEEN ? AND ?
                GROUP BY query_norm
                ORDER BY search_count DESC
                LIMIT {$cleanLimit}
            ");
            $stmt->execute([$startBound, $endBound]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('[LightStatsAnalytics] Erreur getTopSearches: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Top recherches ayant retourné 0 résultat (opportunités de contenu / synonymes)
     * @param int $limit Nombre maximal de résultats
     * @param string|int|null $dateStart Date de début 'YYYY-MM-DD' (ou int $days pour rétrocompatibilité)
     * @param string|null $dateEnd Date de fin 'YYYY-MM-DD'
     */
    public function getZeroResultSearches(int $limit = 15, $dateStart = null, ?string $dateEnd = null): array
    {
        try {
            [$startBound, $endBound] = $this->resolveDateBounds($dateStart, $dateEnd, 30);
            $cleanLimit = max(1, min(100, (int)$limit));
            $stmt = $this->db->prepare("
                SELECT 
                    query_norm,
                    COUNT(*) as zero_count,
                    MAX(created_at) as last_searched
                FROM search_logs
                WHERE nb_results = 0
                  AND created_at BETWEEN ? AND ?
                GROUP BY query_norm
                ORDER BY zero_count DESC
                LIMIT {$cleanLimit}
            ");
            $stmt->execute([$startBound, $endBound]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('[LightStatsAnalytics] Erreur getZeroResultSearches: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Top fiches commerces/POI les plus affichées (impressions dans listes / résultats)
     * @param int $limit Nombre maximal de résultats
     * @param string|int|null $dateStart Date de début 'YYYY-MM-DD' (ou int $days pour rétrocompatibilité)
     * @param string|null $dateEnd Date de fin 'YYYY-MM-DD'
     */
    public function getTopPoiImpressions(int $limit = 15, $dateStart = null, ?string $dateEnd = null): array
    {
        try {
            [$startBound, $endBound] = $this->resolveDateBounds($dateStart, $dateEnd, 30);
            $cleanLimit = max(1, min(100, (int)$limit));
            $stmt = $this->db->prepare("
                SELECT 
                    a.poi_id,
                    COALESCE(p.name, CONCAT('Fiche #', a.poi_id)) as name,
                    p.category,
                    p.addr_city as city,
                    COUNT(*) as impression_count,
                    MAX(a.created_at) as last_impression
                FROM poi_analytics a
                LEFT JOIN poi_search p ON a.poi_id = p.poi_id
                WHERE a.event_type = 'impression'
                  AND a.created_at BETWEEN ? AND ?
                GROUP BY a.poi_id, p.name, p.category, p.addr_city
                ORDER BY impression_count DESC
                LIMIT {$cleanLimit}
            ");
            $stmt->execute([$startBound, $endBound]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('[LightStatsAnalytics] Erreur getTopPoiImpressions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Top fiches commerces/POI les plus cliquées ou consultées
     * @param int $limit Nombre maximal de résultats
     * @param string|int|null $dateStart Date de début 'YYYY-MM-DD' (ou int $days pour rétrocompatibilité)
     * @param string|null $dateEnd Date de fin 'YYYY-MM-DD'
     */
    public function getTopPoiClicks(int $limit = 15, $dateStart = null, ?string $dateEnd = null): array
    {
        try {
            [$startBound, $endBound] = $this->resolveDateBounds($dateStart, $dateEnd, 30);
            $cleanLimit = max(1, min(100, (int)$limit));
            $stmt = $this->db->prepare("
                SELECT 
                    a.poi_id,
                    COALESCE(p.name, CONCAT('Fiche #', a.poi_id)) as name,
                    p.category,
                    p.addr_city as city,
                    COUNT(*) as click_count,
                    MAX(a.created_at) as last_click
                FROM poi_analytics a
                LEFT JOIN poi_search p ON a.poi_id = p.poi_id
                WHERE a.event_type = 'view'
                  AND a.created_at BETWEEN ? AND ?
                GROUP BY a.poi_id, p.name, p.category, p.addr_city
                ORDER BY click_count DESC
                LIMIT {$cleanLimit}
            ");
            $stmt->execute([$startBound, $endBound]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('[LightStatsAnalytics] Erreur getTopPoiClicks: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Statistiques consolidées des actions utiles sur les fiches commerces/POI
     */
    public function getPoiActionStats(string $dateStart, string $dateEnd): array
    {
        try {
            [$startBound, $endBound] = $this->resolveDateBounds($dateStart, $dateEnd, 30);
            $stmt = $this->db->prepare("
                SELECT event_type, COUNT(*) as action_count
                FROM ls_events
                WHERE module IN ('poi', 'SearchUi')
                  AND event_type IN ('call_click', 'phone_click', 'phone_call', 'directions_click', 'itinerary_click', 'website_click', 'share_click', 'email_click', 'contact_click')
                  AND created_at BETWEEN ? AND ?
                GROUP BY event_type
            ");
            $stmt->execute([$startBound, $endBound]);
            $rawRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $rows = [];
            foreach ($rawRows as $r) {
                $rows[$r['event_type']] = (int)$r['action_count'];
            }

            $calls = ($rows['call_click'] ?? 0) + ($rows['phone_click'] ?? 0) + ($rows['phone_call'] ?? 0);
            $directions = ($rows['directions_click'] ?? 0) + ($rows['itinerary_click'] ?? 0);
            $websites = $rows['website_click'] ?? 0;
            $shares = $rows['share_click'] ?? 0;
            $contacts = ($rows['email_click'] ?? 0) + ($rows['contact_click'] ?? 0);
            $totalUseful = $calls + $directions + $websites + $shares + $contacts;

            return [
                'total_useful_actions' => $totalUseful,
                'phone_calls'          => $calls,
                'directions'           => $directions,
                'website_clicks'       => $websites,
                'shares'               => $shares,
                'contacts'             => $contacts,
            ];
        } catch (\Throwable $e) {
            error_log('[LightStatsAnalytics] Erreur getPoiActionStats: ' . $e->getMessage());
            return [
                'total_useful_actions' => 0, 'phone_calls' => 0, 'directions' => 0,
                'website_clicks' => 0, 'shares' => 0, 'contacts' => 0
            ];
        }
    }

    /**
     * Purge automatique des événements de plus de N jours (RGPD & hygiène disque)
     */
    public function purgeOldData(int $days = 30): void
    {
        try {
            $cutoff = date('Y-m-d H:i:s', strtotime("-$days days"));
            $this->db->prepare("DELETE FROM ls_events WHERE created_at < ?")->execute([$cutoff]);
            $this->db->prepare("DELETE FROM ls_sessions WHERE ended_at < ?")->execute([$cutoff]);
        } catch (Exception $e) {
            error_log('[LightStatsAnalytics] Erreur purge: ' . $e->getMessage());
        }
    }

    /**
     * Consolidation analytique des sessions et indicateurs multiniveaux (Étapes 3 & 4)
     * Distingue rigoureusement :
     * - session_id : session technique (découpage sur fenêtre d'inactivité de 30 min)
     * - request_id : réponse HTML HTTP unitaire
     * - event_id   : micro-événement navigateur
     * - visitor_id : consentement explicite uniquement
     *
     * @param string $dateStart Format YYYY-MM-DD
     * @param string $dateEnd   Format YYYY-MM-DD
     * @return array Structure complète à 5 niveaux
     */
    /**
     * Consolidation analytique des sessions et indicateurs multiniveaux (Étapes 3 & 4)
     * Distingue rigoureusement :
     * - session_id : session technique (découpage sur fenêtre d'inactivité de 30 min / 1800s)
     * - request_id : réponse HTML HTTP unitaire (admissible)
     * - event_id   : micro-événement navigateur
     * - visitor_id : consentement explicite uniquement (cookie serveur vb_vid)
     *
     * @param string $dateStart Format YYYY-MM-DD
     * @param string $dateEnd   Format YYYY-MM-DD
     * @return array Structure complète à 5 niveaux
     */
    public function getConsolidatedSessionStats(string $dateStart, string $dateEnd): array
    {
        $periodStart = $dateStart . ' 00:00:00';
        $periodEnd   = $dateEnd . ' 23:59:59';
        // Fenêtre de contexte préfixe de 30 minutes (1800s) pour assurer la continuité des sessions traversant minuit
        $contextStart = date('Y-m-d H:i:s', strtotime($periodStart) - 1800);

        // 1. Récupération des événements (période + fenêtre de contexte de 30 min)
        $hasReqCol = $this->hasColumn('ls_events', 'request_id');
        $hasEvtCol = $this->hasColumn('ls_events', 'event_id');
        $extraCols = '';
        if ($hasEvtCol) {
            $extraCols .= ', event_id';
        }
        if ($hasReqCol) {
            $extraCols .= ', request_id';
        }

        if ((int)ini_get('memory_limit') < 256) {
            @ini_set('memory_limit', '256M');
        }

        $stmt = $this->db->prepare("
            SELECT id {$extraCols}, session_id, visitor_id, module, event_type, page, event_data, created_at
            FROM ls_events
            WHERE created_at BETWEEN ? AND ?
            ORDER BY session_id ASC, created_at ASC, id ASC
        ");
        $stmt->execute([$contextStart, $periodEnd]);

        // 2. Découpage et agrégation en streaming (mémoire O(1)) avec fenêtre d'inactivité de 30 minutes
        $sessionTimeout = 1800;
        $currentSessionWindow = null;

        $totalSessionsCount = 0;
        $certifiedSessionsCount = 0;
        $unverifiedHttpSessionsCount = 0;

        $strictBouncesCount = 0;
        $engagedSinglePageCount = 0;
        $multiPageCount = 0;

        $totalMeasuredDuration = 0;
        $measurableSessionsCount = 0;
        $unmeasurableSessionsCount = 0;

        $consentedVisitors = [];
        $anonymousSessionsCount = 0;

        $allPeriodHttpRequestIds = [];
        $allPeriodCertifiedRequestIds = [];
        $totalBusinessEvents = 0;

        $landingPageCounts = [];
        $exitPageCounts = [];
        $sessionsSample = [];

        $processCompletedSegment = function (array $sess) use (
            &$totalSessionsCount,
            &$certifiedSessionsCount,
            &$unverifiedHttpSessionsCount,
            &$strictBouncesCount,
            &$engagedSinglePageCount,
            &$multiPageCount,
            &$totalMeasuredDuration,
            &$measurableSessionsCount,
            &$unmeasurableSessionsCount,
            &$consentedVisitors,
            &$anonymousSessionsCount,
            &$allPeriodHttpRequestIds,
            &$allPeriodCertifiedRequestIds,
            &$totalBusinessEvents,
            &$landingPageCounts,
            &$exitPageCounts,
            &$sessionsSample
        ) {
            if (!$sess['has_event_in_period']) {
                return;
            }

            $totalSessionsCount++;

            foreach ($sess['period_http_request_ids'] as $reqId => $_) {
                $allPeriodHttpRequestIds[$reqId] = true;
            }
            foreach ($sess['period_certified_request_ids'] as $reqId => $_) {
                $allPeriodCertifiedRequestIds[$reqId] = true;
            }
            $totalBusinessEvents += $sess['business_events_count'];

            if (!empty($sess['visitor_id'])) {
                $consentedVisitors[$sess['visitor_id']] = true;
            } else {
                $anonymousSessionsCount++;
            }

            $distinctSessionCertifiedCount = count($sess['session_certified_request_ids']);
            $sess['distinct_certified_pages'] = $distinctSessionCertifiedCount;
            $isCertified = ($distinctSessionCertifiedCount > 0);
            $sess['is_certified'] = $isCertified;

            if ($isCertified) {
                $certifiedSessionsCount++;

                $lp = !empty($sess['entry_page']) ? $sess['entry_page'] : '/';
                $ep = !empty($sess['exit_page']) ? $sess['exit_page'] : '/';
                $landingPageCounts[$lp] = ($landingPageCounts[$lp] ?? 0) + 1;
                $exitPageCounts[$ep] = ($exitPageCounts[$ep] ?? 0) + 1;

                if ($distinctSessionCertifiedCount === 1) {
                    if ($sess['business_events_count'] === 0) {
                        $sess['engagement_type'] = 'strict_bounce';
                        $strictBouncesCount++;
                    } else {
                        $sess['engagement_type'] = 'engaged_single_page';
                        $engagedSinglePageCount++;
                    }
                } else {
                    $sess['engagement_type'] = 'multi_page';
                    $multiPageCount++;
                }

                $interactions = $sess['interaction_timestamps'];
                if (count($interactions) >= 2) {
                    $tMin = min($interactions);
                    $tMax = max($interactions);
                    $durationSec = $tMax - $tMin;
                    if ($durationSec > 0) {
                        $sess['duration_type'] = 'measured';
                        $sess['duration_seconds'] = $durationSec;
                        $totalMeasuredDuration += $durationSec;
                        $measurableSessionsCount++;
                    } else {
                        $sess['duration_type'] = 'unmeasurable';
                        $sess['duration_seconds'] = null;
                        $unmeasurableSessionsCount++;
                    }
                } else {
                    $sess['duration_type'] = 'unmeasurable';
                    $sess['duration_seconds'] = null;
                    $unmeasurableSessionsCount++;
                }
            } else {
                $unverifiedHttpSessionsCount++;
                $sess['engagement_type'] = 'unverified_http';
                $sess['duration_type'] = 'unmeasurable';
                $sess['duration_seconds'] = null;
                $unmeasurableSessionsCount++;
            }

            if (count($sessionsSample) < 50) {
                $sessionsSample[] = $sess;
            }
        };

        while ($ev = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $evData = !empty($ev['event_data']) ? json_decode($ev['event_data'], true) : [];
            if (!is_array($evData)) {
                $evData = [];
            }
            $reqId = $ev['request_id'] ?? ($evData['request_id'] ?? null);
            if (empty($reqId)) {
                if ($ev['event_type'] === 'pageview' || $ev['event_type'] === 'view_certified') {
                    $reqId = 'req_sess_' . $ev['session_id'] . '_' . md5($ev['page']);
                }
            }
            $ev['request_id'] = $reqId;
            $ev['event_id'] = $ev['event_id'] ?? ($evData['event_id'] ?? null);

            $sid = $ev['session_id'];
            $evTime = strtotime($ev['created_at']);

            $isNewSegment = false;
            if ($currentSessionWindow === null || $currentSessionWindow['session_id'] !== $sid) {
                $isNewSegment = true;
            } elseif (($evTime - $currentSessionWindow['last_timestamp']) > $sessionTimeout) {
                $isNewSegment = true;
            }

            if ($isNewSegment) {
                if ($currentSessionWindow !== null) {
                    $processCompletedSegment($currentSessionWindow);
                }
                $currentSessionWindow = [
                    'session_id'                    => $sid,
                    'visitor_id'                    => $ev['visitor_id'] ?: null,
                    'started_at'                    => $ev['created_at'],
                    'ended_at'                      => $ev['created_at'],
                    'first_timestamp'               => $evTime,
                    'last_timestamp'                => $evTime,
                    'has_event_in_period'           => false,
                    'period_http_request_ids'       => [],
                    'period_certified_request_ids'  => [],
                    'session_certified_request_ids' => [],
                    'business_events_count'         => 0,
                    'entry_page'                    => $ev['page'] ?: '/',
                    'exit_page'                     => $ev['page'] ?: '/',
                    'interaction_timestamps'        => [],
                    'business_event_types'          => [],
                ];
            }

            $currentSessionWindow['ended_at'] = $ev['created_at'];
            $currentSessionWindow['last_timestamp'] = $evTime;
            $currentSessionWindow['exit_page'] = $ev['page'] ?: $currentSessionWindow['exit_page'];

            if (!empty($ev['visitor_id']) && empty($currentSessionWindow['visitor_id'])) {
                $currentSessionWindow['visitor_id'] = $ev['visitor_id'];
            }

            $isInPeriod = ($ev['created_at'] >= $periodStart && $ev['created_at'] <= $periodEnd);
            if ($isInPeriod) {
                $currentSessionWindow['has_event_in_period'] = true;
            }

            if ($ev['event_type'] === 'pageview') {
                if ($isInPeriod && !empty($ev['request_id'])) {
                    $currentSessionWindow['period_http_request_ids'][$ev['request_id']] = true;
                }
            } elseif ($ev['event_type'] === 'view_certified') {
                if (!empty($ev['request_id'])) {
                    if (!isset($currentSessionWindow['session_certified_request_ids'][$ev['request_id']])) {
                        $currentSessionWindow['session_certified_request_ids'][$ev['request_id']] = true;
                        $currentSessionWindow['interaction_timestamps'][] = $evTime;
                    }
                    if ($isInPeriod) {
                        $currentSessionWindow['period_certified_request_ids'][$ev['request_id']] = true;
                    }
                }
            } elseif (self::isEngagementInteraction($ev['event_type'])) {
                $currentSessionWindow['interaction_timestamps'][] = $evTime;
                if ($isInPeriod) {
                    $currentSessionWindow['business_events_count']++;
                    $currentSessionWindow['business_event_types'][$ev['event_type']] = ($currentSessionWindow['business_event_types'][$ev['event_type']] ?? 0) + 1;
                }
            } elseif (self::isRecognizedBusinessEvent($ev['event_type'])) {
                if ($isInPeriod) {
                    $currentSessionWindow['business_event_types'][$ev['event_type']] = ($currentSessionWindow['business_event_types'][$ev['event_type']] ?? 0) + 1;
                }
            }
        }

        if ($currentSessionWindow !== null) {
            $processCompletedSegment($currentSessionWindow);
        }

        // 5. Calculs des ratios & métriques consolidées
        $totalAdmissibleHttpPages = count($allPeriodHttpRequestIds);

        // Intersection stricte (Point 4) :
        // Le numérateur ne retient QUE les request_id certifiés qui correspondent effectivement à une pageview admissible de la période
        $validCertifiedRequestIds = array_intersect_key($allPeriodCertifiedRequestIds, $allPeriodHttpRequestIds);
        $totalValidCertifiedPages = count($validCertifiedRequestIds);
        $orphanCertificationsCount = count($allPeriodCertifiedRequestIds) - $totalValidCertifiedPages;

        $avgMeasuredDuration = $measurableSessionsCount > 0 ? (int)round($totalMeasuredDuration / $measurableSessionsCount) : 0;
        $strictBounceRate = $certifiedSessionsCount > 0 ? round(($strictBouncesCount / $certifiedSessionsCount) * 100, 1) : 0.0;
        $certificationRate = $totalAdmissibleHttpPages > 0 ? round(($totalValidCertifiedPages / $totalAdmissibleHttpPages) * 100, 1) : 0.0;

        arsort($landingPageCounts);
        arsort($exitPageCounts);

        $topLandingPages = [];
        foreach (array_slice($landingPageCounts, 0, 10, true) as $p => $c) {
            $topLandingPages[] = ['page' => $p, 'hits' => (int)$c];
        }

        $topExitPages = [];
        foreach (array_slice($exitPageCounts, 0, 10, true) as $p => $c) {
            $topExitPages[] = ['page' => $p, 'hits' => (int)$c];
        }

        return [
            'period' => [
                'start' => $dateStart,
                'end'   => $dateEnd,
            ],
            // Niveau 2 : Activité Navigateur
            'browser_activity' => [
                'admissible_http_pages'         => $totalAdmissibleHttpPages,
                'certified_pageviews'           => $totalValidCertifiedPages,
                'orphan_certifications_count'   => $orphanCertificationsCount,
                'certified_sessions'            => $certifiedSessionsCount,
                'unverified_http_sessions'      => $unverifiedHttpSessionsCount,
                'total_db_sessions'             => $totalSessionsCount,
                'certification_rate_pct'        => $certificationRate,
            ],
            // Niveau 3 : Engagement & Parcours
            'engagement' => [
                'total_certified_sessions'      => $certifiedSessionsCount,
                'strict_bounces'                => $strictBouncesCount,
                'strict_bounce_rate_pct'        => $strictBounceRate,
                'engaged_single_page_sessions'  => $engagedSinglePageCount,
                'multi_page_sessions'           => $multiPageCount,
                'business_events_count'         => $totalBusinessEvents,
            ],
            // Niveau 3b : Pages d'entrée et de sortie agrégées
            'journeys_aggregate' => [
                'top_landing_pages'             => $topLandingPages,
                'top_exit_pages'                => $topExitPages,
            ],
            // Niveau 4 : Durées (mesurées vs non mesurables)
            'duration' => [
                'measurable_sessions'           => $measurableSessionsCount,
                'unmeasurable_sessions'         => $unmeasurableSessionsCount,
                'total_measured_seconds'        => $totalMeasuredDuration,
                'avg_measured_seconds'          => $avgMeasuredDuration,
                'avg_measured_formatted'        => self::formatDurationStatic($avgMeasuredDuration),
                'notice'                        => 'Les sessions à interaction unique ne comportent pas de durée extrapolée artificielle.',
            ],
            // Niveau 5 : Visiteurs & Consentement
            'visitors' => [
                'consented_visitors_count'      => count($consentedVisitors),
                'anonymous_sessions_count'      => $anonymousSessionsCount,
                'unidentifiable_notice'         => 'Le nombre absolu de visiteurs physiques uniques est non déterminable sans consentement persistant (zéro fingerprinting intrusif conforme CNIL).',
            ],
            'sessions_sample' => $sessionsSample,
        ];
    }

    /**
     * Formatage lisible d'une durée en secondes
     */
    public static function formatDurationStatic(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        }
        $m = floor($seconds / 60);
        $s = $seconds % 60;
        if ($m < 60) {
            return sprintf('%dm %02ds', $m, $s);
        }
        $h = floor($m / 60);
        $m = $m % 60;
        return sprintf('%dh %02dm', $h, $m);
    }
}
