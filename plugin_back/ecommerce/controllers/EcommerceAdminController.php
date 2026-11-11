<?php

namespace Ecommerce\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Plugin\PluginsManager;

defined('ROOT') or exit('Access denied!');

class EcommerceAdminController extends AdminController
{
    public function home()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('ecommerce:admin'), $ctx)) {
            \Utils\Show::msg(\Core\Lang::get('core-not-authorized'), 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('ecommerce', 'admin-home');
        
        $plugin = PluginsManager::getInstance()->getPlugin('ecommerce');
        $orderManager = new \Ecommerce\Entities\OrderManager();
        $productManager = new \Ecommerce\Entities\ProductManager();
        
        $orders = $orderManager->getAll();
        $products = $productManager->getAll();
        
        $stats = [
            'total_orders' => count($orders),
            'pending_orders' => count(array_filter($orders, fn($o) => $o->getStatus() === 'pending')),
            'total_products' => count($products),
            'active_products' => count(array_filter($products, fn($p) => $p->isActive())),
        ];
        
        $tpl->set('stats', $stats);
        $tpl->set('recentOrders', array_slice($orders, 0, 5));
        $tpl->set('token', $this->user->token);
        
        $response->addTemplate($tpl);
        return $response;
    }
}

