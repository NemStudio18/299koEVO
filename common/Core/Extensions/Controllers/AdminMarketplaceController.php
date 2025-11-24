<?php

namespace Core\Extensions\Controllers;

use Core\Controllers\AdminController;
use Core\Extensions\MarketPlaceManager;
use Core\Extensions\MarketPlaceRessource;
use Core\Responses\AdminResponse;
use Utils\Show;
use Core\Lang;
use Utils\Util;

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

/**
 * AdminMarketplaceController
 *
 * This controller manages the marketplace homepage in the admin panel.
 * It ensures that the cache files for plugins and themes are valid and updated.
 * Then, it randomly selects 5 plugins and 5 themes to display.
 */
class AdminMarketplaceController extends AdminController
{
    protected MarketPlaceManager $marketManager;
    
    public function __construct() {
        parent::__construct();
        if (!function_exists('curl_init')) {
            Show::msg(Lang::get('marketplace.curl_not_installed'), 'error');
            $this->core->redirect($this->router->generate('pluginsmanager-list'));
        }
        $this->marketManager = $this->core->extensions()->marketplace();
    }

    public function index() {
        $themes = $this->marketManager->getThemes() ?? [];
        $plugins = $this->marketManager->getPlugins() ?? [];
        shuffle($plugins);
        $randomPlugins = array_slice($plugins, 0, 5);

        // Randomly select 5 themes from the cache data
        shuffle($themes);
        $randomThemes = array_slice($themes, 0, 5);

        $plugins = [];
        foreach ($randomPlugins as $plugin) {
            $r = new MarketPlaceRessource(MarketPlaceRessource::TYPE_PLUGIN, $plugin);
            $plugins[$plugin->slug] = $r;
        }

        $themes = [];
        foreach ($randomThemes as $theme) {
            $r = new MarketPlaceRessource(MarketPlaceRessource::TYPE_THEME, $theme);
            $themes[$theme->slug] = $r;
        }
        // Prepare the admin response using the marketplace template
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('marketplace', 'admin-marketplace');
        $response->setTitle(Lang::get('marketplace.description'));
        $pluginsTpl = $response->createPluginTemplate('marketplace', 'display-ressources');
        $pluginsTpl->set('ressources', $plugins);
        $pluginsTpl->set('token', $this->user->token);
        $themesTpl = $response->createPluginTemplate('marketplace', 'display-ressources');
        $themesTpl->set('ressources', $themes);
        $themesTpl->set('token', $this->user->token);
        $tpl->set('PLUGINS_TPL', $pluginsTpl->output());
        $tpl->set('THEMES_TPL', $themesTpl->output());
        $tpl->set('havePlugins', !empty($plugins));
        $tpl->set('haveThemes', !empty($themes));

        $tpl->set('pluginsPageUrl', $this->router->generate('marketplace-plugins'));
        $tpl->set('themesPageUrl', $this->router->generate('marketplace-themes'));
        $response->addTemplate($tpl);
        return $response;
    }

    public function installRelease(string $type, string $slug, string $token) {
        if (!$this->user->isAuthorized()) {
            $this->core->redirect($this->router->generate('admin-marketplace'));
        }

        $ressourceArray = $this->marketManager->getRessourceAsArray($type, $slug);
        if (!$ressourceArray) {
            Show::msg(Lang::get('marketplace.ressource_not_found'), 'error');
            $this->core->redirect($this->router->generate('admin-marketplace'));
        }
        $ressource = new MarketPlaceRessource($type, $ressourceArray);
        if (!$ressource->isInstallable) {
            Show::msg(Lang::get('marketplace.server_requirements_error'), 'error');
            $this->core->redirect($this->router->generate('admin-marketplace'));
        }
        $installed = $this->marketManager->installRessource($ressource);
        if ($installed) {
            if ($ressource->type == MarketPlaceRessource::TYPE_PLUGIN) {
                if (!$ressource->isInstalled) {
                    Show::msg(Lang::get('marketplace.new_plugin_installed', $this->router->generate('pluginsmanager-list')), 'success');
                } else {
                    Show::msg(Lang::get('marketplace.plugin_updated'), 'success');
                }
            } else {
                if (!$ressource->isInstalled) {
                    Show::msg(Lang::get('marketplace.new_theme_installed', $this->router->generate('configmanager-admin') . '#label_theme'), 'success');
                } else {
                    Show::msg(Lang::get('marketplace.theme_updated'), 'success');
                }
            }
        } else {
            Show::msg(Lang::get('marketplace.error_during_install'), 'error');
        }
        $this->core->redirect($this->router->generate('admin-marketplace'));
    }

    public function uninstallRessource(string $type, string $slug, string $token) {
        if (!$this->user->isAuthorized()) {
            $this->core->redirect($this->router->generate('admin-marketplace'));
        }
        if ($type === MarketPlaceRessource::TYPE_PLUGIN) {
            $plugin = $this->pluginsManager->getPlugin($slug);
            if ($plugin !== false) {
                $defaultPlugin = $this->core->getConfigVal('defaultPlugin');
                if ($slug !== $defaultPlugin) {
                    if ($this->pluginsManager->uninstallPlugin($slug)) {
                        Show::msg(Lang::get('marketplace.plugin_uninstalled'), 'success');
                    } else {
                        Show::msg(Lang::get('marketplace.error_during_uninstall'), 'error');
                    }
                } else {
                    Show::msg(Lang::get('marketplace.plugin_is_default'), 'error');
                }
            } else {
                Show::msg(Lang::get('marketplace.plugin_not_found'), 'error');
            }
        } else {
            $usedTheme = $this->core->getConfigVal('theme');
            if ($slug !== $usedTheme) {
                if (Util::delTree(THEMES . $slug)) {
                    Show::msg(Lang::get('marketplace.theme_uninstalled'), 'success');
                } else {
                    Show::msg(Lang::get('marketplace.error_during_uninstall'), 'error');
                }
            } else {
                Show::msg(Lang::get('marketplace.theme_is_used'), 'error');
            }
        }
        $this->core->redirect($this->router->generate('admin-marketplace'));
    }

}