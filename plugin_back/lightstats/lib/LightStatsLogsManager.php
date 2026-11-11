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
        $this->dateStart = $dateStart;
        $this->dateEnd = $dateEnd;
        $this->path = DATA_PLUGIN . 'lightstats/logs/';
        $this->parseLogs();
    }

    protected function parseLogs() {
        $period = new DatePeriod(
                $this->dateStart,
                new DateInterval('P1D'),
                $this->dateEnd
        );

        foreach ($period as $value) {
            $file = $this->path . $value->format('Y/m/d') . '.json';
            $day = $value->format('d/m/Y');
            $dayVisitors = [];
            $this->datas[$day]['pages'] = 0;
            $logs = is_file($file) ? Util::readJsonFile($file) : false;
            if ($logs === false) {
                $this->datas[$day]['visitors'] = 0;
                continue;
            }
            foreach ($logs as $k => $log) {
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
    
    public function getChartsPages($viewMode = 'public') {
        // Si mode public, calculer uniquement les pages humaines
        if ($viewMode === 'public') {
            $humanPagesByDay = [];
            foreach ($this->datas as $day => $data) {
                $humanPagesByDay[$day] = 0;
            }
            
            foreach ($this->logs as $log) {
                if (!($log->isBot ?? false)) {
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
        // Si mode public, calculer uniquement les visiteurs humains
        if ($viewMode === 'public') {
            $humanVisitorsByDay = [];
            foreach ($this->datas as $day => $data) {
                $humanVisitorsByDay[$day] = [];
            }
            
            foreach ($this->logs as $log) {
                if (!($log->isBot ?? false)) {
                    $date = $log->date ?? date('Y-m-d H:i:s');
                    $day = date('d/m/Y', strtotime($date));
                    $ip = $log->ip ?? 'unknown';
                    if (isset($humanVisitorsByDay[$day])) {
                        $humanVisitorsByDay[$day][$ip] = true;
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
        if (strpos($page, '/admin/') === 0) {
            return true;
        }
        
        // Exclure les pages utilisateurs (login, register, profile)
        if (preg_match('#^/(users|user)/(login|register|profile|logout)#', $page)) {
            return true;
        }
        
        // Exclure les environnements de test
        if (strpos($page, '.test') !== false || strpos($page, '.local') !== false) {
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
     * Calcule les sessions à partir des logs (timeout 30 minutes)
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
            $sessionKey = ($log->ip ?? 'unknown') . '|' . ($log->browser ?? 'unknown') . '|' . ($log->platform ?? 'unknown');
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
                    'ip' => $log->ip ?? 'unknown',
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
     * Calculer les KPI supplémentaires avec séparation publique/technique
     */
    public function getKpis($viewMode = 'public') {
        // Séparer les logs humains et bots
        $humanLogs = [];
        $botLogs = [];
        $adminLogs = [];
        $testLogs = [];
        
        foreach ($this->logs as $log) {
            $page = $log->page ?? '/';
            $referer = $log->referer ?? '';
            
            // Identifier le type de log
            if ($this->isExcludedPage($page) || $this->isExcludedReferer($referer)) {
                if (strpos($page, '/admin/') === 0 || strpos($referer, '/admin/') !== false) {
                    $adminLogs[] = $log;
                } else {
                    $testLogs[] = $log;
                }
            } elseif ($log->isBot ?? false) {
                $botLogs[] = $log;
            } else {
                $humanLogs[] = $log;
            }
        }
        
        // Calculer les sessions pour les logs humains
        $sessions = $this->calculateSessions($humanLogs);
        
        // Calculer les métriques de base
        $totalLogs = count($this->logs);
        $totalHumanLogs = count($humanLogs);
        $totalBotLogs = count($botLogs);
        $totalAdminLogs = count($adminLogs);
        $totalTestLogs = count($testLogs);
        
        // Visiteurs uniques (humains uniquement, basé sur IP)
        $uniqueHumanVisitors = [];
        foreach ($humanLogs as $log) {
            $ip = $log->ip ?? 'unknown';
            $uniqueHumanVisitors[$ip] = true;
        }
        $uniqueHumanVisitorsCount = count($uniqueHumanVisitors);
        
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
        
        // Nouveaux vs récurrents (basé sur IP)
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
            // Exclure les pages admin des stats publiques
            if ($viewMode === 'public' && $this->isExcludedPage($page)) {
                continue;
            }
            $pagesCount[$page] = ($pagesCount[$page] ?? 0) + 1;
            
            // Compter les référents (exclure admin et test)
            $referer = $log->referer ?? '';
            if ($viewMode === 'public' && $this->isExcludedReferer($referer)) {
                continue;
            }
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

}
