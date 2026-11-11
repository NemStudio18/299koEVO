<?php

namespace Blog\Entities;

use Content\Categories\CategoriesManager as CoreCategoriesManager;
use Core\Router\Router;
use Core\Lang;
use Core\Responses\AdminResponse;
use Core\Auth\UsersManager;

defined('ROOT') OR exit('Access denied!');

class CategoriesManager extends CoreCategoriesManager
{

    protected string $pluginId = 'blog';
    protected string $name = 'categories';
    protected string $className = 'Blog\Entities\Category';
    protected bool $nested = true;
    protected bool $chooseMany = true;

    public function getAddCategoryUrl(): string
    {
        return Router::getInstance()->generate('admin-blog-add-category');
    }

    public function getDeleteUrl(): string
    {
        return Router::getInstance()->generate('admin-blog-delete-category');
    }

    public function getAjaxDisplayListUrl(): string
    {
        return Router::getInstance()->generate('admin-blog-list-ajax-categories');
    }

    public function getEditUrl(): string
    {
        return Router::getInstance()->generate('admin-blog-edit-category');
    }

    public function outputAsList()
    {
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('blog', 'admin-categories-list');
        $tpl->set('categoriesManager', $this);
        $tpl->set('token', UsersManager::getCurrentUser()->token);
        echo '<section id="categories_panel">';
        echo '<header>' . Lang::get('blog-categories-management-title') . '</header>';
        echo $tpl->output();
        echo '</section>';
    }
}
