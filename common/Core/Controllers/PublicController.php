<?php

namespace Core\Controllers;

use Core\Controllers\Controller;
use Core\Plugin\Plugin;
use Core\Auth\User;
use Core\Plugin\PluginsManager;
use Core\Auth\UsersManager;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('Access denied!');

class PublicController extends Controller
{

    /**
     * Current plugin instance (null for core modules)
     * @var Plugin|null
     */
    protected ?Plugin $runPlugin = null;

    /**
     * Current User
     * @var User
     */
    protected ?User $user;

    public function __construct()
    {
        parent::__construct();
        $pluginName = $this->core->getPluginToCall();
        // Check if it's a core module first
        if ($this->core->isCoreModule($pluginName)) {
            // Core module - no plugin instance needed
            $this->runPlugin = null;
        } elseif (PluginsManager::isActivePlugin($pluginName)) {
            // Regular plugin
            $this->runPlugin = $this->pluginsManager->getPlugin($pluginName);
        } else {
            // Plugin not found - but don't error404() here if we're handling a route
            // that already matched (like the short URL route /nomdupoi)
            // The route matching happens before this, so if we got here, the route matched
            // and we should allow the controller method to execute
            // Only error404() if we're not in a matched route context
            $router = \Core\Router\Router::getInstance();
            $match = $router->match();
            if (!is_array($match)) {
                // No route matched, this is a real 404
                $this->core->error404();
                return;
            }
            // Route matched, allow execution even if plugin name doesn't match
            // This handles cases like /nomdupoi where the route matches but plugin name doesn't
            $this->runPlugin = null;
        }
        if (!defined('ADMIN_MODE')) {
            define('ADMIN_MODE', false);
        }

        // Mode Simulation : Forcer la réinitialisation du contexte utilisateur
        // pour que les plugins voient les droits simulés (claims, etc.)
        $testMode = \Core\Auth\AdminTestMode::getInstance();
        if ($testMode->isActive()) {
            $auth = \Core\Core::getInstance()->auth();
            $auth->clearUserCache();
            \Core\Auth\CurrentUser::get()->initialize(true);
        }

        $this->user = UsersManager::getCurrentUser() ? UsersManager::getCurrentUser() : null;
        \Template\Template::addGlobal('isLogged', $this->user !== null);
    }

}