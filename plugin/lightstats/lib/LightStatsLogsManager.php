<?php

namespace LightStats\Lib;

use DateInterval;
use DatePeriod;
use Utils\Util;

defined('ROOT') or exit('No direct script access allowed');

class LightStatsLogsManager {

    public $dateStart;
    public $dateEnd;
    public $logs = [];
    public $uniquesVisitor = [];
    protected $path;
    
    public $datas = [];

    public function __construct($dateStart = false, $dateEnd = false) {
        $this->path = DATA_PLUGIN . 'lightstats/logs/';

        // Conversion tolérante de la date de début
        if (is_string($dateStart) && trim($dateStart) !== '') {
            try {
                $this->dateStart = new \DateTime(trim($dateStart));
            } catch (\Exception $e) {
                $this->dateStart = false;
            }
        } elseif ($dateStart instanceof \DateTimeInterface) {
            $this->dateStart = $dateStart;
        } else {
            $this->dateStart = false;
        }

        // Conversion tolérante de la date de fin
        if (is_string($dateEnd) && trim($dateEnd) !== '') {
            try {
                $this->dateEnd = new \DateTime(trim($dateEnd));
            } catch (\Exception $e) {
                $this->dateEnd = false;
            }
        } elseif ($dateEnd instanceof \DateTimeInterface) {
            $this->dateEnd = $dateEnd;
        } else {
            $this->dateEnd = false;
        }

        if ($this->dateStart instanceof \DateTimeInterface && $this->dateEnd instanceof \DateTimeInterface) {
            // Si la date de fin est antérieure ou égale à la date de début (ex: '2026-11-01' et '2026-11-01'),
            // DatePeriod('P1D') donnerait 0 jour sans au moins 1 jour d'amplitude.
            // Si $dateEnd a déjà été modifiée (comme dans LightStatsAdminController: $dateEnd->modify('+1 day'))
            // ou si des heures explicites couvrent déjà la journée ($dateEnd > $dateStart), aucun décalage n'est ajouté.
            if ($this->dateEnd <= $this->dateStart) {
                $this->dateEnd = (clone $this->dateEnd)->modify('+1 day');
            }
            $this->parseLogs();
        }
    }

    /**
     * Lit un fichier de logs au format historique (tableau JSON complet [ ])
     * ou au nouveau format (JSONL un objet par ligne { }).
     * Tolérant aux fichiers vides, lignes tronquées ou corrompues.
     *
     * @param string $file
     * @return array
     */
    public static function readLogFile(string $file): array {
        if (!is_file($file) || filesize($file) === 0) {
            return [];
        }

        $handle = @fopen($file, 'rb');
        if (!$handle) {
            return [];
        }

        // Détection du format par le premier caractère non-blanc
        $firstChar = '';
        while (($char = fgetc($handle)) !== false) {
            if (!ctype_space($char)) {
                $firstChar = $char;
                break;
            }
        }

        if ($firstChar === '[') {
            // Ancien format : tableau JSON complet
            fclose($handle);
            $content = @file_get_contents($file);
            $decoded = json_decode($content, true);
            return is_array($decoded) ? $decoded : [];
        }

        if ($firstChar === '{') {
            // Nouveau format : JSONL (1 objet par ligne)
            rewind($handle);
            $logs = [];
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if ($line === '') continue;
                $item = json_decode($line, true);
                if (is_array($item)) {
                    $logs[] = $item;
                }
                // Les lignes tronquées ou corrompues sont ignorées silencieusement
            }
            fclose($handle);
            return $logs;
        }

