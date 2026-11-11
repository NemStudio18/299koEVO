<?php

namespace LightStats\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Utils\Util;
use LightStats\Lib\LightStatsLogsManager;

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

        // Récupérer les dates depuis POST ou GET (pour la pagination)
        $dateStartInput = $_POST['dateStart'] ?? $_GET['dateStart'] ?? date('Y-m-d');
        $dateEndInput = $_POST['dateEnd'] ?? $_GET['dateEnd'] ?? date('Y-m-d');
        $dateStart = new \DateTime($dateStartInput);
        $dateEnd = new \DateTime($dateEndInput);

        $browserDateStart = $dateStart->format('Y-m-d');
        $browserDateEnd = $dateEnd->format('Y-m-d');

        $inDateStart = Util::getDate($dateStart->getTimestamp());
        $inDateEnd = Util::getDate($dateEnd->getTimestamp());

        $dateEnd->modify('+1 day');

        $logsManager = new LightStatsLogsManager($dateStart, $dateEnd);
        $logs = $logsManager->logs;
        $uniqueVisitor = $logsManager->uniquesVisitor;

        // Calculer les KPI supplémentaires (mode public par défaut)
        $viewMode = $_GET['mode'] ?? 'public';
        $kpis = $logsManager->getKpis($viewMode);
        
        // Générer les graphiques avec les données humaines uniquement
        $chartVisitors = $logsManager->getChartsVisitors($viewMode);
        $chartPages = $logsManager->getChartsPages($viewMode);
        $chartDays = $logsManager->getChartsDays();
        
        // S'assurer que les variables sont définies même si vides
        if (empty($chartVisitors)) {
            $chartVisitors = '';
        }
        if (empty($chartPages)) {
            $chartPages = '';
        }
        if (empty($chartDays)) {
            $chartDays = '';
        }
        
        // Préparer les données pour les graphiques JavaScript (éviter les boucles dans le template)
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
        
        // Normaliser les référents pour l'affichage (remplacer '' par 'Direct' et ajouter un flag)
        if (isset($kpis['top_referers']) && is_array($kpis['top_referers'])) {
            $normalizedReferers = [];
            foreach ($kpis['top_referers'] as $referer => $count) {
                if ($referer === '') {
                    $normalizedReferers['Direct'] = [
                        'label' => 'Direct',
                        'url' => '',
                        'is_direct' => true,
                        'count' => $count
                    ];
                } else {
                    $normalizedReferers[$referer] = [
                        'label' => $referer,
                        'url' => $referer,
                        'is_direct' => false,
                        'count' => $count
                    ];
                }
            }
            $kpis['top_referers'] = $normalizedReferers;
        }
        
        // Pagination
        $itemsPerPage = 50;
        $currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $totalItems = count($logs);
        $totalPages = max(1, ceil($totalItems / $itemsPerPage));
        $offset = ($currentPage - 1) * $itemsPerPage;
        $paginatedLogs = array_slice($logs, $offset, $itemsPerPage);
        
        // Calculer les valeurs pour l'affichage de la pagination
        $paginationStart = $offset + 1;
        $paginationEnd = min($offset + $itemsPerPage, $totalItems);
        
        // Créer un tableau pour la pagination (1 à totalPages)
        $paginationPages = [];
        for ($i = 1; $i <= $totalPages; $i++) {
            $paginationPages[] = $i;
        }
        
        // Construire l'URL de base pour la pagination
        try {
            $baseUrl = $this->router->generate('admin-lightstats-home');
        } catch (\Exception $e) {
            $baseUrl = '/admin/lightstats';
        }
        
        // Construire les URLs de pagination avec les paramètres de date
        $paginationUrls = [];
        foreach ($paginationPages as $pageNum) {
            $params = [
                'page' => $pageNum,
                'dateStart' => $browserDateStart,
                'dateEnd' => $browserDateEnd
            ];
            $paginationUrls[$pageNum] = $baseUrl . '?' . http_build_query($params);
        }
        
        // URLs pour précédent/suivant
        $prevPageUrl = null;
        if ($currentPage > 1) {
            $params = [
                'page' => $currentPage - 1,
                'dateStart' => $browserDateStart,
                'dateEnd' => $browserDateEnd
            ];
            $prevPageUrl = $baseUrl . '?' . http_build_query($params);
        }
        
        $nextPageUrl = null;
        if ($currentPage < $totalPages) {
            $params = [
                'page' => $currentPage + 1,
                'dateStart' => $browserDateStart,
                'dateEnd' => $browserDateEnd
            ];
            $nextPageUrl = $baseUrl . '?' . http_build_query($params);
        }

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('lightstats', 'admin');
        $tpl->set('linkToHome', $baseUrl);
        $tpl->set('paginationUrls', $paginationUrls);
        $tpl->set('prevPageUrl', $prevPageUrl);
        $tpl->set('nextPageUrl', $nextPageUrl);
        $tpl->set('browserDateStart', $browserDateStart);
        $tpl->set('browserDateEnd', $browserDateEnd);
        $tpl->set('inDateStart', $inDateStart);
        $tpl->set('inDateEnd', $inDateEnd);
        $tpl->set('logsManager', $logsManager);
        $tpl->set('logs', $paginatedLogs);
        $tpl->set('allLogs', $logs); // Pour les totaux
        $tpl->set('uniqueVisitor', $uniqueVisitor);
        $tpl->set('chartVisitors', $chartVisitors);
        $tpl->set('chartPages', $chartPages);
        $tpl->set('chartDays', $chartDays);
        $tpl->set('kpis', $kpis);
        $tpl->set('currentPage', $currentPage);
        $tpl->set('totalPages', $totalPages);
        $tpl->set('totalItems', $totalItems);
        $tpl->set('itemsPerPage', $itemsPerPage);
        $tpl->set('paginationPages', $paginationPages);
        $tpl->set('paginationStart', $paginationStart);
        $tpl->set('paginationEnd', $paginationEnd);
        $response->addTemplate($tpl);
        return $response;
    }
}