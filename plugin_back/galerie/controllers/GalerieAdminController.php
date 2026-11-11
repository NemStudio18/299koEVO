<?php

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Utils\Show;
use Core\Lang;
use Content\Editor;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class GalerieAdminController extends AdminController
{

    public function home()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('galerie:admin'), $ctx)) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            $this->core->redirect($this->router->generate('admin-home'));
            return;
        }
        $galerie = new galerie();
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('galerie', 'admin');

        // Determine active tab: GET > Session > Default
        $activeTab = 'images';
        if (isset($_GET['tab'])) {
            $activeTab = $_GET['tab'];
        } elseif (isset($_SESSION['galerie_admin_tab'])) {
            $activeTab = $_SESSION['galerie_admin_tab'];
        }
        
        $tpl->set('galerie', $galerie);
        $tpl->set('mode', 'list');
        $tpl->set('activeTab', $activeTab);
        $tpl->set('token', $this->user->token);
        $response->addTemplate($tpl);
        return $response;
    }

    public function edit() {
        $item = new galerieItem();
        return $this->renderEdit($item);
    }

    public function editId($id) {
        $galerie = new galerie();
        $item = $galerie->createItem($id);
        if ($item === false) {
            $this->core->error404();
        }
        return $this->renderEdit($item);
    }

    public function delete($id, $token) {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('galerie:admin'), $ctx)) {
            return $this->home();
        }
        $galerie = new galerie();
        $item = $galerie->createItem($id);
        if ($item !== false) {
            if ($galerie->delItem($item)) {
                Show::msg(Lang::get('core-item-deleted'), 'success');
            } else {
                Show::msg(Lang::get('core-item-not-deleted'), 'error');
            }
        } else {
            Show::msg(Lang::get('core-item-not-deleted'), 'error');
        }
        return $this->core->redirect(\Core\Router\Router::getInstance()->generate('admin-galerie-home'));
    }

    public function save() {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('galerie:admin'), $ctx)) {
            return $this->home();
        }
        $galerie = new galerie();
        $contentEditor = new Editor('galContent', '', Lang::get('galerie.content'));

        $item = ($_REQUEST['id']) ? $galerie->createItem($_REQUEST['id']) : new galerieItem();
        $item->setCategory($_REQUEST['category']);
        $item->setTitle($_REQUEST['title']);
        $item->setContent($contentEditor->getPostContent());
        $item->setDate($_REQUEST['date']);
        $item->setHidden((isset($_POST['hidden'])) ? 1 : 0);
        if ($galerie->saveItem($item)) {
            Show::msg(Lang::get('core-changes-saved'), 'success');
        } else {
            Show::msg(Lang::get('core-changes-not-saved'), 'error');
        }
        return $this->core->redirect(\Core\Router\Router::getInstance()->generate('admin-galerie-edit-id', ['id' => $item->getId()]));
    }

    public function saveConf() {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('galerie:admin'), $ctx)) {
            return $this->home();
        }
        $introEditor = new Editor('introduction', '', Lang::get('galerie.introduction'), true);
        $this->runPlugin->setConfigVal('label', trim($_POST['label']));
        $this->runPlugin->setConfigVal('order', trim($_POST['order']));
        $this->runPlugin->setConfigVal('introduction', $introEditor->getPostContent() );
        $this->runPlugin->setConfigVal('showTitles', (isset($_POST['showTitles']) ? 1 : 0));
        $this->runPlugin->setConfigVal('size', trim($_POST['size']));
        
        // Gallery Carousel settings
        $this->runPlugin->setConfigVal('carouselGalleryEnabled', (isset($_POST['carouselGalleryEnabled']) ? 1 : 0));
        $this->runPlugin->setConfigVal('carouselGalleryType', trim($_POST['carouselGalleryType']));
        $this->runPlugin->setConfigVal('carouselGallerySource', trim($_POST['carouselGallerySource']));
        $this->runPlugin->setConfigVal('carouselGalleryCategory', trim($_POST['carouselGalleryCategory']));

        // External Hook Carousel settings
        $this->runPlugin->setConfigVal('carouselHookEnabled', (isset($_POST['carouselHookEnabled']) ? 1 : 0));
        $this->runPlugin->setConfigVal('carouselHookType', trim($_POST['carouselHookType']));
        $this->runPlugin->setConfigVal('carouselHookSource', trim($_POST['carouselHookSource']));
        $this->runPlugin->setConfigVal('carouselHookCategory', trim($_POST['carouselHookCategory']));

        if ($this->pluginsManager->savePluginConfig($this->runPlugin)) {
            Show::msg(Lang::get('core-changes-saved'), 'success');
        } else {
            Show::msg(Lang::get('core-changes-not-saved'), 'error');
        }
        $activeTab = $_POST['activeTab'] ?? 'settings';
        $_SESSION['galerie_admin_tab'] = $activeTab;
        return $this->core->redirect($this->router->generate('admin-galerie-home'));
    }

    public function addCategory() {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('galerie:admin'), $ctx)) {
            return $this->home();
        }
        $galerie = new galerie();
        if ($galerie->saveCategory($_POST['name'], $_POST['parent'] ?? '')) {
            Show::msg(Lang::get('core-changes-saved'), 'success');
        } else {
            Show::msg(Lang::get('core-changes-not-saved'), 'error');
        }
        $_SESSION['galerie_admin_tab'] = 'categories';
        return $this->core->redirect(\Core\Router\Router::getInstance()->generate('admin-galerie-home'));
    }

    public function deleteCategory($id) {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('galerie:admin'), $ctx)) {
            return $this->home();
        }
        $galerie = new galerie();
        if ($galerie->deleteCategory($id)) {
            Show::msg(Lang::get('core-item-deleted'), 'success');
        } else {
            Show::msg(Lang::get('core-item-not-deleted'), 'error');
        }
        $_SESSION['galerie_admin_tab'] = 'categories';
        return $this->core->redirect(\Core\Router\Router::getInstance()->generate('admin-galerie-home'));
    }

    protected function renderEdit(galerieItem $item) {
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('galerie', 'admin');
        $contentEditor = new Editor('galContent', $item->getContent(), Lang::get('galerie.content'));

        $galerie = new galerie();
        if ($item->getDate() == '')
            $item->setDate('');
        
        $tpl->set('contentEditor', $contentEditor);
        $tpl->set('galerie', $galerie);
        $tpl->set('item', $item);
        $tpl->set('mode', 'edit');
        $tpl->set('token', $this->user->token);
        $response->addTemplate($tpl);
        return $response;
    }
}