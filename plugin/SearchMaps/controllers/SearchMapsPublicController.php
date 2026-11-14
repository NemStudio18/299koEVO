<?php

namespace SearchMaps\Controllers;

use Core\Controllers\PublicController as BasePublicController;
use Core\Responses\PublicResponse;
use SearchMaps\Services\PoiManager;

defined('ROOT') or exit('Access denied!');

class SearchMapsPublicController extends BasePublicController
{
    public function publicMap()
    {
        $pluginManager = \Core\Plugin\PluginsManager::getInstance();
        $config = $pluginManager->getPlugin('SearchMaps')->getConfig();

        $pois = PoiManager::getAll();
        $categories = PoiManager::getCategories();

        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('SearchMaps', 'carte');
        $tpl->set('pageTitle', 'Carte Interactive & Points d\'intérêt');
        $tpl->set('pois', $pois);
        $tpl->set('categories', $categories);
        $tpl->set('poisCount', count($pois));
        $tpl->set('poisJson', json_encode($pois, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP));
        $tpl->set('centerLat', (float)($config['center_lat'] ?? 47.0833));
        $tpl->set('centerLon', (float)($config['center_lon'] ?? 2.4));
        $tpl->set('zoomDefault', (int)($config['zoom_default'] ?? 13));
        $response->addTemplate($tpl);
        return $response;
    }
}

