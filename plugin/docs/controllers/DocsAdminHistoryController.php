<?php

namespace Docs\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Responses\ApiResponse;
use Core\Lang;
use Docs\Entities\WikiPageManager;
use Utils\Show;

defined('ROOT') or exit('Access denied!');

class DocsAdminHistoryController extends AdminController
{

    public function showHistory($id)
    {
        $pageId = (int) $id;
        $wikiPageManager = new WikiPageManager();
        $page = $wikiPageManager->create($pageId);
        
        if (!$page) {
            Show::msg(Lang::get('wiki-item-dont-exist'), 'error');
            $this->core->redirect($this->router->generate('admin-docs-list'));
        }

        // Créer une nouvelle instance pour avoir l'historique à jour
        $historyManager = new WikiPageManager();
        $history = $historyManager->getPageHistory($pageId);

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('docs', 'admin-history');
        
        $tpl->set('page', $page);
        $tpl->set('history', $history);
        $tpl->set('wikiPageManager', $wikiPageManager);
        $tpl->set('token', $this->user->token);
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function showVersion($id, $version)
    {
        $pageId = (int) $id;
        $version = (int) $version;
        
        $wikiPageManager = new WikiPageManager();
        $page = $wikiPageManager->getPageVersion($pageId, $version);
        
        if (!$page) {
            $this->logger->log("Version not found: PageID $pageId, Version $version", "ERROR");
            Show::msg(Lang::get('wiki-version-not-found'), 'error');
            $this->core->redirect($this->router->generate('admin-docs-list'));
        }

        $currentPage = $wikiPageManager->create($pageId);
        
        // Préparer les données pour le template
        $currentPageId = $currentPage ? $currentPage->getId() : 0;
        $currentPageVersion = $currentPage ? $currentPage->getVersion() : 0;
        $canRestore = $currentPage && $version != $currentPageVersion;
        
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('docs', 'admin-version');
        
        $tpl->set('page', $page);
        $tpl->set('currentPage', $currentPage);
        $tpl->set('currentPageId', $currentPageId);
        $tpl->set('currentPageVersion', $currentPageVersion);
        $tpl->set('canRestore', $canRestore);
        $tpl->set('version', $version);
        $tpl->set('token', $this->user->token);
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function restoreVersion()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('docs:admin'), $ctx)) {
            $response = new ApiResponse();
            $response->status = ApiResponse::STATUS_NOT_AUTHORIZED;
            return $response;
        }
        
        // Lire les données JSON
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);
        
        if (!$data) {
            $response = new ApiResponse();
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
            $response->body = ['error' => 'Invalid JSON data'];
            return $response;
        }
        
        $pageId = (int) ($data['id'] ?? 0);
        $version = (int) ($data['version'] ?? 0);
        
        $this->logger->log("Restore version request: PageID $pageId, Version $version", "INFO");
        
        $wikiPageManager = new WikiPageManager();
        $oldPage = $wikiPageManager->getPageVersion($pageId, $version);
        
        if (!$oldPage) {
            $this->logger->log("Old version not found: PageID $pageId, Version $version", "ERROR");
            $response = new ApiResponse();
            $response->status = ApiResponse::STATUS_NOT_FOUND;
            $response->body = ['error' => Lang::get('wiki-version-not-found')];
            return $response;
        }

        $currentPage = $wikiPageManager->create($pageId);
        if ($currentPage === false) {
            $this->logger->log("Current page not found: PageID $pageId", "ERROR");
            $response = new ApiResponse();
            $response->status = ApiResponse::STATUS_NOT_FOUND;
            $response->body = ['error' => Lang::get('wiki-item-dont-exist')];
            return $response;
        }

        // Restaurer le contenu de l'ancienne version
        $currentPage->setContent($oldPage->getContent());
        $currentPage->setName($oldPage->getName());
        $currentPage->setIntro($oldPage->getIntro());
        $currentPage->setSEODesc($oldPage->getSEODesc());
        $currentPage->setModifiedBy($this->user->email);
        
        $changeDescription = Lang::get('wiki-restore-version') . ' ' . $version;
        
        $result = $wikiPageManager->saveWikiPage($currentPage, $changeDescription);
        
        $response = new ApiResponse();
        
        if ($result) {
            $this->logger->log("Version restored successfully: PageID $pageId, Version $version", "INFO");
            $response->status = ApiResponse::STATUS_ACCEPTED;
            $response->body = ['message' => Lang::get('wiki-version-restored')];
        } else {
            $this->logger->log("Failed to restore version: PageID $pageId, Version $version", "ERROR");
            $response->status = 'HTTP/1.1 500 Internal Server Error';
            $response->body = ['error' => Lang::get('wiki-version-restore-error')];
        }
        
        return $response;
    }

} 