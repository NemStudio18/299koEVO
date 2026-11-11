<?php

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Utils\Show;
use Core\Lang;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('Access denied!');

class BlogAdminConfigController extends AdminController {

    public function list() {
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('blog', 'admin-home');
        $tpl->set('activeTab', 'config');
        $newsManager = new \Blog\Entities\NewsManager();
        $tpl->set('newsManager', $newsManager);
        $tpl->set('posts', $newsManager->getItems());
        $tpl->set('categoriesManager', new \Blog\Entities\CategoriesManager());
        $tpl->set('token', $this->user->token);
        $response->addTemplate($tpl);
        return $response;
    }

    public function saveConfig() {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if ($evaluator->canAccess(new \Core\Navigation\GenericResource('blog:admin'), $ctx)) {
            $this->runPlugin->setConfigVal('label', trim($_REQUEST['label']));
            $this->runPlugin->setConfigVal('items_per_page', trim(intval($_REQUEST['itemsByPage'] ?: $_REQUEST['items_per_page'])));
            $this->runPlugin->setConfigVal('notify_admin_email', trim($_REQUEST['notify_admin_email'] ?? ''));
            $this->runPlugin->setConfigVal('hideContent', (isset($_POST['hideContent']) ? 1 : 0));
            $this->runPlugin->setConfigVal('comments', (isset($_POST['comments']) ? 1 : 0));
            $this->runPlugin->setConfigVal('displayTOC', filter_input(INPUT_POST, 'displayTOC', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
            $this->runPlugin->setConfigVal('displayAuthor', (isset($_POST['displayAuthor']) ? 1 : 0));
            $this->runPlugin->setConfigVal('authorName', trim($_POST['authorName']));
            $this->runPlugin->setConfigVal('authorAvatar', trim($_POST['authorAvatar']));
            $this->runPlugin->setConfigVal('authorBio', $this->core->callHook('beforeSaveEditor',htmlspecialchars($_POST['authorBio'])));
            if ($this->pluginsManager->savePluginConfig($this->runPlugin)) {
                Show::msg(Lang::get('core-changes-saved'), 'success');
            } else {
                Show::msg(Lang::get('core-changes-not-saved'), 'error');
            }            
        }
        // Open the posts list
        $controller = new BlogAdminPostsController();
        return $controller->list();
    }
}