<?php

namespace Core\Controllers;

use Core\Auth\User;
use Core\Auth\UsersManager;
use Core\Controllers\Controller;
use Core\Plugin\Plugin;
use Core\Plugin\PluginsManager;
use Utils\Show;
use Core\Lang;
use Core\Router\Router;
use Core\Router\RouterResponse;

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
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $currentUser = UsersManager::getCurrentUser();
        $ctx = new \Core\Navigation\ConditionContext($currentUser);
        $isAdmin = $evaluator->isAdmin($ctx);
        
        if (!$isAdmin) {
            $this->core->error404();
        }
        $pluginName = $this->core->getPluginToCall();
        if (PluginsManager::isActivePlugin($pluginName)) {
            $this->runPlugin = $this->pluginsManager->getPlugin($pluginName);
            // Normaliser le nom du plugin vers sa casse réelle
            if ($this->runPlugin) {
                $pluginName = $this->runPlugin->getName();
            }
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

        $this->user = UsersManager::getCurrentUser();

        // Second check with feedback (redundant for safety but provides UI feedback)
        if (!$evaluator->isAdmin(new \Core\Navigation\ConditionContext($this->user))) {
            Show::msg(Lang::get('permissions.denied'), 'error');
            $this->core->redirect($this->router->generate('login'));
        }
    }

    protected function requirePermission(string $permission): void
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        $resource = new \Core\Navigation\GenericResource('system:permission:' . $permission, [], [$permission => true]);

        if (!$evaluator->canAccess($resource, $ctx)) {
            Show::msg(Lang::get('permissions.denied'), 'error');
            $this->core->redirect($this->router->generate('admin'));
        }
    }
        
}