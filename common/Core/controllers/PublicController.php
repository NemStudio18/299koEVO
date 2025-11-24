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

class PublicController extends Controller {

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

    public function __construct() {
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
            // Not found
            $this->core->error404();
        }
        if (!defined('ADMIN_MODE')) {
            define('ADMIN_MODE', false);
        }
        $this->user = UsersManager::getCurrentUser() ? UsersManager::getCurrentUser() : null;
    }

}