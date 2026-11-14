<?php

namespace SearchMaps\Controllers;

use Core\Controllers\AdminController as BaseAdminController;
use Core\Responses\AdminResponse;
use SearchMaps\Services\PoiManager;

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
        $pois = PoiManager::getAll();
        $categories = PoiManager::getCategories();

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('SearchMaps', 'param');
        $tpl->set('config', $config);
        $tpl->set('zoomDefault', (int)($config['zoom_default'] ?? 13));
        $tpl->set('centerLat', (string)($config['center_lat'] ?? '47.0833'));
        $tpl->set('centerLon', (string)($config['center_lon'] ?? '2.4'));
        $tpl->set('priorityVal', (int)($config['priority'] ?? 1));
        $tpl->set('debugChecked', !empty($config['debug']));
        $tpl->set('pois', $pois);
        $tpl->set('categories', $categories);
        $tpl->set('poisCount', count($pois));
        $tpl->set('poisJson', json_encode($pois, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP));
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

    public function savePoi()
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

        $poi = [
            'id' => trim($_POST['poi_id'] ?? ''),
            'title' => trim($_POST['title'] ?? ''),
            'category' => trim($_POST['category'] ?? 'Général'),
            'lat' => (float)($_POST['lat'] ?? 47.0833),
            'lon' => (float)($_POST['lon'] ?? 2.4),
            'address' => trim($_POST['address'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'website' => trim($_POST['website'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'icon' => trim($_POST['icon'] ?? 'fa-map-pin'),
            'color' => trim($_POST['color'] ?? '#0284c7'),
        ];

        if (empty($poi['title'])) {
            \Utils\Show::msg('Le titre du point d\'intérêt est obligatoire.', 'error');
        } else {
            PoiManager::save($poi);
            \Utils\Show::msg('Point d\'intérêt enregistré avec succès.', 'success');
        }

        $this->core->redirect($this->router->generate('admin-SearchMaps-home') . '#tab-pois');
    }

    public function deletePoi($id)
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

        PoiManager::delete((string)$id);
        \Utils\Show::msg('Point d\'intérêt supprimé.', 'success');
        $this->core->redirect($this->router->generate('admin-SearchMaps-home') . '#tab-pois');
    }

    public function apiPois()
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(PoiManager::getAll(), JSON_UNESCAPED_UNICODE);
        exit;
    }
}
