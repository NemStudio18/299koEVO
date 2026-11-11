<?php

namespace LightStats\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use LightStats\Lib\LightStatsLogsManager;
use LightStats\Lib\LightStatsLog;
use Utils\Util;

defined('ROOT') or exit('No direct script access allowed');

class LightStatsAdminController extends AdminController
{
    public function home() {
        require_once PLUGINS . 'lightstats/lib/UserAgent/UserAgentInterface.php';
        require_once PLUGINS . 'lightstats/lib/UserAgent/Browsers.php';
        require_once PLUGINS . 'lightstats/lib/UserAgent/Platforms.php';
        require_once PLUGINS . 'lightstats/lib/UserAgent/UserAgent.php';
        require_once PLUGINS . 'lightstats/lib/UserAgent/UserAgentParser.php';
        require_once PLUGINS . 'lightstats/lib/UserAgentParser.php';

        require_once PLUGINS . 'lightstats/lib/LightStatsLogsManager.php';
        require_once PLUGINS . 'lightstats/lib/LightStatsLog.php';

        // Onglet actif mémorisé (public/global, journeys, technical/security, raw/logs)
        $tabAliases = [
            'global'    => 'public',
            'security'  => 'technical',
            'logs'      => 'raw',
        ];
        $requestedTab = $_GET['tab'] ?? $_POST['tab'] ?? 'public';
        $activeTab = $tabAliases[$requestedTab] ?? $requestedTab;
        if (!in_array($activeTab, ['public', 'journeys', 'technical', 'raw'], true)) {
            $activeTab = 'public';
        }

        // Récupérer les dates depuis POST ou GET (par défaut : les 7 derniers jours pour toujours avoir une vue active)
        $dateStartInput = $_POST['dateStart'] ?? $_GET['dateStart'] ?? date('Y-m-d', strtotime('-6 days'));
        $dateEndInput = $_POST['dateEnd'] ?? $_GET['dateEnd'] ?? date('Y-m-d');
        $dateStart = new \DateTime($dateStartInput);
        $dateEnd = new \DateTime($dateEndInput);

        $browserDateStart = $dateStart->format('Y-m-d');
        $browserDateEnd = $dateEnd->format('Y-m-d');

        // Détection du preset actif pour le style des boutons rapides
        $todayStr = date('Y-m-d');
        $yesterdayStr = date('Y-m-d', strtotime('-1 day'));
        $sevenDaysStr = date('Y-m-d', strtotime('-6 days'));
        $thirtyDaysStr = date('Y-m-d', strtotime('-29 days'));
        $monthStartStr = date('Y-m-01');

        $activePreset = 'custom';
        if ($browserDateStart === $todayStr && $browserDateEnd === $todayStr) {
            $activePreset = 'today';
        } elseif ($browserDateStart === $yesterdayStr && $browserDateEnd === $yesterdayStr) {
            $activePreset = 'yesterday';
        } elseif ($browserDateStart === $sevenDaysStr && $browserDateEnd === $todayStr) {
            $activePreset = '7days';
        } elseif ($browserDateStart === $thirtyDaysStr && $browserDateEnd === $todayStr) {
            $activePreset = '30days';
        } elseif ($browserDateStart === $monthStartStr && $browserDateEnd === $todayStr) {
            $activePreset = 'month';
        }

        $inDateStart = Util::getDate($dateStart->getTimestamp());
        $inDateEnd = Util::getDate($dateEnd->getTimestamp());

        $dateEnd->modify('+1 day');

        $logsManager = new LightStatsLogsManager($dateStart, $dateEnd);
        $logs = $logsManager->logs;
        $uniqueVisitor = $logsManager->uniquesVisitor;

        // Calculer les KPI supplémentaires
        $kpis = $logsManager->getKpis('public');
        
        // Données graphiques
        $chartVisitors = $logsManager->getChartsVisitors('public') ?: '';
        $chartPages = $logsManager->getChartsPages('public') ?: '';
        $chartDays = $logsManager->getChartsDays() ?: '';
        
        $isSingleDay = ($browserDateStart === $browserDateEnd);
        $hourlyStats = $logsManager->getHourlyStats('public');
        $hourlyChartLabels = $hourlyStats['labels_js'];
        $hourlyChartVisitors = $hourlyStats['visitors_js'];
        $hourlyChartPages = $hourlyStats['pages_js'];
        
        // Préparer les données pour les graphiques JavaScript
        $browsersLabels = [];
        $browsersData = [];
        foreach ($kpis['top_browsers'] ?? [] as $browser => $count) {
            $browsersLabels[] = "'" . addslashes($browser) . "'";
            $browsersData[] = $count;
        }
        $kpis['browsers_labels_js'] = !empty($browsersLabels) ? implode(',', $browsersLabels) : '';
        $kpis['browsers_data_js'] = !empty($browsersData) ? implode(',', $browsersData) : '';
        
        $platformsLabels = [];
        $platformsData = [];
        foreach ($kpis['top_platforms'] ?? [] as $platform => $count) {
            $platformsLabels[] = "'" . addslashes($platform) . "'";
            $platformsData[] = $count;
        }
        $kpis['platforms_labels_js'] = !empty($platformsLabels) ? implode(',', $platformsLabels) : '';
        $kpis['platforms_data_js'] = !empty($platformsData) ? implode(',', $platformsData) : '';
        
        $hoursLabels = [];
        $hoursData = [];
        foreach ($kpis['hourly_distribution'] ?? [] as $hour => $count) {
            $hoursLabels[] = "'" . $hour . "h'";
            $hoursData[] = $count;
        }
        $kpis['hours_labels_js'] = !empty($hoursLabels) ? implode(',', $hoursLabels) : '';
        $kpis['hours_data_js'] = !empty($hoursData) ? implode(',', $hoursData) : '';
        
        // Normaliser les référents
        if (isset($kpis['top_referers']) && is_array($kpis['top_referers'])) {
            $normalizedReferers = [];
            foreach ($kpis['top_referers'] as $referer => $count) {
                if ($referer === '') {
                    $normalizedReferers['Direct'] = [
                        'label' => 'Direct',
                        'url' => '',
                        'is_direct' => true,
                        'count' => $count,
                        'hits' => $count
                    ];
                } else {
                    $normalizedReferers[$referer] = [
                        'label' => $referer,
                        'url' => $referer,
                        'is_direct' => false,
                        'count' => $count,
                        'hits' => $count
                    ];
                }
            }
            $kpis['top_referers'] = $normalizedReferers;
        }
        
        // Pagination
        $itemsPerPage = 50;
        $currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $totalItems = count($logs);
        $totalPages = max(1, (int)ceil($totalItems / $itemsPerPage));
        $offset = ($currentPage - 1) * $itemsPerPage;
        $paginatedLogs = array_slice($logs, $offset, $itemsPerPage);
        
        $paginationStart = $totalItems > 0 ? $offset + 1 : 0;
        $paginationEnd = min($offset + $itemsPerPage, $totalItems);
        
        // URL de base
        try {
            $baseUrl = $this->router->generate('admin-lightstats-home');
        } catch (\Exception $e) {
            $baseUrl = '/admin/lightstats';
        }
        
        // URLs complètes avec conservation de l'onglet actif et des dates
        $makeUrl = function(int $page) use ($baseUrl, $browserDateStart, $browserDateEnd, $activeTab): string {
            return $baseUrl . '?' . http_build_query([
                'page' => $page,
                'dateStart' => $browserDateStart,
                'dateEnd' => $browserDateEnd,
                'tab' => $activeTab
            ]);
        };
        
        $prevPageUrl = $currentPage > 1 ? $makeUrl($currentPage - 1) : null;
        $nextPageUrl = $currentPage < $totalPages ? $makeUrl($currentPage + 1) : null;
        
        // Fenêtrage compact de pagination (ex: 1 ... 4 5 [6] 7 8 ... 45)
        $paginationElements = [];
        if ($totalPages <= 9) {
            for ($i = 1; $i <= $totalPages; $i++) {
                $paginationElements[] = [
                    'type' => 'page',
                    'num' => $i,
                    'isCurrent' => ($i === $currentPage),
                    'url' => $makeUrl($i)
                ];
            }
        } else {
            $paginationElements[] = [
                'type' => 'page',
                'num' => 1,
                'isCurrent' => (1 === $currentPage),
                'url' => $makeUrl(1)
            ];
            
            $start = max(2, $currentPage - 2);
            $end = min($totalPages - 1, $currentPage + 2);
            
            if ($start > 2) {
                $paginationElements[] = ['type' => 'ellipsis'];
            }
            
            for ($i = $start; $i <= $end; $i++) {
                $paginationElements[] = [
                    'type' => 'page',
                    'num' => $i,
                    'isCurrent' => ($i === $currentPage),
                    'url' => $makeUrl($i)
                ];
            }
            
            if ($end < $totalPages - 1) {
                $paginationElements[] = ['type' => 'ellipsis'];
            }
            
            $paginationElements[] = [
                'type' => 'page',
                'num' => $totalPages,
                'isCurrent' => ($totalPages === $currentPage),
                'url' => $makeUrl($totalPages)
            ];
        }

        // 6. Analytics consolidés & réconciliés
        require_once PLUGINS . 'lightstats/lib/LightStatsAnalyticsService.php';
        $analytics = \LightStats\Lib\LightStatsAnalyticsService::getInstance();

        $periodDays = max(1, (int)round((strtotime($browserDateEnd) - strtotime($browserDateStart)) / 86400) + 1);

        $consolidated = $analytics->getConsolidatedSessionStats($browserDateStart, $browserDateEnd);
        $journeys = $analytics->getRecentJourneys(40);

        // Activité HTTP orthogonale pour Onglet Crawlers & Sécurité
        $httpKpis = $logsManager->getHttpActivityKpis();

        // Calculs des KPIs avec gestion stricte de la division par zéro
        $totalDbSessions = (int)($consolidated['browser_activity']['total_db_sessions'] ?? 0);
        $certifiedSessions = (int)($consolidated['browser_activity']['certified_sessions'] ?? 0);
        $certifiedPageviews = (int)($consolidated['browser_activity']['certified_pageviews'] ?? 0);
        $admissibleHttpPages = (int)($consolidated['browser_activity']['admissible_http_pages'] ?? 0);
        $unverifiedSessions = (int)($consolidated['browser_activity']['unverified_http_sessions'] ?? 0);

        $multiPageSessions = (int)($consolidated['engagement']['multi_page_sessions'] ?? 0);
        $engagedSinglePageSessions = (int)($consolidated['engagement']['engaged_single_page_sessions'] ?? 0);
        $strictBounces = (int)($consolidated['engagement']['strict_bounces'] ?? 0);

        // Taux d'engagement qualifié sur sessions certifiées : (multi-pages + monopages engagées) / sessions certifiées * 100
        $engagedQualifiedCount = $multiPageSessions + $engagedSinglePageSessions;
        $engagementRatePct = $certifiedSessions > 0 ? round(($engagedQualifiedCount / $certifiedSessions) * 100, 1) : 0.0;

        // Répartition des sessions en pourcentages sur le total des sessions observées
        $multiPagePct = $totalDbSessions > 0 ? round(($multiPageSessions / $totalDbSessions) * 100, 1) : 0.0;
        $engagedSinglePagePct = $totalDbSessions > 0 ? round(($engagedSinglePageSessions / $totalDbSessions) * 100, 1) : 0.0;
        $strictBouncePct = $totalDbSessions > 0 ? round(($strictBounces / $totalDbSessions) * 100, 1) : 0.0;
        $unverifiedPct = $totalDbSessions > 0 ? round(($unverifiedSessions / $totalDbSessions) * 100, 1) : 0.0;

        $sessionBreakdown = [
            'multi_page' => ['count' => $multiPageSessions, 'pct' => $multiPagePct],
            'engaged_single_page' => ['count' => $engagedSinglePageSessions, 'pct' => $engagedSinglePagePct],
            'strict_bounce' => ['count' => $strictBounces, 'pct' => $strictBouncePct],
            'unverified' => ['count' => $unverifiedSessions, 'pct' => $unverifiedPct],
        ];

        $topLandingPages = $consolidated['journeys_aggregate']['top_landing_pages'] ?? [];
        $topExitPages = $consolidated['journeys_aggregate']['top_exit_pages'] ?? [];

        $humanVisitorsEst = (int)($kpis['unique_visitors'] ?? 0);
        $sovereignUniqueVisitors = $humanVisitorsEst > 0 ? $humanVisitorsEst : $certifiedSessions;

        $lsPlugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('lightstats');
        $privacyMode = ($lsPlugin && $lsPlugin->getConfigVal('privacy_mode') === 'cnil_exempt')
            ? 'cnil_exempt'
            : 'consent_banner';

        try {
            $saveConfigUrl = $this->router->generate('admin-lightstats-save-config');
        } catch (\Exception $e) {
            $saveConfigUrl = '/admin/lightstats/save-config';
        }

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('lightstats', 'admin');
        $tpl->set('privacyMode', $privacyMode);
        $tpl->set('isCnilExempt', ($privacyMode === 'cnil_exempt'));
        $tpl->set('saveConfigUrl', $saveConfigUrl);
        $tpl->set('token', $this->user->token);
        $tpl->set('consolidated', $consolidated);
        $tpl->set('totalDbSessions', $totalDbSessions);
        $tpl->set('certifiedSessions', $certifiedSessions);
        $tpl->set('certifiedPageviews', $certifiedPageviews);
        $tpl->set('admissibleHttpPages', $admissibleHttpPages);
        $tpl->set('engagementRatePct', $engagementRatePct);
        $tpl->set('sessionBreakdown', $sessionBreakdown);
        $tpl->set('periodDays', $periodDays);
        $tpl->set('sovereignUniqueVisitors', $sovereignUniqueVisitors);
        $tpl->set('humanVisitorsEst', $humanVisitorsEst);
        $tpl->set('httpKpis', $httpKpis);
        $tpl->set('topLandingPages', $topLandingPages);
        $tpl->set('topExitPages', $topExitPages);
        $tpl->set('journeys', $journeys);
        $tpl->set('linkToHome', $baseUrl);
        $tpl->set('activeTab', $activeTab);
        $tpl->set('activePreset', $activePreset);
        $tpl->set('prevPageUrl', $prevPageUrl);
        $tpl->set('nextPageUrl', $nextPageUrl);
        $tpl->set('paginationElements', $paginationElements);
        $tpl->set('browserDateStart', $browserDateStart);
        $tpl->set('browserDateEnd', $browserDateEnd);
        $tpl->set('inDateStart', $inDateStart);
        $tpl->set('inDateEnd', $inDateEnd);
        $tpl->set('logsManager', $logsManager);
        $tpl->set('logs', $paginatedLogs);
        $tpl->set('allLogs', $logs);
        $tpl->set('uniqueVisitor', $uniqueVisitor);
        $tpl->set('chartVisitors', $chartVisitors);
        $tpl->set('chartPages', $chartPages);
        $tpl->set('chartDays', $chartDays);
        $tpl->set('isSingleDay', $isSingleDay);
        $tpl->set('hourlyChartLabels', $hourlyChartLabels);
        $tpl->set('hourlyChartVisitors', $hourlyChartVisitors);
        $tpl->set('hourlyChartPages', $hourlyChartPages);
        $tpl->set('kpis', $kpis);
        $tpl->set('currentPage', $currentPage);
        $tpl->set('totalPages', $totalPages);
        $tpl->set('totalItems', $totalItems);
        $tpl->set('itemsPerPage', $itemsPerPage);
        $tpl->set('paginationStart', $paginationStart);
        $tpl->set('paginationEnd', $paginationEnd);

        $response->addTemplate($tpl);
        return $response;
    }

    public function saveConfig()
    {
        if (!isset($_POST['token']) || $_POST['token'] !== $this->user->token) {
            \Utils\Show::msg(\Core\Lang::get('core-invalid-token'), 'error');
            $this->core->redirect($this->router->generate('admin-lightstats-home'));
        }

        $mode = ($_POST['privacy_mode'] ?? 'consent_banner') === 'cnil_exempt'
            ? 'cnil_exempt'
            : 'consent_banner';

        $plugin = $this->pluginsManager->getPlugin('lightstats');
        if ($plugin) {
            $plugin->setConfigVal('privacy_mode', $mode);
            $this->pluginsManager->savePluginConfig($plugin);
            \Utils\Show::msg(\Core\Lang::get('core-changes-saved'), 'success');
        }

        $this->core->redirect($this->router->generate('admin-lightstats-home'));
    }
}
