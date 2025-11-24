<?php

namespace Core\Extensions\Controllers;

use Core\Controllers\AdminController;
use Core\Extensions\MarketPlaceManager;
use Core\Extensions\MarketPlaceRessource;
use Core\Responses\AdminResponse;
use Utils\Show;
use Core\Lang;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxime Blanc <nemstudio18@gmail.com>
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 *
 * @package 299Ko https://github.com/299Ko/299ko
 *
 * Marketplace Plugin for 299Ko CMS
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */

defined('ROOT') or exit('Access denied!');

class PluginsMarketController extends AdminController
{
    protected MarketPlaceManager $marketManager;
    public function __construct() {
        parent::__construct();
        if(!function_exists('curl_init')) {
            Show::msg(Lang::get('marketplace.curl_not_installed'), 'error');
            $this->core->redirect($this->router->generate('pluginsmanager-list'));
        }
        $this->marketManager = $this->core->extensions()->marketplace();
    }

    public function index() {
        $pluginsData = $this->marketManager->getPlugins() ?? [];
        $plugins = [];
        foreach ($pluginsData as $plugin) {
            $r = new MarketPlaceRessource(MarketPlaceRessource::TYPE_PLUGIN, $plugin);
            $plugins[$plugin->slug] = $r;
        }

        // Prepare the admin response with the plugins marketplace template
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('marketplace', 'admin-marketplace-plugins');
        $response->setTitle(Lang::get('marketplace.list_plugins'));
        
        $pluginsTpl = $response->createPluginTemplate('marketplace', 'display-ressources');
        $pluginsTpl->set('ressources', $plugins);
        $pluginsTpl->set('token', $this->user->token);
        $tpl->set('PLUGINS_TPL', $pluginsTpl->output());
        $response->addTemplate($tpl);
        return $response;

    }


}