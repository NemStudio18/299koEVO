<?php

namespace Ecommerce\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;

defined('ROOT') or exit('Access denied!');

class EcommerceAdminCategoriesController extends AdminController
{
    private $categoryManager;

    public function __construct()
    {
        parent::__construct();
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
        $tpl = $response->createPluginTemplate('ecommerce', 'admin-categories-list');
        
        $categories = $this->categoryManager->getAll();
        $tpl->set('categories', $categories);
        $tpl->set('token', $this->user->token);
        
        $response->addTemplate($tpl);
        return $response;
    }
}

