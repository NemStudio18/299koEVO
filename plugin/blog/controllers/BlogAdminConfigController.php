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
            $itemsPerPage = intval($_REQUEST['items_per_page'] ?? ($_REQUEST['itemsByPage'] ?? 10));
            $this->runPlugin->setConfigVal('label', trim($_REQUEST['label'] ?? ''));
            $this->runPlugin->setConfigVal('items_per_page', max(1, $itemsPerPage));
            $this->runPlugin->setConfigVal('itemsByPage', max(1, $itemsPerPage));
            $this->runPlugin->setConfigVal('notify_admin_email', trim($_REQUEST['notify_admin_email'] ?? ''));
            $this->runPlugin->setConfigVal('hideContent', (isset($_POST['hideContent']) ? 1 : 0));
            $this->runPlugin->setConfigVal('comments', (isset($_POST['comments']) ? 1 : 0));
            $this->runPlugin->setConfigVal('displayTOC', filter_input(INPUT_POST, 'displayTOC', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '0');
            $this->runPlugin->setConfigVal('displayAuthor', (isset($_POST['displayAuthor']) ? 1 : 0));
            $this->runPlugin->setConfigVal('authorName', trim($_POST['authorName'] ?? ''));
            $this->runPlugin->setConfigVal('authorAvatar', trim($_POST['authorAvatar'] ?? ''));
            $authorBio = $_POST['authorBio'] ?? '';
            $this->runPlugin->setConfigVal('authorBio', $this->core->callHook('beforeSaveEditor', htmlspecialchars($authorBio)));
            if ($this->pluginsManager->savePluginConfig($this->runPlugin)) {
                Show::msg(Lang::get('core-changes-saved'), 'success');
            } else {
                Show::msg(Lang::get('core-changes-not-saved'), 'error');
            }
        }
        return $this->list();
    }
}