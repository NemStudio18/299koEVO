<?php

namespace SearchEngine\Controllers;

use Core\Controllers\AdminController as BaseAdminController;
use Core\Responses\AdminResponse;

defined('ROOT') or exit('Access denied!');

class SearchEngineAdminController extends BaseAdminController
{
    public function index()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('search-engine:admin'), $ctx)) {
            \Utils\Show::msg('Permissions insuffisantes.', 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }

        $pluginManager = \Core\Plugin\PluginsManager::getInstance();
        $config = $pluginManager->getPlugin('SearchEngine')->getConfig();

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('SearchEngine', 'param');
        $tpl->set('config', $config);
        $response->addTemplate($tpl);
        return $response;
    }

    public function save()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('search-engine:admin'), $ctx)) {
            \Utils\Show::msg('Permissions insuffisantes.', 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }

        $pluginManager = \Core\Plugin\PluginsManager::getInstance();
        $plugin = $pluginManager->getPlugin('SearchEngine');

        $data = [
            'priority' => $_POST['priority'] ?? '10',
            'radius_default' => $_POST['radius_default'] ?? '5.0',
            'items_per_page' => $_POST['items_per_page'] ?? '20',
            'weight_name' => $_POST['weight_name'] ?? '10',
            'weight_description' => $_POST['weight_description'] ?? '5',
            'weight_brand' => $_POST['weight_brand'] ?? '2',
            'debug' => isset($_POST['debug']) ? 1 : 0
        ];

        foreach ($data as $k => $v) {
            $plugin->setConfigVal($k, $v);
        }

        $pluginManager->savePluginConfig($plugin);
        \Utils\Show::msg('Configuration enregistrée avec succès.', 'success');
        $this->core->redirect($this->router->generate('admin-SearchEngine-index'));
    }

    public function clearCache()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('search-engine:admin'), $ctx)) {
            \Utils\Show::msg('Permissions insuffisantes.', 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }

        $pdo = \Core\Database::getInstance()->getPdo('searchevo');
        $cacheService = new \SearchEngine\Services\CacheJsonService($pdo);
        $cacheService->invalidateAll();

        \Utils\Show::msg('Le cache de recherche a été vidé.', 'success');
        $this->core->redirect($this->router->generate('admin-SearchEngine-index'));
    }
}