        fclose($handle);
        return [];
    }

    protected function parseLogs() {
        $period = new DatePeriod(
            $this->dateStart,
            new DateInterval('P1D'),
            $this->dateEnd
        );

        foreach ($period as $value) {
            $basePath = $this->path . $value->format('Y/m/d');
            $day = $value->format('d/m/Y');
            $dayVisitors = [];
            $this->datas[$day]['pages'] = 0;

            $jsonFile = $basePath . '.json';
            $jsonlFile = $basePath . '.jsonl';

            $dayLogs = [];
            if (is_file($jsonFile)) {
                $dayLogs = array_merge($dayLogs, self::readLogFile($jsonFile));
            }
            if (is_file($jsonlFile)) {
                $dayLogs = array_merge($dayLogs, self::readLogFile($jsonlFile));
            }

            if (empty($dayLogs)) {
                $this->datas[$day]['visitors'] = 0;
                continue;
            }

            // Tri chronologique strict garanti (Point 4)
            // Préserve l'ordre temporel même en cas d'agrégation .json + .jsonl
            usort($dayLogs, function($a, $b) {
                $dateA = $a['date'] ?? '1970-01-01 00:00:00';
                $dateB = $b['date'] ?? '1970-01-01 00:00:00';
                return strcmp($dateA, $dateB);
            });

            // Détection automatique des robots / aspirateurs (scrapers) par cadence :
            // Si une adresse IP dépasse 30 requêtes par minute sans exécuter de JavaScript,
            // elle est automatiquement identifiée et reclassée comme robot scraper (scraper_bot).
            $minuteCounts = [];
            $hasJsByIp = [];
            foreach ($dayLogs as $entry) {
                $ipKey = $entry['ipHash'] ?? ($entry['ip'] ?? '');
                if ($ipKey === '') {
                    continue;
                }
                if (!empty($entry['hasJs'])) {
                    $hasJsByIp[$ipKey] = true;
                }
                $dateVal = $entry['date'] ?? '1970-01-01 00:00:00';
                $minSlot = substr($dateVal, 0, 16);
                $minuteCounts[$ipKey][$minSlot] = ($minuteCounts[$ipKey][$minSlot] ?? 0) + 1;
            }

            $scraperIps = [];
            foreach ($minuteCounts as $ipKey => $mins) {
                if (empty($hasJsByIp[$ipKey])) {
                    foreach ($mins as $slot => $count) {
                        if ($count >= 30) {
                            $scraperIps[$ipKey] = true;
                            break;
                        }
                    }
                }
            }

            foreach ($dayLogs as $log) {
                $ipKey = $log['ipHash'] ?? ($log['ip'] ?? '');
                if (isset($scraperIps[$ipKey])) {
                    $log['isBot'] = true;
                    $log['botCategory'] = 'scraper_bot';
                }
                $this->logs[] = new LightStatsLog($log);
                $this->uniquesVisitor[$log['ip']] = 1;
                $this->datas[$day]['pages']++;
                $dayVisitors[$log['ip']] = 1;
            }
            $this->datas[$day]['visitors'] = count($dayVisitors);
        }
    }
    
    public function hasLogs() {
        return (!empty($this->logs));
    }
    
    public function getChartsDays() {
        $days = [];
        foreach ($this->datas as $k => $v) {
            $days[] = '"' . $k . '"';
        }
        return implode(',', $days);
    }
    
    /**
     * Vérifie si un log correspond à un hit public humain valide conforme aux normes AdSense / MRC :
     * - Statut HTTP 200 uniquement (les 404/500 ne sont jamais des vues publiques monétisables)
     * - Visiteur humain (exclusion de tous les bots et scanners)
     * - Non-administrateur
     * - Page publique réelle (exclusion d'admin, login, register, extensions sensibles scanniques)
     */
    public function isValidPublicHit(LightStatsLog $log): bool
    {
        // 1. Doit être un humain
        if ($log->isBot) {
            return false;
        }

        // 2. Statut HTTP : 200 uniquement (si présent). Si absent (logs anciens), autoriser uniquement si l'URL n'est pas suspecte
        if ($log->status !== null && $log->status !== 200) {
            return false;
        }

        // 3. Exclure les requêtes d'attaque / sondage de fichiers
        if ($this->isSuspiciousRequest($log->page ?? '', (string)($log->query ?? ''))) {
            return false;
        }

        // 4. Exclure les sessions de l'administrateur
        if ($log->isAdmin) {
            return false;
        }

        // 5. Exclure les pages admin, système ou de test
        if ($this->isExcludedPage($log->page ?? '/')) {
            return false;
        }

        // 6. Exclure les référents admin ou de test
        if ($this->isExcludedReferer($log->referer ?? '')) {
            return false;
        }

        return true;
    }

    /**
     * Détection des attaques et scans de vulnérabilités (extensions et fichiers sensibles)
     */
    public function isSuspiciousRequest(string $page, string $query = ''): bool
    {
        $uri = strtolower($page . ($query !== '' ? '?' . $query : ''));
        return (bool)preg_match('/\.(sql|sql\.gz|bak|backup|old|save|swp|temp|tmp|env|yml|yaml|ini|conf|config|cfg|sh|bash|key|pem|crt|cer|pfx|p12|id_rsa|log|tar|tar\.gz|tgz|zip|rar|7z)(\?|$)/i', $uri)
            || (bool)preg_match('/(wp-config|phpinfo|web\.xml|settings\.py|\.aws\/|bedrock|phpmyadmin|adminer)/i', $uri);
    }

    public function getChartsPages($viewMode = 'public') {
        // Si mode public, calculer uniquement les pages humaines certifiées 200 OK
        if ($viewMode === 'public') {
            $humanPagesByDay = [];
            foreach ($this->datas as $day => $data) {
                $humanPagesByDay[$day] = 0;
            }
            
            foreach ($this->logs as $log) {
                if ($this->isValidPublicHit($log)) {
                    $date = $log->date ?? date('Y-m-d H:i:s');
                    $day = date('d/m/Y', strtotime($date));
                    if (isset($humanPagesByDay[$day])) {
                        $humanPagesByDay[$day]++;
                    }
                }
            }
            
            $pages = [];
            foreach ($this->datas as $day => $v) {
                $pages[] = $humanPagesByDay[$day] ?? 0;
            }
            return implode(',', $pages);
        }
        
        // Mode technique ou raw : toutes les pages
        $pages = [];
        foreach ($this->datas as $v) {
            $pages[] = $v['pages'];
        }
        return implode(',', $pages);
    }
    
    public function getChartsVisitors($viewMode = 'public') {
        // Si mode public, calculer uniquement les visiteurs humains sur pages valides
        if ($viewMode === 'public') {
            $humanVisitorsByDay = [];
            foreach ($this->datas as $day => $data) {
                $humanVisitorsByDay[$day] = [];
            }
            
            foreach ($this->logs as $log) {
                if ($this->isValidPublicHit($log)) {
                    $date = $log->date ?? date('Y-m-d H:i:s');
                    $day = date('d/m/Y', strtotime($date));
                    $identifier = $log->ipHash ?? ($log->ip ?? 'unknown');
                    if (isset($humanVisitorsByDay[$day])) {
                        $humanVisitorsByDay[$day][$identifier] = true;
                    }
                }
            }
            
            $visitors = [];
            foreach ($this->datas as $day => $v) {
                $visitors[] = count($humanVisitorsByDay[$day] ?? []);
            }
            return implode(',', $visitors);
        }
        
        // Mode technique ou raw : tous les visiteurs
        $visitors = [];
        foreach ($this->datas as $v) {
            $visitors[] = $v['visitors'];
        }
        return implode(',', $visitors);
    }
    
    /**
     * Vérifie si une page doit être exclue des stats publiques
     */
    private function isExcludedPage(string $page): bool
    {
        // Exclure les pages admin
        if (strpos($page, '/admin/') === 0 || $page === '/admin') {
            return true;
        }
        
        // Exclure les pages utilisateurs système (login, register, profile, logout, etc.)
        if (preg_match('#^/(users|user)(/|$)#i', $page)) {
            return true;
        }
        
        // Exclure les environnements et routes de test
        if (preg_match('#(\.test|\.local|/test(/|$)|/tests(/|$))#i', $page)) {
            return true;
        }

        // Exclure les sondages de fichiers techniques
        if ($this->isSuspiciousRequest($page)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Vérifie si un référent doit être exclu des stats publiques
     */
    private function isExcludedReferer(string $referer): bool
    {
        // Exclure les référents admin
        if (strpos($referer, '/admin/') !== false) {
            return true;
        }
        
        // Exclure les environnements de test
        if (strpos($referer, '.test') !== false || strpos($referer, '.local') !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Calcule les sessions à partir des logs (timeout 30 minutes conforme GA / AdSense)
     */
    private function calculateSessions(array $humanLogs): array
    {
        $sessions = [];
        $sessionTimeout = 30 * 60; // 30 minutes en secondes
        
        // Trier les logs par date
        usort($humanLogs, function($a, $b) {
            $dateA = strtotime($a->date ?? '1970-01-01 00:00:00');
            $dateB = strtotime($b->date ?? '1970-01-01 00:00:00');
            return $dateA <=> $dateB;
        });
        
        foreach ($humanLogs as $log) {
            // Préférer le sessionId généré par le navigateur (privacy-tracker.js) s'il existe
            if (!empty($log->sessionId)) {
                $sessionKey = 'sid_' . $log->sessionId;
            } else {
                $identifier = $log->ipHash ?? ($log->ip ?? 'unknown');
                $sessionKey = $identifier . '|' . ($log->browser ?? 'unknown') . '|' . ($log->platform ?? 'unknown');
            }
            $logTime = strtotime($log->date ?? '1970-01-01 00:00:00');
            
            // Chercher une session existante pour cette clé
            $sessionFound = false;
            foreach ($sessions as &$session) {
                if ($session['key'] === $sessionKey) {
                    $lastActivity = strtotime($session['last_activity']);
                    // Si moins de 30 minutes depuis la dernière activité, continuer la session
                    if (($logTime - $lastActivity) <= $sessionTimeout) {
                        $session['pages'][] = $log->page ?? '/';
                        $session['last_activity'] = $log->date;
                        $session['end_time'] = $log->date;
                        $session['page_count']++;
                        $sessionFound = true;
                        break;
                    }
                }
            }
            unset($session);
            
            // Si aucune session trouvée, créer une nouvelle session
            if (!$sessionFound) {
                $sessions[] = [
                    'key' => $sessionKey,
                    'ip' => $log->ipHash ?? ($log->ip ?? 'unknown'),
                    'start_time' => $log->date,
                    'last_activity' => $log->date,
                    'end_time' => $log->date,
                    'pages' => [$log->page ?? '/'],
                    'page_count' => 1,
                    'referer' => $log->referer ?? ''
                ];
            }
        }
        
        return $sessions;
    }
    
    /**
     * Indicateurs d'Activité HTTP (Niveau 1 de la refonte statistique)
     * Deux dimensions rigoureusement orthogonales :
     * 1. Catégorie de trafic (public admissible, robots, scanners, admin)
     * 2. Statut HTTP (2xx succès, 3xx redirections, 4xx erreurs client, 5xx erreurs serveur)
     */
    public function getHttpActivityKpis(): array {
        $totalRequests = count($this->logs);

        // Dimension 1 : Catégories de trafic
        $adminRequests = 0;
        $scannersSuspicious = 0;
        $identifiedBots = 0;
        $publicTraffic = 0;
        $admissiblePublicPages = 0;

        // Dimension 2 : Statuts HTTP (Codes de réponse)
        $status2xx = 0;
        $status3xx = 0; // Redirections (non assimilées à des erreurs)
        $status4xx = 0; // Erreurs client (404, 403, 400)
        $status5xx = 0; // Erreurs serveur (500, 502, 503)

        // Croisement : Erreurs par catégorie de trafic
        $publicErrors = 0;
        $scannerErrors = 0;
        $botErrors = 0;

        foreach ($this->logs as $log) {
            $page = $log->page ?? '/';
            $referer = $log->referer ?? '';
            $status = (int)($log->status ?? 200);
            $method = (isset($log->method) && is_string($log->method) && !empty($log->method))
                ? strtoupper($log->method)
                : 'UNKNOWN';

            // --- Qualification de la catégorie de trafic ---
            $isLogAdmin = $log->isAdmin || strpos($page, '/admin/') === 0 || $page === '/admin' || strpos($referer, '/admin/') !== false;
            $isLogScanner = $this->isSuspiciousRequest($page, (string)($log->query ?? ''));
            $isLogBot = (bool)($log->isBot ?? false);

            if ($isLogAdmin) {
                $adminRequests++;
            } elseif ($isLogScanner) {
                $scannersSuspicious++;
            } elseif ($isLogBot) {
                $identifiedBots++;
            } else {
                $publicTraffic++;
                // Page publique admissible : GET, 200 OK, routes publiques non techniques
                if ($method === 'GET' && $status === 200 && !$this->isExcludedPage($page) && !$this->isExcludedReferer($referer)) {
                    $admissiblePublicPages++;
                }
            }

            // --- Qualification du statut HTTP (indépendante de la catégorie) ---
            if ($status >= 200 && $status < 300) {
                $status2xx++;
            } elseif ($status >= 300 && $status < 400) {
                $status3xx++;
            } elseif ($status >= 400 && $status < 500) {
                $status4xx++;
                // Croisement d'erreur
                if ($isLogScanner) {
                    $scannerErrors++;
                } elseif ($isLogBot) {
                    $botErrors++;
                } elseif (!$isLogAdmin) {
                    $publicErrors++;
                }
            } elseif ($status >= 500 && $status < 600) {
                $status5xx++;
                if ($isLogScanner) {
                    $scannerErrors++;
                } elseif ($isLogBot) {
                    $botErrors++;
                } elseif (!$isLogAdmin) {
                    $publicErrors++;
                }
            }
        }

        $totalErrors = $status4xx + $status5xx;

        return [
            'total_http_requests'       => $totalRequests,
            // Catégories de trafic
            'admissible_public_pages'   => $admissiblePublicPages,
            'public_traffic'            => $publicTraffic,
            'identified_bots'           => $identifiedBots,
            'scanners_suspicious'       => $scannersSuspicious,
            'admin_requests'            => $adminRequests,
            // Statuts HTTP orthogonaux
            'status_2xx'                => $status2xx,
            'status_3xx'                => $status3xx, // Redirections isolées
            'status_4xx'                => $status4xx,
            'status_5xx'                => $status5xx,
            'total_http_errors'         => $totalErrors,
            // Détail des erreurs croisées
            'public_errors'             => $publicErrors,
            'scanner_errors'            => $scannerErrors,
            'bot_errors'                => $botErrors,
        ];
    }

    /**
     * Calculer les KPI supplémentaires avec séparation publique/technique
     */
    public function getKpis($viewMode = 'public') {
        // Séparer rigoureusement les catégories de requêtes
        $humanLogs = [];
        $botLogs = [];
        $adminLogs = [];
        $testLogs = [];
        $errorLogs = [];
        
        foreach ($this->logs as $log) {
            $page = $log->page ?? '/';
            $referer = $log->referer ?? '';
            
            if ($log->isAdmin || strpos($page, '/admin/') === 0 || $page === '/admin' || strpos($referer, '/admin/') !== false) {
                $adminLogs[] = $log;
            } elseif ($this->isExcludedReferer($referer) || strpos($page, '.test') !== false || strpos($page, '.local') !== false) {
                $testLogs[] = $log;
            } elseif ($log->isBot || $this->isSuspiciousRequest($page, (string)($log->query ?? ''))) {
                $botLogs[] = $log;
            } elseif ($log->status !== null && $log->status !== 200) {
                $errorLogs[] = $log;
            } elseif ($this->isExcludedPage($page)) {
                $testLogs[] = $log;
            } else {
                $humanLogs[] = $log;
            }
        }
        
        // Calculer les sessions pour les vrais logs humains
        $sessions = $this->calculateSessions($humanLogs);
        
        // Calculer les métriques de base
        $totalLogs = count($this->logs);
        $totalHumanLogs = count($humanLogs);
        $totalBotLogs = count($botLogs);
        $totalAdminLogs = count($adminLogs);
        $totalTestLogs = count($testLogs);
        $totalErrorLogs = count($errorLogs);
        
        // Décompte multi-jours rigoureux :
        // L'IP anonymisée (ex: 92.142.53.0) reste stable sur plusieurs jours et n'est pas altérée par le sel journalier
        $uniqueVisitorsEstimated = [];
        $uniqueVisitorsJsVerified = [];
        $jsVerifiedPagesCount = 0;
        
        foreach ($humanLogs as $log) {
            $visitorKey = $log->ip ?? 'unknown';
            $uniqueVisitorsEstimated[$visitorKey] = true;
            if ($log->hasJs || !empty($log->sessionId)) {
                $uniqueVisitorsJsVerified[$visitorKey] = true;
                $jsVerifiedPagesCount++;
            }
        }
        $uniqueHumanVisitorsCount = count($uniqueVisitorsEstimated);
        $uniqueJsVerifiedCount = count($uniqueVisitorsJsVerified);
        
        // Sessions
        $totalSessions = count($sessions);
        
        // Pages vues humaines
        $totalHumanPages = $totalHumanLogs;
        
        // Pages par session
        $totalPagesInSessions = 0;
        foreach ($sessions as $session) {
            $totalPagesInSessions += $session['page_count'];
        }
        $avgPagesPerSession = $totalSessions > 0 ? round($totalPagesInSessions / $totalSessions, 1) : 0;
        
        // Durée moyenne de session
        $totalSessionDuration = 0;
        $sessionsWithDuration = 0;
        foreach ($sessions as $session) {
            $start = strtotime($session['start_time']);
            $end = strtotime($session['end_time']);
            if ($end > $start) {
                $totalSessionDuration += ($end - $start);
                $sessionsWithDuration++;
            }
        }
        $avgSessionDuration = $sessionsWithDuration > 0 ? round($totalSessionDuration / $sessionsWithDuration) : 0;
        $avgSessionDurationFormatted = $this->formatDuration($avgSessionDuration);
        
        // Taux de rebond (sessions avec une seule page vue, humains uniquement)
        $bounceSessions = 0;
        foreach ($sessions as $session) {
            if ($session['page_count'] === 1) {
                $bounceSessions++;
            }
        }
        $bounceRate = $totalSessions > 0 ? round(($bounceSessions / $totalSessions) * 100, 1) : 0;
        
        // Nouveaux vs récurrents (basé sur IP ou hash)
        $firstVisitIps = [];
        $recurrentIps = [];
        foreach ($sessions as $session) {
            $ip = $session['ip'];
            if (!isset($firstVisitIps[$ip])) {
                $firstVisitIps[$ip] = true;
            } else {
                $recurrentIps[$ip] = true;
            }
        }
        $newVisitors = count($firstVisitIps);
        $recurrentVisitors = count($recurrentIps);
        
        // Moyenne par jour (pages vues humaines)
        $daysCount = count($this->datas);
        $avgPagesPerDay = $daysCount > 0 ? round($totalHumanPages / $daysCount, 1) : 0;
        $avgSessionsPerDay = $daysCount > 0 ? round($totalSessions / $daysCount, 1) : 0;
        
        // Pages populaires (publiques uniquement, humains uniquement)
        $pagesCount = [];
        $referersCount = [];
        $browsersCount = [];
        $platformsCount = [];
        $hourlyCount = [];
        
        foreach ($humanLogs as $log) {
            $page = $log->page ?? '/';
            $pagesCount[$page] = ($pagesCount[$page] ?? 0) + 1;
            
            // Compter les référents (exclure admin et test)
            $referer = $log->referer ?? '';
            if (!empty($referer)) {
                $referersCount[$referer] = ($referersCount[$referer] ?? 0) + 1;
            } else {
                $referersCount[''] = ($referersCount[''] ?? 0) + 1;
            }
            
            // Compter les navigateurs
            $browser = $log->browser ?? 'Unknown';
            $browsersCount[$browser] = ($browsersCount[$browser] ?? 0) + 1;
            
            // Compter les OS
            $platform = $log->platform ?? 'Unknown';
            $platformsCount[$platform] = ($platformsCount[$platform] ?? 0) + 1;
            
            // Distribution horaire
            $date = $log->date ?? date('Y-m-d H:i:s');
            $hour = date('H', strtotime($date));
            $hourlyCount[$hour] = ($hourlyCount[$hour] ?? 0) + 1;
        }
        
        // Top 10 pages
        arsort($pagesCount);
        $topPages = array_slice($pagesCount, 0, 10, true);
        
        // Top 10 référents
        arsort($referersCount);
        $topReferers = array_slice($referersCount, 0, 10, true);
        
        // Top navigateurs
        arsort($browsersCount);
        $topBrowsers = array_slice($browsersCount, 0, 10, true);
        
        // Top OS
        arsort($platformsCount);
        $topPlatforms = array_slice($platformsCount, 0, 10, true);
        
        // Distribution horaire (trier par heure)
        ksort($hourlyCount);
        $hourlyDistribution = $hourlyCount;
        
        // Construire le tableau de KPIs
        $kpis = [
            // Triptyque de Mesure de Qualité (Indicateurs Internes)
            'http_hits' => $totalLogs,
            'js_verified_pages' => $jsVerifiedPagesCount,
            'human_estimated_pages' => $totalHumanPages,
            'unique_visitors_js' => $uniqueJsVerifiedCount,

            // Métriques publiques (humaines uniquement)
            'unique_visitors' => $uniqueHumanVisitorsCount,
            'total_sessions' => $totalSessions,
            'total_pages' => $totalHumanPages,
            'pages_per_session' => $avgPagesPerSession,
            'avg_session_duration' => $avgSessionDuration,
            'avg_session_duration_formatted' => $avgSessionDurationFormatted,
            'bounce_rate' => $bounceRate,
            'new_visitors' => $newVisitors,
            'recurrent_visitors' => $recurrentVisitors,
            'avg_pages_per_day' => $avgPagesPerDay,
            'avg_sessions_per_day' => $avgSessionsPerDay,
            
            // Métriques techniques
            'bot_visits' => $totalBotLogs,
            'human_visits' => $totalHumanLogs,
            'admin_visits' => $totalAdminLogs,
            'test_visits' => $totalTestLogs,
            'error_visits' => $totalErrorLogs,
            'total_visits' => $totalLogs,
            
            // Top lists
            'top_pages' => $topPages,
            'top_referers' => $topReferers,
            'top_browsers' => $topBrowsers,
            'top_platforms' => $topPlatforms,
            'hourly_distribution' => $hourlyDistribution,
        ];
        
        return $kpis;
    }
    
    /**
     * Formate une durée en secondes en format lisible (ex: "3 min 12 s")
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' s';
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($remainingSeconds === 0) {
            return $minutes . ' min';
        }
        
        return $minutes . ' min ' . $remainingSeconds . ' s';
    }

    /**
     * Calcule la répartition horaire (24h de 00h à 23h) des visiteurs uniques et pages vues
     */
    public function getHourlyStats(string $viewMode = 'public'): array
    {
        $hourlyPages = array_fill(0, 24, 0);
        $hourlyVisitors = [];
        for ($i = 0; $i < 24; $i++) {
            $hourlyVisitors[$i] = [];
        }

        foreach ($this->logs as $log) {
            if ($viewMode === 'public' && !$this->isValidPublicHit($log)) {
                continue;
            }

            $date = $log->date ?? null;
            if (!$date) continue;

            $hour = (int)date('H', strtotime($date));
            if ($hour >= 0 && $hour < 24) {
                $hourlyPages[$hour]++;
                $identifier = $log->ipHash ?? ($log->ip ?? 'unknown');
                $hourlyVisitors[$hour][$identifier] = true;
            }
        }

        $labels = [];
        $visitorsData = [];
        $pagesData = [];

        for ($h = 0; $h < 24; $h++) {
            $labels[] = sprintf('"%02dh"', $h);
            $visitorsData[] = count($hourlyVisitors[$h]);
            $pagesData[] = $hourlyPages[$h];
        }

        return [
            'labels_js' => implode(',', $labels),
            'visitors_js' => implode(',', $visitorsData),
            'pages_js' => implode(',', $pagesData),
        ];
    }
}
