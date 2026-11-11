<?php

namespace SearchMaps\Controllers;

use Core\Controllers\AdminController as BaseAdminController;
use Core\Responses\AdminResponse;

defined('ROOT') or exit('Access denied!');

class SearchMapsAdminController extends BaseAdminController
{
    public function home()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('search-maps:admin'), $ctx)) {
            \Utils\Show::msg('Permissions insuffisantes.', 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }

        $pluginManager = \Core\Plugin\PluginsManager::getInstance();
        $config = $pluginManager->getPlugin('SearchMaps')->getConfig();

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('SearchMaps', 'param');
        $tpl->set('config', $config);
        $tpl->set('csrfToken', \Core\Security\Csrf::token());
        $response->addTemplate($tpl);
        return $response;
    }

    public function save()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('search-maps:admin'), $ctx)) {
            \Utils\Show::msg('Permissions insuffisantes.', 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }

        if (!\Core\Security\Csrf::validate($_POST['_csrf'] ?? '')) {
            \Utils\Show::msg('Token CSRF invalide.', 'error');
            $this->core->redirect($this->router->generate('admin-SearchMaps-home'));
            return;
        }

        $pluginManager = \Core\Plugin\PluginsManager::getInstance();
        $plugin = $pluginManager->getPlugin('SearchMaps');

        $data = [
            'zoom_default' => (int)($_POST['zoom_default'] ?? 13),
            'center_lat' => (float)($_POST['center_lat'] ?? 47.0833),
            'center_lon' => (float)($_POST['center_lon'] ?? 2.4),
            'priority' => (int)($_POST['priority'] ?? 1),
            'debug' => isset($_POST['debug']) ? 1 : 0
        ];

        foreach ($data as $k => $v) {
            $plugin->setConfigVal($k, $v);
        }

        $pluginManager->savePluginConfig($plugin);
        \Utils\Show::msg('Configuration enregistrée avec succès.', 'success');
        $this->core->redirect($this->router->generate('admin-SearchMaps-home'));
    }
}
