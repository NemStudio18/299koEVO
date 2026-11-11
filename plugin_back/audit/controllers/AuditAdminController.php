<?php

namespace Audit\Controllers;

use Core\Controllers\AdminController;
use Core\Lang;
use Core\Responses\AdminResponse;
use Core\Router\Router;
use Core\Navigation\AccessPolicyEvaluator;
use Core\Navigation\GenericResource;
use Core\Navigation\ConditionContext;
use Utils\Show;

defined('ROOT') OR exit('No direct script access allowed');

class AuditAdminController extends AdminController
{

    public function index()
    {
        // Security check
        $evaluator = AccessPolicyEvaluator::getInstance();
        $ctx = new ConditionContext($this->user);
        if (!$evaluator->canAccess(new GenericResource('audit:admin'), $ctx)) {
            // Typically only superadmin should access this tool
            if (!$this->user->isSuperAdmin()) {
                Show::msg(Lang::get('core-not-authorized'), 'error');
                return $this->core->redirect($this->router->generate('admin-home'));
            }
        }

        $this->runPlugin->setMainTitle('Audit Admin Panel');
        $this->runPlugin->setTitleTag('Audit Admin Panel');

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('audit', 'admin-index');
        
        // Get all routes
        $router = Router::getInstance();
        $allRoutes = $router->getRoutes();
        
        $adminRoutes = [];
        foreach ($allRoutes as $route) {
            // route[1] is the pattern/path
            // route[3] is the name
            if (strpos($route[1], '/admin') === 0 && strpos($route[1], '/audit') === false) {
                
                // Compile Test URL
                $testUrl = $route[1];
                
                // Replace parameters with '1' for testing
                // e.g. /admin/user/[i:id] -> /admin/user/1
                // This is crude but sufficient for general crash testing
                $testUrl = preg_replace('/\[[^\]]+\]/', '1', $testUrl);
                
                // Remove optional params if any (simple approach)
                $testUrl = str_replace('?', '', $testUrl); 

                $adminRoutes[] = [
                    'pattern' => $route[1],
                    'name' => $route[3] ?? 'unknown',
                    'testUrl' => $testUrl,
                ];
            }
        }

        $tpl->set('adminRoutes', $adminRoutes);
        $tpl->set('token', $this->user->token);

        $response->addTemplate($tpl);
        return $response;
    }
}
