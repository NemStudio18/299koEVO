<?php

namespace Core\Page\Controllers;

use Core\Controllers\AdminController;
use Core\Page\Page;
use Core\Page\PageItem;
use Utils\Show;
use Core\Lang;
use Core\Responses\AdminResponse;
use Content\Editor;
use Utils\Util;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class PageAdminController extends AdminController {

    public function list() {
        $page = new Page();
        // Recherche des pages perdues
        $parents = [];
        $lost = '';
        foreach ($page->getItems() as $k => $v)
            if ((int) $v->getParent() == 0) {
                $parents[] = $v->getId();
            }
        foreach ($page->getItems() as $k => $v)
            if ((int) $v->getParent() > 0) {
                if (!in_array($v->getParent(), $parents))
                    $lost .= $v->getId() . ',';
            }
        // Suite...
        if (!$page->createHomepage() && $this->core->getConfigVal('defaultPlugin') === 'page') {
            Show::msg(Lang::get('page.no-homepage-defined'), 'warning');
        }

        $items = $page->getItems();
        $totalPages = count($items);
        $visiblePages = 0;
        $hiddenPages = 0;
        $parentPages = 0;
        foreach ($items as $item) {
            if ($item->getIsHidden()) {
                $hiddenPages++;
            } else {
                $visiblePages++;
            }
            if ((int) $item->getParent() === 0) {
                $parentPages++;
            }
        }
        $childPages = max($totalPages - $parentPages, 0);
        $lostIds = array_filter(array_map('trim', explode(',', $lost)));
        $lostCount = count($lostIds);

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('page', 'list');

        // Passer les traductions en JSON pour éviter les problèmes d'échappement dans le JavaScript
        $translations = [
            'savingOrder' => Lang::get('page.saving-order'),
            'orderSaved' => Lang::get('page.order-saved'),
            'orderNotSaved' => Lang::get('page.order-not-saved'),
            'dragToReorder' => Lang::get('page.drag-to-reorder')
        ];
        $tpl->set('translationsJson', json_encode($translations, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE));

        $tpl->set('token', $this->user->token);
        $tpl->set('page', $page);
        $tpl->set('lost', $lost);
        $tpl->set('lostCount', $lostCount);
        $tpl->set('pageStats', [
            'total' => $totalPages,
            'visible' => $visiblePages,
            'hidden' => $hiddenPages,
            'parents' => $parentPages,
            'children' => $childPages,
            'lost' => $lostCount,
        ]);
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function delete($id, $token) {
        if (!$this->user->isAuthorized()) {
            return $this->list();
        }
        $page = new Page();
        $pageItem = $page->create($id);
        if ($page->del($pageItem)) {
            Show::msg(Lang::get('core-item-deleted'), 'success');
        } else {
            Show::msg(Lang::get('core-item-not-deleted'), 'error');
        }
        return $this->list();
    }

    public function maintenance($id, $token) {
        $page = new Page();
        $ids = explode(',', $id);
        foreach ($ids as $k => $v) {
            if ($v != '') {
                $pageItem = $page->create($v);
                $page->del($pageItem);
            }
        }
        return $this->list();
    }

    public function pageUp($id, $token) {
        if (!$this->user->isAuthorized()) {
            return $this->list();
        }
        $page = new Page();
        $pageItem = $page->create($id);
        $newPos = $pageItem->getPosition() - 1.5;
        $pageItem->setPosition($newPos);
        $page->save($pageItem);
        $page = new Page();
        return $this->list();
    }

    public function pageDown($id, $token) {
        if (!$this->user->isAuthorized()) {
            return $this->list();
        }
        $page = new Page();
        $pageItem = $page->create($id);
        $newPos = $pageItem->getPosition() + 1.5;
        $pageItem->setPosition($newPos);
        $page->save($pageItem);
        $page = new Page();
        return $this->list();
    }

    public function new() {
        $page = new Page();
        $pageItem = new PageItem();
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('page', 'edit');

        $contentEditor = new Editor('pageContent', '', Lang::get('page.content'), true);
        $tpl->set('page', $page);
        $tpl->set('token', $this->user->token);
        $tpl->set('pageItem', $pageItem);
        $tpl->set('contentEditor', $contentEditor);
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function newLink() {
        $page = new Page();
        $pageItem = new PageItem();
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('page', 'edit-link');
        $pageItem->setTarget('url');
        $tpl->set('page', $page);
        $tpl->set('token', $this->user->token);
        $tpl->set('pageItem', $pageItem);
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function newParent() {
        $pageItem = new PageItem();
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('page', 'edit-parent');
        $pageItem->setTarget('parent');

        $tpl->set('token', $this->user->token);
        $tpl->set('pageItem', $pageItem);
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function edit($id) {
        $page = new Page();
        $pageItem = $page->create($id);
        if (!$pageItem) {
            return $this->list();
        }
        $response = new AdminResponse();
        if ($pageItem->targetIs() === 'url' || $pageItem->targetIs() === 'plugin') {
            $tpl = $response->createPluginTemplate('page', 'edit-link');
        } elseif ($pageItem->targetIs() === 'parent') {
            $tpl = $response->createPluginTemplate('page', 'edit-parent');
        } else {
            $tpl = $response->createPluginTemplate('page', 'edit');
            $contentEditor = new Editor('pageContent', $pageItem->getContent(), Lang::get('page.content'), true);
            $tpl->set('contentEditor', $contentEditor);
        }
        $tpl->set('page', $page);
        $tpl->set('token', $this->user->token);
        $tpl->set('pageItem', $pageItem);
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function saveOrder() {
        // Cette route est une API, toujours retourner du JSON
        header('Content-Type: application/json');
        
        if (!$this->user->isAuthorized()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            die();
        }
        
        // Vérifier le token CSRF (ne pas régénérer pour cette route non-critique)
        $csrfToken = $this->request->post('_csrf') ?? null;
        if (!\Core\Security\Csrf::validate($csrfToken, false)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            die();
        }
        
        $order = json_decode($this->request->post('order', ''), true);
        if (!is_array($order) || empty($order)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid order data']);
            die();
        }
        
        $page = new Page();
        if ($page->saveOrder($order)) {
            http_response_code(200);
            // Retourner le token actuel (non régénéré) pour que le client puisse le réutiliser
            echo json_encode([
                'success' => true, 
                'message' => Lang::get('page.order-saved'),
                'csrfToken' => \Core\Security\Csrf::token()
            ]);
            die();
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => Lang::get('page.order-not-saved')]);
            die();
        }
    }

    public function save() {
        if (!$this->user->isAuthorized()) {
            return $this->list();
        }
        
        // Vérifier le token CSRF
        $csrfToken = $this->request->post('_csrf') ?? null;
        if (!\Core\Security\Csrf::validate($csrfToken)) {
            Show::msg(Lang::get('core-csrf-invalid'), 'error');
            return $this->list();
        }
        
        $page = new Page();
        $contentEditor = new Editor('pageContent', '', Lang::get('page.content'), true);
        $imgId = (isset($_POST['delImg'])) ? '' : ($_REQUEST['imgId'] ?? '');
        if (isset($_FILES['file']['name']) && $_FILES['file']['name'] != '') {
            if ($this->pluginsManager->isActivePlugin('galerie')) {
                $galerie = new \galerie();
                $img = new \galerieItem(array('category' => ''));
                $img->setTitle($_POST['name'] . ' (' . Lang::get('galerie.featured-image') . ')');
                $img->setHidden(1);
                $galerie->saveItem($img);
                $imgId = $galerie->getLastId() . '.' . Util::getFileExtension($_FILES['file']['name']);
            }
        }
        // Récupérer ou créer la page
        $pageId = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($pageId > 0) {
            $pageItem = $page->create($pageId);
            if (!$pageItem) {
                // La page n'existe pas, créer une nouvelle
                $pageItem = new PageItem();
            }
        } else {
            $pageItem = new PageItem();
        }
        
        // Définir toutes les propriétés
        $pageItem->setName($_POST['name'] ?? '');
        $pageItem->setPosition($_POST['position'] ?? '');
        $pageItem->setIsHomepage((isset($_POST['isHomepage'])) ? 1 : 0);
        $pageItem->setContent($contentEditor->getPostContent());
        $pageItem->setFile($_POST['file'] ?? '');
        $pageItem->setIsHidden((isset($_POST['isHidden'])) ? 1 : 0);
        $pageItem->setMainTitle($_POST['mainTitle'] ?? '');
        $pageItem->setMetaDescriptionTag($_POST['metaDescriptionTag'] ?? '');
        $pageItem->setMetaTitleTag($_POST['metaTitleTag'] ?? '');
        $pageItem->setTarget($_POST['target'] ?? '');
        $pageItem->setTargetAttr($_POST['targetAttr'] ?? '_self');
        $pageItem->setNoIndex((isset($_POST['noIndex'])) ? 1 : 0);
        $pageItem->setParent($_POST['parent'] ?? '');
        $pageItem->setCssClass($_POST['cssClass'] ?? '');
        $pageItem->setImg($imgId);
        if (isset($_POST['_password']) && $_POST['_password'] != '')
            $pageItem->setPassword($_POST['_password']);
        if (isset($_POST['resetPassword']))
            $pageItem->setPassword('');
        
        $saveResult = $page->save($pageItem);
        
        if ($saveResult) {
            Show::msg(Lang::get('core-changes-saved'), 'success');
            // Rediriger vers l'édition de la page avec le router
            $this->core->redirect($this->router->generate('page-admin-edit', ['id' => $pageItem->getId()]));
        } else {
            Show::msg(Lang::get('core-changes-not-saved'), 'error');
            // Vérifier les logs pour plus de détails
            $this->logger->error('Page save failed. Check logs for details.');
            // Rediriger vers la liste en cas d'erreur
            $this->core->redirect($this->router->generate('page-admin-home'));
        }
        die();
    }
}