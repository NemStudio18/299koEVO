<?php

namespace Core\Controllers;

use Core\Controllers\Controller;
use Core\Plugin\Plugin;
use Core\Plugin\PluginsManager;
use Core\Page\Controllers\PageController;
use Core\Auth\Controllers\UsersAdminController;
use Core\Page\Controllers\PageAdminController;
use Core\Settings\Controllers\ConfigManagerAdminController;
use Core\Media\Controllers\FileManagerAPIController;
use Core\Core;

/**
 * @copyright (C) 2023, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class CoreController extends Controller
{

    protected ?Plugin $defaultPlugin = null;
    protected ?string $defaultModule = null;

    public function __construct() {
        parent::__construct();
        $pluginName = $this->core->getPluginToCall();

        // Check if it's a core module first
        if ($this->core->isCoreModule($pluginName)) {
            $this->defaultModule = $pluginName;
        } elseif (PluginsManager::isActivePlugin($pluginName)) {
            $this->defaultPlugin = $this->pluginsManager->getPlugin($pluginName);
        } else {
            $this->core->error404();
        }
    }

    public function renderHome()
    {
        // Handle core modules
        if ($this->defaultModule === 'page') {
            $controller = new PageController();
            return $controller->home();
        }
        
        // Handle regular plugins
        if ($this->defaultPlugin && $this->defaultPlugin->getIsCallableOnPublic()) {
            $callback = $this->defaultPlugin->getCallablePublic();
            if (method_exists($callback[0], $callback[1])) {
                $obj = new $callback[0]();
                $response = call_user_func([$obj, $callback[1]]);
                return $response;
            }
        }
        Core::getInstance()->error404();
    }

    public function renderAdminHome()
    {
        // Handle core modules
        if ($this->defaultModule !== null) {
            // Redirect to the specific admin route for the core module
            $moduleName = $this->defaultModule;
            if ($moduleName === 'users') {
                $controller = new UsersAdminController();
                return $controller->home();
            } elseif ($moduleName === 'page') {
                $controller = new PageAdminController();
                return $controller->list();
            } elseif ($moduleName === 'configmanager') {
                $controller = new ConfigManagerAdminController();
                return $controller->home();
            } elseif ($moduleName === 'filemanager') {
                $controller = new FileManagerAPIController();
                return $controller->home();
            } elseif ($moduleName === 'marketplace' || $moduleName === 'pluginsmanager') {
                // These are handled by ExtensionsService
                $this->core->redirect($this->router->generate('admin') . $moduleName);
                return null;
            }
        }
        
        // Handle regular plugins
        if ($this->defaultPlugin && $this->defaultPlugin->getIsCallableOnAdmin()) {
            $callback = $this->defaultPlugin->getCallableAdmin();
            if (method_exists($callback[0], $callback[1])) {
                $obj = new $callback[0]();
                $response = call_user_func([$obj, $callback[1]]);
                return $response;
            }
        }
        Core::getInstance()->error404();
    }
}