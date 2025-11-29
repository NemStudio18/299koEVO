<?php

namespace Core\Controllers;

use Core\Auth\User;
use Core\Auth\UsersManager;
use Core\Controllers\Controller;
use Core\Plugin\Plugin;
use Core\Plugin\PluginsManager;
use Utils\Show;
use Core\Lang;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('Access denied!');

class AdminController extends Controller {

    /**
     * Current plugin instance
     * @var Plugin
     */
    protected Plugin $runPlugin;

    /**
     * Current User
     * @var User
     */
    protected User $user;

    public function __construct() {
        parent::__construct();
        if (IS_ADMIN === false) {
            $this->core->error404();
        }
        $pluginName = $this->core->getPluginToCall();
        if (PluginsManager::isActivePlugin($pluginName)) {
            $this->runPlugin = $this->pluginsManager->getPlugin($pluginName);
            // Charger les traductions du plugin
            if ($this->runPlugin) {
                $this->runPlugin->loadLangFile();
            }
        } elseif (
            !$this->core->isCoreModule($pluginName)
            && !$this->core->extensions()->isCoreAdminModule($pluginName)
            && !$this->core->settings()->isCoreAdminModule($pluginName)
            && !$this->core->media()->isCoreAdminModule($pluginName)
        ) {
            $this->core->error404();
        }

        if (!defined('ADMIN_MODE')) {
            define('ADMIN_MODE', true);
        }
        $this->user = UsersManager::getCurrentUser();

        if ($this->user === null || !$this->user->hasPermission('admin.access')) {
            Show::msg(Lang::get('permissions.denied'), 'error');
            $this->core->redirect($this->router->generate('login'));
        }
    }

    protected function requirePermission(string $permission): void
    {
        if (!$this->user->hasPermission($permission)) {
            Show::msg(Lang::get('permissions.denied'), 'error');
            $this->core->redirect($this->router->generate('admin'));
        }
    }
        
}