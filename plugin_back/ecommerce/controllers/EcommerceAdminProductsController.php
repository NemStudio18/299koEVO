<?php

namespace Ecommerce\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Responses\ApiResponse;
use Utils\Show;
use Core\Lang;
use Utils\Util;

defined('ROOT') or exit('Access denied!');

class EcommerceAdminProductsController extends AdminController
{
    private $productManager;
    private $categoryManager;

    public function __construct()
    {
        parent::__construct();
        $this->productManager = new \Ecommerce\Entities\ProductManager();
        $this->categoryManager = new \Ecommerce\Entities\CategoryManager();
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
        $tpl = $response->createPluginTemplate('ecommerce', 'admin-products-list');
        
        $products = $this->productManager->getAll();
        $tpl->set('products', $products);
        $tpl->set('token', $this->user->token);
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function add()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('ecommerce:admin'), $ctx)) {
            \Utils\Show::msg(\Core\Lang::get('core-not-authorized'), 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('ecommerce', 'admin-products-edit');
        
        $categories = $this->categoryManager->getAll();
        $tpl->set('product', null);
        $tpl->set('categories', $categories);
        $tpl->set('token', $this->user->token);
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function addSend()
    {
        $response = new ApiResponse();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('ecommerce:admin'), $ctx)) {
            $response->status = ApiResponse::STATUS_NOT_AUTHORIZED;
            return $response;
        }
        
        if (!\Core\Security\Csrf::validate($_POST['token'] ?? '')) {
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
            $response->body = ['message' => Lang::get('core.bad-token')];
            return $response;
        }
        
        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'price' => (float)($_POST['price'] ?? 0),
            'stock' => (int)($_POST['stock'] ?? 0),
            'image' => $_POST['image'] ?? '',
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'active' => isset($_POST['active']) ? 1 : 0,
        ];
        
        if (empty($data['name']) || $data['price'] <= 0) {
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
            $response->body = ['message' => Lang::get('ecommerce.invalid-data')];
            return $response;
        }
        
        $product = $this->productManager->create($data);
        if ($product) {
            $response->status = ApiResponse::STATUS_SUCCESS;
            $response->body = [
                'message' => Lang::get('ecommerce.product-created'),
                'redirect' => Util::urlBuild('/admin/ecommerce/products')
            ];
        } else {
            $response->status = ApiResponse::STATUS_ERROR;
            $response->body = ['message' => Lang::get('ecommerce.product-create-error')];
        }
        
        return $response;
    }

    public function edit()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('ecommerce:admin'), $ctx)) {
            \Utils\Show::msg(\Core\Lang::get('core-not-authorized'), 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }
        $response = new AdminResponse();
        $id = (int)$this->router->getParam('id');
        
        $product = $this->productManager->getById($id);
        if (!$product) {
            return $this->core->redirect('/admin/ecommerce/products');
        }
        
        $tpl = $response->createPluginTemplate('ecommerce', 'admin-products-edit');
        $categories = $this->categoryManager->getAll();
        
        $tpl->set('product', $product);
        $tpl->set('categories', $categories);
        $tpl->set('token', $this->user->token);
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function editSend()
    {
        $response = new ApiResponse();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('ecommerce:admin'), $ctx)) {
            $response->status = ApiResponse::STATUS_NOT_AUTHORIZED;
            return $response;
        }
        
        if (!\Core\Security\Csrf::validate($_POST['token'] ?? '')) {
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
            $response->body = ['message' => Lang::get('core.bad-token')];
            return $response;
        }
        
        $id = (int)($this->router->getParam('id') ?? $_POST['id'] ?? 0);
        
        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'price' => (float)($_POST['price'] ?? 0),
            'stock' => (int)($_POST['stock'] ?? 0),
            'image' => $_POST['image'] ?? '',
            'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            'active' => isset($_POST['active']) ? 1 : 0,
        ];
        
        if ($this->productManager->update($id, $data)) {
            $response->status = ApiResponse::STATUS_SUCCESS;
            $response->body = [
                'message' => Lang::get('ecommerce.product-updated'),
                'redirect' => Util::urlBuild('/admin/ecommerce/products')
            ];
        } else {
            $response->status = ApiResponse::STATUS_ERROR;
            $response->body = ['message' => Lang::get('ecommerce.product-update-error')];
        }
        
        return $response;
    }

    public function delete($id)
    {
        $response = new ApiResponse();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('ecommerce:admin'), $ctx)) {
            $response->status = ApiResponse::STATUS_NOT_AUTHORIZED;
            return $response;
        }
        
        $id = (int)$id; // Use the passed $id parameter
        
        if ($this->productManager->delete($id)) {
            $response->status = ApiResponse::STATUS_SUCCESS;
            $response->body = ['message' => Lang::get('ecommerce.product-deleted')];
        } else {
            $response->status = ApiResponse::STATUS_ERROR;
            $response->body = ['message' => Lang::get('ecommerce.product-delete-error')];
        }
        
        return $response;
    }
}

