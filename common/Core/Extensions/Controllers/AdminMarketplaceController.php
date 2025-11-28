<?php

namespace Core\Extensions\Controllers;

use Core\Controllers\AdminController;
use Core\Extensions\LegacyPluginsMigrator;
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
        $allThemes = $this->marketManager->getThemes() ?? [];
        $allPlugins = $this->marketManager->getPlugins() ?? [];

        // Featured plugins and themes (5 random ones for overview)
        $randomPlugins = $allPlugins;
        shuffle($randomPlugins);
        $randomPlugins = array_slice($randomPlugins, 0, 5);
        
        $randomThemes = $allThemes;
        shuffle($randomThemes);
        $randomThemes = array_slice($randomThemes, 0, 5);

        $featuredPlugins = [];
        foreach ($randomPlugins as $plugin) {
            $r = new MarketPlaceRessource(MarketPlaceRessource::TYPE_PLUGIN, $plugin);
            $featuredPlugins[$plugin->slug] = $r;
        }

        $featuredThemes = [];
        foreach ($randomThemes as $theme) {
            $r = new MarketPlaceRessource(MarketPlaceRessource::TYPE_THEME, $theme);
            $featuredThemes[$theme->slug] = $r;
        }

        // All plugins and themes for dedicated tabs
        $allPluginsResources = [];
        foreach ($allPlugins as $plugin) {
            $r = new MarketPlaceRessource(MarketPlaceRessource::TYPE_PLUGIN, $plugin);
            $allPluginsResources[$plugin->slug] = $r;
        }

        $allThemesResources = [];
        foreach ($allThemes as $theme) {
            $r = new MarketPlaceRessource(MarketPlaceRessource::TYPE_THEME, $theme);
            $allThemesResources[$theme->slug] = $r;
        }

        // Prepare the admin response using the marketplace template
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('marketplace', 'admin-marketplace');
        $response->setTitle(Lang::get('marketplace.description'));
        
        // Featured templates for overview tab
        $featuredPluginsTpl = $response->createPluginTemplate('marketplace', 'display-ressources');
        $featuredPluginsTpl->set('ressources', $featuredPlugins);
        $featuredPluginsTpl->set('token', $this->user->token);
        $tpl->set('FEATURED_PLUGINS_TPL', $featuredPluginsTpl->output());
        
        $featuredThemesTpl = $response->createPluginTemplate('marketplace', 'display-ressources');
        $featuredThemesTpl->set('ressources', $featuredThemes);
        $featuredThemesTpl->set('token', $this->user->token);
        $tpl->set('FEATURED_THEMES_TPL', $featuredThemesTpl->output());
        
        // All plugins template for plugins tab
        $allPluginsTpl = $response->createPluginTemplate('marketplace', 'display-ressources');
        $allPluginsTpl->set('ressources', $allPluginsResources);
        $allPluginsTpl->set('token', $this->user->token);
        $tpl->set('ALL_PLUGINS_TPL', $allPluginsTpl->output());
        
        // All themes template for themes tab
        $allThemesTpl = $response->createPluginTemplate('marketplace', 'display-ressources');
        $allThemesTpl->set('ressources', $allThemesResources);
        $allThemesTpl->set('token', $this->user->token);
        $tpl->set('ALL_THEMES_TPL', $allThemesTpl->output());
        
        $tpl->set('havePlugins', !empty($allPlugins));
        $tpl->set('haveThemes', !empty($allThemes));
        $pendingLegacy = $this->core->extensions()->getPendingLegacyPlugins();
        $tpl->set('pendingLegacyPlugins', $pendingLegacy);
        $tpl->set('pendingLegacyList', implode(', ', $pendingLegacy));
        $tpl->set('pendingLegacyCount', count($pendingLegacy));
        $tpl->set('legacyMigrationUrl', $this->router->generate('marketplace-migrate-legacy'));

        $tpl->set('pluginsPageUrl', $this->router->generate('marketplace-plugins'));
        $tpl->set('themesPageUrl', $this->router->generate('marketplace-themes'));

        $stats = [
            'plugins_total' => count($allPlugins),
            'plugins_installed' => 0,
            'plugins_updates' => 0,
            'themes_total' => count($allThemes),
            'themes_installed' => 0,
        ];

        foreach ($allPlugins as $plugin) {
            $r = new MarketPlaceRessource(MarketPlaceRessource::TYPE_PLUGIN, $plugin);
            if ($r->isInstalled) {
                $stats['plugins_installed']++;
            }
            if ($r->updateNeeded()) {
                $stats['plugins_updates']++;
            }
        }

        foreach ($allThemes as $theme) {
            $r = new MarketPlaceRessource(MarketPlaceRessource::TYPE_THEME, $theme);
            if ($r->isInstalled) {
                $stats['themes_installed']++;
            }
        }

        $tpl->set('stats', $stats);

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

    public function migrateLegacyPlugins() {
        if (!$this->user->isAuthorized()) {
            $this->core->redirect($this->router->generate('admin-marketplace'));
        }
        $pending = $this->core->extensions()->getPendingLegacyPlugins();
        if (empty($pending)) {
            Show::msg(Lang::get('marketplace.legacy_plugins_none'), 'info');
            $this->core->redirect($this->router->generate('admin-marketplace'));
        }
        if (!function_exists('curl_init')) {
            Show::msg(Lang::get('marketplace.curl_not_installed'), 'error');
            $this->core->redirect($this->router->generate('admin-marketplace'));
        }
        $result = LegacyPluginsMigrator::install($pending, $this->marketManager);
        $failed = array_keys($result['failed']);
        $this->core->extensions()->savePendingLegacyPlugins($failed);
        if (empty($failed)) {
            Show::msg(Lang::get('marketplace.legacy_plugins_success'), 'success');
        } else {
            Show::msg(Lang::get('marketplace.legacy_plugins_partial', implode(', ', $failed)), 'error');
        }
        $this->core->redirect($this->router->generate('admin-marketplace'));
    }

}