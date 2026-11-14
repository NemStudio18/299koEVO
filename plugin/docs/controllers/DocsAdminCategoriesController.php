<?php

namespace Docs\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Responses\ApiResponse;
use Core\Responses\StringResponse;
use Docs\Entities\WikiCategoriesManager;
use Docs\Entities\WikiPageManager;

defined('ROOT') or exit('Access denied!');

class DocsAdminCategoriesController extends AdminController
{

    public WikiCategoriesManager $categoriesManager;

    public WikiPageManager $wikiPageManager;

    public function __construct() {
        parent::__construct();
        $this->categoriesManager = new WikiCategoriesManager();
        $this->wikiPageManager = new WikiPageManager();
    }

    public function addCategory() {
        $response = new ApiResponse();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('docs:admin'), $ctx)) {
            $response->status = ApiResponse::STATUS_NOT_AUTHORIZED;
            return $response;
        }
        
        $label = filter_var($this->jsonData['label'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $parentId = filter_var($this->jsonData['parentId'], FILTER_SANITIZE_NUMBER_INT) ?? 0;
        
        if (empty($label)) {
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
            return $response;
        }
        
        $this->categoriesManager->createCategory($label, $parentId);
        // Synchroniser automatiquement avec les pages
        $this->categoriesManager->syncWithWikiPages();
        
        $response->status = ApiResponse::STATUS_CREATED;
        return $response;
    }

    public function deleteCategory() {
        $response = new ApiResponse();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('docs:admin'), $ctx)) {
            $response->status = ApiResponse::STATUS_NOT_AUTHORIZED;
            return $response;
        }
        
        $id = (int) $this->jsonData['id'] ?? 0;
        
        if (!$this->categoriesManager->isCategoryExist($id)) {
            $response->status = ApiResponse::STATUS_NOT_FOUND;
            return $response;
        }
        
            if ($this->categoriesManager->deleteCategory($id)) {
            // Synchroniser automatiquement avec les pages
            $this->categoriesManager->syncWithWikiPages();
            $response->status = ApiResponse::STATUS_NO_CONTENT;
            } else {
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
        }
        
        return $response;
    }

    public function editCategory() {
        // Called By Fancybox
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('docs:admin'), $ctx)) {
            echo 'forbidden';
            die();
        }
        $response = new StringResponse();
        $tpl = $response->createPluginTemplate('docs', 'admin-edit-category');
        $id = (int) $_POST['id'] ?? 0;
        if (!$this->categoriesManager->isCategoryExist($id)) {
            echo 'dont exist';
            die();
        }
        $category = $this->categoriesManager->getCategory($id);

        $tpl->set('categoriesManager', $this->categoriesManager);
        $tpl->set('category', $category);
        $tpl->set('token', $this->user->token);
        $response->addTemplate($tpl);
        return $response;
    }

    public function saveCategory($id) {
        $response = new ApiResponse();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('docs:admin'), $ctx)) {
            $response->status = ApiResponse::STATUS_NOT_AUTHORIZED;
            return $response;
        }
        
        $label = filter_var($this->jsonData['label'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $parentId = (int)filter_var($this->jsonData['parentId'], FILTER_SANITIZE_NUMBER_INT) ?? 0;
        
        if (!$this->categoriesManager->isCategoryExist($id)) {
            $response->status = ApiResponse::STATUS_NOT_FOUND;
            return $response;
        }
        if ($parentId !== 0 && !$this->categoriesManager->isCategoryExist($parentId)) {
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
            return $response;
        }
        
        $category = $this->categoriesManager->getCategory($id);
        $category->parentId = $parentId;
        $category->label = $label;
        $this->categoriesManager->saveCategory($category);
        // Recharger les catégories pour mettre à jour les données
        $this->categoriesManager = new WikiCategoriesManager();
        
        $response->status = ApiResponse::STATUS_ACCEPTED;
        return $response;
    }

    public function getCategoriesList() {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('docs:admin'), $ctx)) {
            $this->core->error404();
        }
        
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('docs', 'categories-list');
        $tpl->set('categoriesManager', $this->categoriesManager);
        $tpl->set('catDisplay', 'root');
        $tpl->set('this', $this->categoriesManager); // pour compatibilité avec le template
        $tpl->set('token', $this->user->token);
        $response->addTemplate($tpl);
        return $response;
    }

}