<?php

namespace Ecommerce\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Responses\ApiResponse;
use Utils\Show;
use Core\Lang;
use Utils\Util;

defined('ROOT') or exit('Access denied!');

class EcommerceAdminOrdersController extends AdminController
{
    private $orderManager;

    public function __construct()
    {
        parent::__construct();
        $this->orderManager = new \Ecommerce\Entities\OrderManager();
    }

    public function list()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('ecommerce:admin'), $ctx)) {
            \Utils\Show::msg(\Core\Lang::get('core-not-authorized'), 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('ecommerce', 'admin-orders-list');
        
        $orders = $this->orderManager->getAll();
        $tpl->set('orders', $orders);
        $tpl->set('token', $this->user->token);
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function view()
    {
        $response = new AdminResponse();
        $id = (int)$this->router->getParam('id');
        
        $order = $this->orderManager->getById($id);
        if (!$order) {
            return $this->core->redirect('/admin/ecommerce/orders');
        }
        
        $tpl = $response->createPluginTemplate('ecommerce', 'admin-orders-view');
        $tpl->set('order', $order);
        $tpl->set('token', $this->user->token);
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function updateStatus()
    {
        $response = new ApiResponse();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('ecommerce:admin'), $ctx)) {
            $response->status = ApiResponse::STATUS_NOT_AUTHORIZED;
            return $response;
        }
        
        if (!Show::tokenCheck($_POST['token'] ?? '')) {
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
            $response->body = ['message' => Lang::get('core.bad-token')];
            return $response;
        }
        
        $id = (int)($this->router->getParam('id') ?? $_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        
        $allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($status, $allowedStatuses)) {
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
            $response->body = ['message' => Lang::get('ecommerce.invalid-status')];
            return $response;
        }
        
        if ($this->orderManager->updateStatus($id, $status)) {
            $response->status = ApiResponse::STATUS_SUCCESS;
            $response->body = ['message' => Lang::get('ecommerce.status-updated')];
        } else {
            $response->status = ApiResponse::STATUS_ERROR;
            $response->body = ['message' => Lang::get('ecommerce.status-update-error')];
        }
        
        return $response;
    }
}

