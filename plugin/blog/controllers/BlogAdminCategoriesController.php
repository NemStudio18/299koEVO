<?php

use Core\Controllers\AdminController;
use Core\Responses\ApiResponse;
use Core\Responses\AdminResponse;
use Core\Responses\StringResponse;
use Blog\Entities\CategoriesManager;
use Blog\Entities\NewsManager;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('Access denied!');

class BlogAdminCategoriesController extends AdminController
{

    public CategoriesManager $categoriesManager;

    public NewsManager $newsManager;

    public function __construct()
    {
        parent::__construct();
        $this->categoriesManager = new CategoriesManager();
        $this->newsManager = new NewsManager();
    }

    public function addCategory()
    {
        $response = new ApiResponse();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('blog:admin'), $ctx)) {
            $response->status = ApiResponse::STATUS_NOT_AUTHORIZED;
            return $response;
        }
        $label = filter_var($this->jsonData['label'] ?? $_POST['label'] ?? $_POST['name'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $parentId = (int) ($this->jsonData['parentId'] ?? $_POST['parentId'] ?? 0);
        $this->categoriesManager->createCategory($label, $parentId);
        $response->status = ApiResponse::STATUS_CREATED;
        return $response;
    }

    public function deleteCategory()
    {
        // Called by Ajax
        $response = new ApiResponse();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('blog:admin'), $ctx)) {
            $response->status = ApiResponse::STATUS_NOT_AUTHORIZED;
            return $response;
        }
        $id = (int) $this->jsonData['id'] ?? 0;
        if ($this->categoriesManager->isCategoryExist($id)) {
            if ($this->categoriesManager->deleteCategory($id)) {
                $response->status = ApiResponse::STATUS_NO_CONTENT;
            } else {
                $response->status = ApiResponse::STATUS_NOT_FOUND;
            }
        }
        return $response;
    }

    public function editCategory()
    {
        // Called By Fancybox
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('blog:admin'), $ctx)) {
            echo 'forbidden';
            die();
        }
        $response = new StringResponse();
        $tpl = $response->createPluginTemplate('blog', 'admin-edit-category');
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

    public function saveCategory($id)
    {
        // Called by Ajax
        $response = new ApiResponse();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('blog:admin'), $ctx)) {
            $response->status = ApiResponse::STATUS_NOT_AUTHORIZED;
            return $response;
        }
        $label = filter_var($this->jsonData['label'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $parentId = (int) filter_var($this->jsonData['parentId'], FILTER_SANITIZE_NUMBER_INT) ?? 0;
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
        $response->status = ApiResponse::STATUS_ACCEPTED;
        return $response;
    }

    public function list()
    {
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('blog', 'admin-home');
        $tpl->set('activeTab', 'categories');
        $tpl->set('newsManager', $this->newsManager);
        $tpl->set('posts', $this->newsManager->getItems());
        $tpl->set('categoriesManager', $this->categoriesManager);
        $tpl->set('token', $this->user->token);
        $response->addTemplate($tpl);
        return $response;
    }

    public function listAjaxCategories()
    {
        $this->categoriesManager->outputAsList();
        die();
    }


}