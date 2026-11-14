<?php

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Lang;
use Utils\Show;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 *
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class TelemetryAdminController extends AdminController
{
    private string $apiUrl;

    public function __construct()
    {
        parent::__construct();
        $core = \Core\Core::getInstance();
        $marketplaceUrl = $core->getConfigVal('marketplaceUrl');
        $this->apiUrl = rtrim($marketplaceUrl, '/');
    }

    private function checkAdminAccess(): bool
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('telemetry:admin'), $ctx)) {
            Show::msg('Permissions insuffisantes.', 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return false;
        }
        return true;
    }

    /**
     * Dashboard principal avec statistiques
     */
    public function dashboard()
    {
        if (!$this->checkAdminAccess()) {
            return;
        }

        // Les traductions sont déjà chargées dans AdminController::__construct()
        // Mais on s'assure qu'elles sont bien chargées (comme le fait FAQ)
        if ($this->runPlugin) {
            $this->runPlugin->loadLangFile();
        }
        
        // Test pour vérifier que les traductions sont chargées
        $testKey = 'telemetryadmin.dashboard-title';
        $testValue = Lang::get($testKey);
        if ($testValue === $testKey) {
            // La traduction n'est pas trouvée, forcer le chargement
            $langPath = PLUGINS . 'telemetryadmin' . DS . 'langs' . DS;
            if (is_dir($langPath)) {
                \Core\Lang::loadLanguageFile($langPath);
            }
        }
        
        // Utiliser setMainTitle comme les autres plugins
        $this->runPlugin->setMainTitle(Lang::get('telemetryadmin.dashboard-title'));
        $this->runPlugin->setTitleTag(Lang::get('telemetryadmin.dashboard-title'));

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('telemetryadmin', 'dashboard');
        $response->setTitle(Lang::get('telemetryadmin.dashboard-title'));

        // Récupérer les statistiques et compléments depuis l'API
        $stats = $this->callApi('GET', '/admin/stats');
        $installations = $this->callApi('GET', '/admin/installations?per_page=50&page=1');
        $syncLogs = $this->callApi('GET', '/admin/sync-logs?per_page=20&page=1');
        
        // Si erreur API, utiliser Show::msg() comme les autres plugins
        if (isset($stats['error'])) {
            Show::msg($stats['error'], 'error');
            // Initialiser avec des données vides pour éviter les erreurs dans le template
            $stats = [
                'telemetry' => [
                    'total_installations' => 0,
                    'active_installations' => 0,
                    'cms_versions' => [],
                    'php_versions' => [],
                    'popular_plugins' => []
                ],
                'reports' => [
                    'total' => 0,
                    'by_status' => [],
                    'by_type' => []
                ]
            ];
        } else {
            // S'assurer que toutes les clés nécessaires existent
            if (empty($stats)) {
                $stats = [
                    'telemetry' => [
                        'total_installations' => 0,
                        'active_installations' => 0,
                        'cms_versions' => [],
                        'php_versions' => [],
                        'popular_plugins' => []
                    ],
                    'reports' => [
                        'total' => 0,
                        'by_status' => [],
                        'by_type' => []
                    ]
                ];
            } else {
                if (!isset($stats['telemetry'])) {
                    $stats['telemetry'] = [
                        'total_installations' => 0,
                        'active_installations' => 0,
                        'cms_versions' => [],
                        'php_versions' => [],
                        'popular_plugins' => []
                    ];
                }
                if (!isset($stats['reports'])) {
                    $stats['reports'] = [
                        'total' => 0,
                        'by_status' => [],
                        'by_type' => []
                    ];
                }
            }
        }
        
        // Compléments : sécuriser les tableaux
        if (!isset($stats['telemetry']['active_timeline'])) {
            $stats['telemetry']['active_timeline'] = [];
        }
        if (!is_array($installations)) {
            $installations = ['items' => [], 'total' => 0, 'page' => 1, 'per_page' => 50];
        }
        if (isset($installations['error'])) {
            Show::msg($installations['error'], 'error');
            $installations = ['items' => [], 'total' => 0, 'page' => 1, 'per_page' => 50];
        }
        if (!isset($installations['items'])) {
            $installations['items'] = [];
        }
        if (!is_array($syncLogs)) {
            $syncLogs = ['items' => [], 'total' => 0, 'page' => 1, 'per_page' => 20];
        }
        if (isset($syncLogs['error'])) {
            Show::msg($syncLogs['error'], 'error');
            $syncLogs = ['items' => [], 'total' => 0, 'page' => 1, 'per_page' => 20];
        }
        if (!isset($syncLogs['items'])) {
            $syncLogs['items'] = [];
        }

        // Ne JAMAIS passer de clé "error" au template
        $tpl->set('stats', $stats);
        $tpl->set('installations', $installations);
        $tpl->set('syncLogs', $syncLogs);
        $tpl->set('apiUrl', rtrim($this->apiUrl, '/'));

        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Liste des rapports
     */
    public function reports()
    {
        if (!$this->checkAdminAccess()) {
            return;
        }

        $this->runPlugin->setMainTitle(Lang::get('telemetryadmin.reports-title'));
        $this->runPlugin->setTitleTag(Lang::get('telemetryadmin.reports-title'));

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('telemetryadmin', 'reports');
        $response->setTitle(Lang::get('telemetryadmin.reports-title'));

        // Paramètres de pagination
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        $status = $_GET['status'] ?? null;
        $type = $_GET['type'] ?? null;

        // Construire l'URL avec les paramètres
        $params = ['page' => $page, 'per_page' => $perPage];
        if ($status !== null) {
            $params['status'] = $status;
        }
        if ($type !== null) {
            $params['type'] = $type;
        }

        $url = '/admin/reports?' . http_build_query($params);
        $result = $this->callApi('GET', $url);

        // Si erreur API, utiliser Show::msg()
        if (isset($result['error'])) {
            Show::msg($result['error'], 'error');
            $result = [
                'reports' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => 20,
                'total_pages' => 0
            ];
        }

        $tpl->set('reports', $result['reports'] ?? []);
        $tpl->set('pagination', [
            'total' => $result['total'] ?? 0,
            'page' => $result['page'] ?? 1,
            'per_page' => $result['per_page'] ?? 20,
            'total_pages' => $result['total_pages'] ?? 0,
        ]);
        $tpl->set('currentStatus', $status);
        $tpl->set('currentType', $type);
        $tpl->set('updateStatusUrl', $this->router->generate('telemetryadmin-report-status', ['reportId' => 'REPORT_ID']));

        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Liste des installations (paginée + filtres)
     */
    public function installations()
    {
        if (!$this->checkAdminAccess()) {
            return;
        }

        $this->runPlugin->setMainTitle(Lang::get('telemetryadmin.installations-title'));
        $this->runPlugin->setTitleTag(Lang::get('telemetryadmin.installations-title'));

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('telemetryadmin', 'installations');
        $response->setTitle(Lang::get('telemetryadmin.installations-title'));

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, (int)($_GET['per_page'] ?? 20));
        $filters = [
            'lang' => $_GET['lang'] ?? null,
            'theme' => $_GET['theme'] ?? null,
            'cms_version' => $_GET['cms_version'] ?? null,
            'php_version' => $_GET['php_version'] ?? null,
            'status' => $_GET['status'] ?? null,
            'since_days' => isset($_GET['since_days']) ? (int)$_GET['since_days'] : null,
            'level' => $_GET['level'] ?? null,
            'search' => $_GET['search'] ?? null,
        ];

        $query = array_filter([
            'page' => $page,
            'per_page' => $perPage,
            'lang' => $filters['lang'],
            'theme' => $filters['theme'],
            'cms_version' => $filters['cms_version'],
            'php_version' => $filters['php_version'],
            'status' => $filters['status'],
            'since_days' => $filters['since_days'],
            'level' => $filters['level'],
            'search' => $filters['search'],
        ], static fn($v) => $v !== null && $v !== '');

        $url = '/admin/installations?' . http_build_query($query);
        $result = $this->callApi('GET', $url);

        if (isset($result['error'])) {
            Show::msg($result['error'], 'error');
            $result = ['items' => [], 'total' => 0, 'page' => 1, 'per_page' => $perPage];
        }

        $tpl->set('installations', $result['items'] ?? []);
        $tpl->set('pagination', [
            'total' => $result['total'] ?? 0,
            'page' => $result['page'] ?? 1,
            'per_page' => $result['per_page'] ?? $perPage,
        ]);
        $tpl->set('filters', $filters);
        $tpl->set('exportUrl', rtrim($this->apiUrl, '/') . '/admin/installations/export?' . http_build_query($query));

        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Plugins pour une installation
     */
    public function installationPlugins(string $installationId)
    {
        if (!$this->checkAdminAccess()) {
            return;
        }

        $this->runPlugin->setMainTitle(Lang::get('telemetryadmin.installation-plugins-title'));
        $this->runPlugin->setTitleTag(Lang::get('telemetryadmin.installation-plugins-title'));

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('telemetryadmin', 'installation-plugins');
        $response->setTitle(Lang::get('telemetryadmin.installation-plugins-title'));

        $result = $this->callApi('GET', '/admin/installations/' . $installationId . '/plugins');
        if (isset($result['error'])) {
            Show::msg($result['error'], 'error');
            $result = ['plugins' => [], 'installation_id' => $installationId];
        }

        $tpl->set('installationId', $installationId);
        $tpl->set('plugins', $result['plugins'] ?? []);
        $tpl->set('backUrl', $this->router->generate('telemetryadmin-installations'));

        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Logs de synchronisation
     */
    public function syncLogs()
    {
        if (!$this->checkAdminAccess()) {
            return;
        }

        $this->runPlugin->setMainTitle(Lang::get('telemetryadmin.sync-logs-title'));
        $this->runPlugin->setTitleTag(Lang::get('telemetryadmin.sync-logs-title'));

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('telemetryadmin', 'sync-logs');
        $response->setTitle(Lang::get('telemetryadmin.sync-logs-title'));

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, (int)($_GET['per_page'] ?? 50));
        $filters = [
            'installation_id' => $_GET['installation_id'] ?? null,
            'sync_type' => $_GET['sync_type'] ?? null,
            'success' => isset($_GET['success']) ? $_GET['success'] : null,
            'from' => $_GET['from'] ?? null,
            'to' => $_GET['to'] ?? null,
        ];

        $query = array_filter([
            'page' => $page,
            'per_page' => $perPage,
            'installation_id' => $filters['installation_id'],
            'sync_type' => $filters['sync_type'],
            'success' => $filters['success'],
            'from' => $filters['from'],
            'to' => $filters['to'],
        ], static fn($v) => $v !== null && $v !== '');

        $url = '/admin/sync-logs?' . http_build_query($query);
        $result = $this->callApi('GET', $url);

        if (isset($result['error'])) {
            Show::msg($result['error'], 'error');
            $result = ['items' => [], 'total' => 0, 'page' => 1, 'per_page' => $perPage];
        }

        $tpl->set('logs', $result['items'] ?? []);
        $tpl->set('pagination', [
            'total' => $result['total'] ?? 0,
            'page' => $result['page'] ?? 1,
            'per_page' => $result['per_page'] ?? $perPage,
        ]);
        $tpl->set('filters', $filters);

        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Détail d'un rapport
     */
    public function reportDetail(string $reportId)
    {
        if (!$this->checkAdminAccess()) {
            return;
        }

        $this->runPlugin->setMainTitle(Lang::get('telemetryadmin.report-detail-title'));
        $this->runPlugin->setTitleTag(Lang::get('telemetryadmin.report-detail-title'));

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('telemetryadmin', 'report-detail');
        $response->setTitle(Lang::get('telemetryadmin.report-detail-title'));

        $report = $this->callApi('GET', '/admin/reports/' . $reportId);
        
        if (isset($report['error'])) {
            Show::msg(Lang::get('telemetryadmin.report-not-found'), 'error');
            $this->core->redirect($this->router->generate('telemetryadmin-reports'));
            return;
        }

        $tpl->set('report', $report);
        $tpl->set('updateStatusUrl', $this->router->generate('telemetryadmin-report-status', ['reportId' => $reportId]));
        $tpl->set('backUrl', $this->router->generate('telemetryadmin-reports'));
        
        // Passer l'URL du marketplace pour les screenshots
        $core = \Core\Core::getInstance();
        $marketplaceUrl = $core->getConfigVal('marketplaceUrl');
        $tpl->set('marketplaceUrl', rtrim($marketplaceUrl, '/'));

        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Mise à jour du statut d'un rapport
     */
    public function updateStatus(string $reportId)
    {
        if (!$this->checkAdminAccess()) {
            return;
        }

        $status = $_POST['status'] ?? '';
        $comment = trim($_POST['comment'] ?? '');

        if (empty($status)) {
            Show::msg(Lang::get('telemetryadmin.status-required'), 'error');
            $this->core->redirect($this->router->generate('telemetryadmin-report-detail', ['reportId' => $reportId]));
            return;
        }

        $data = ['status' => $status];
        if (!empty($comment)) {
            $data['comment'] = $comment;
        }

        $result = $this->callApi('POST', '/admin/reports/' . $reportId . '/status', $data);

        if (isset($result['success']) && $result['success']) {
            Show::msg(Lang::get('telemetryadmin.status-updated'), 'success');
        } else {
            $errorMsg = $result['error'] ?? Lang::get('telemetryadmin.status-update-error');
            Show::msg($errorMsg, 'error');
        }

        $this->core->redirect($this->router->generate('telemetryadmin-report-detail', ['reportId' => $reportId]));
    }

    /**
     * Appelle l'API marketplace
     */
    private function callApi(string $method, string $endpoint, ?array $data = null): array
    {
        try {
            // Vérifier que l'URL de l'API est configurée
            if (empty($this->apiUrl)) {
                return ['error' => Lang::get('telemetryadmin.api-url-not-configured') ?: 'URL de l\'API non configurée'];
            }

            // Construire l'URL comme le fait le service de télémétrie
            $endpoint = ltrim($endpoint, '/');
            $url = rtrim($this->apiUrl, '/') . '/' . $endpoint;
            
            $curl = new \Core\Http\Curl($url);
            
            if ($method === 'GET') {
                $curl->get();
            } else {
                $curl->post();
                if ($data !== null) {
                    $curl->sendAsJson = true;
                    $curl->setDatas($data);
                }
            }

            $resp = $curl->execute()->getResponse();

            if ($resp['code'] === 200) {
                $result = json_decode($resp['body'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return ['error' => Lang::get('telemetryadmin.api-invalid-response') ?: 'Réponse invalide de l\'API'];
                }
                return is_array($result) ? $result : [];
            }

            // Message d'erreur détaillé
            $errorMessage = 'Erreur API HTTP ' . $resp['code'];
            if (!empty($resp['body'])) {
                $errorData = json_decode($resp['body'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($errorData) && isset($errorData['error'])) {
                    $errorMessage = $errorData['error'];
                } else {
                    $errorMessage .= ': ' . substr($resp['body'], 0, 100);
                }
            }
            
            return ['error' => $errorMessage];
        } catch (\Exception $e) {
            return ['error' => 'Exception: ' . $e->getMessage()];
        }
    }
}
