<?php

namespace Docs\Controllers;

use Core\Controllers\AdminController;
use Core\Lang;
use Core\Responses\AdminResponse;
use Core\Responses\ApiResponse;
use Core\Router\Router;
use Content\Editor;
use Docs\Entities\WikiActivityManager;
use Docs\Entities\WikiCategoriesManager;
use Docs\Entities\WikiHistoryManager;
use Docs\Entities\WikiPage;
use Docs\Entities\WikiPageManager;
use Utils\Show;
use Utils\Util;

defined('ROOT') or exit('Access denied!');

class DocsAdminPagesController extends AdminController
{

    public WikiCategoriesManager $categoriesManager;

    public WikiPageManager $wikiPageManager;

    public function __construct()
    {
        parent::__construct();
        $this->categoriesManager = new WikiCategoriesManager();
        $this->wikiPageManager = new WikiPageManager();
    }

    public function list()
    {
        // Synchroniser automatiquement les catégories avec les pages
        $this->categoriesManager->syncWithWikiPages();
        
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('docs', 'admin-list');

        $tpl->set('wikiPageManager', $this->wikiPageManager);
        $tpl->set('categoriesManager', $this->categoriesManager);
        $tpl->set('token', $this->user->token);
        $tpl->set('runPlugin', $this->runPlugin);

        $response->addTemplate($tpl);
        return $response;
    }

    public function deletePage()
    {
        $response = new ApiResponse();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('docs:admin'), $ctx)) {
            $response->status = ApiResponse::STATUS_NOT_AUTHORIZED;
            return $response;
        }
        
        $id = (int) $this->jsonData['id'] ?? 0;
        
        if ($id <= 0) {
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
            return $response;
        }
        
        $item = $this->wikiPageManager->create($id);
        if (!$item) {
            $response->status = ApiResponse::STATUS_NOT_FOUND;
            return $response;
        }
        
        $title = $item->getName();
        
        // Récupérer le nom de la catégorie avant suppression
        $categoryName = '';
        foreach ($this->categoriesManager->getCategories() as $category) {
            if (in_array($item->getId(), $category->items)) {
                $categoryName = $category->label;
                break;
            }
        }
        
        // Supprimer la page de toutes les catégories avant de supprimer la page
        $categoriesWhereItem = $this->categoriesManager->getCategoriesWhereItem($item->getId());
        if (!empty($categoriesWhereItem)) {
            $categoryIds = array_keys($categoriesWhereItem);
            WikiCategoriesManager::deleteItemFromCategories($item->getId(), $categoryIds);
        }
        
        if ($this->wikiPageManager->delWikiPage($item)) {
            // Enregistrer l'activité de suppression
            $activityManager = new WikiActivityManager();
            $activityManager->addActivity('delete', $title, $categoryName, $item->getId());
            
            $this->logger->log('info', $this->user->email . ' deleted docs page ' . $title);
            $response->status = ApiResponse::STATUS_NO_CONTENT;
        } else {
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
        }
        
        return $response;
    }

    public function editPage($id = false)
    {
        if ($id === false) {
            $wikiPage = new WikiPage();
            $wikiPage->setDate(date('Y-m-d'));
        } else {
            /** @var WikiPage $wikiPage */
            $wikiPage = $this->wikiPageManager->create($id);
            if ($wikiPage === false) {
                Show::msg(Lang::get('wiki-item-dont-exist'), 'error');
                $this->core->redirect($this->router->generate('admin-docs-list'));
            }
        }
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('docs', 'admin-edit');

        $contentEditor = new Editor('wikiContent', $wikiPage->getContent(), Lang::get('wiki-content'));
        
        $tpl->set('contentEditor', $contentEditor);
        $tpl->set('wikiPage', $wikiPage);
        $tpl->set('categoriesManager', $this->categoriesManager);
        $tpl->set('token', $this->user->token);

        $response->addTemplate($tpl);
        return $response;
    }

    public function savePage()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('docs:admin'), $ctx)) {
            return $this->list();
        }

        $contentEditor = new Editor('wikiContent', '', Lang::get('wiki-content'));

        $id = $this->request->post('id');
        if ($id) {
            /** @var WikiPage $wikiPageOrig */
            $wikiPageOrig = $this->wikiPageManager->create($id);
            if (!$wikiPageOrig) {
                Show::msg(Lang::get('wiki-item-dont-exist'), 'error');
                $this->core->redirect($this->router->generate('admin-docs-list'));
                return;
            }
            $wikiPage = clone $wikiPageOrig;
            $isNewPage = false;
        } else {
            $wikiPage = new WikiPage();
            $isNewPage = true;
        }
        
        // Validation : pour les pages existantes, le champ changeDescription est obligatoire
        if (!$isNewPage) {
            $changeDescription = trim($this->request->post('changeDescription') ?? '');
            if (empty($changeDescription)) {
                Show::msg(Lang::get('wiki-change-description-required'), 'error');
                $this->core->redirect($this->router->generate('admin-docs-edit-page', ['id' => $wikiPage->getId()]));
                return;
            }
        }
        
        $wikiPage->setName($this->request->post('name'));
        $wikiPage->setContent($contentEditor->getPostContent());
        $wikiPage->setIntro($this->core->callHook('beforeSaveEditor', htmlspecialchars($this->request->post('intro'))));
        $wikiPage->setSEODesc($this->request->post('seoDesc'));
        $wikiPage->setDraft((isset($_POST['draft']) ? 1 : 0));
        if (!isset($_REQUEST['date']) || $_REQUEST['date'] == "")
            $wikiPage->setDate($wikiPage->getDate());
        else
            $wikiPage->setDate($_REQUEST['date']);
        $wikiPage->setImg($this->request->post('wiki-page-image' , ''));
        
        // Gestion des versions et historique
        $changeDescription = $this->request->post('changeDescription') ?? '';
        $wikiPage->setModifiedBy($this->user->email);
        
        if ($isNewPage) {
            $wikiPage->setVersion(1);
        } else {
            // Le WikiPageManager s'occupera d'incrémenter la version et de sauvegarder l'historique
        }
        
        // Préparer les catégories avant la sauvegarde
        $choosenCats = [];
        if (isset($_POST['categoriesCheckbox'])) {
            foreach ($_POST['categoriesCheckbox'] as $cat) {
                $choosenCats[] = (int) $cat;
            }
        }
        $label = filter_input(INPUT_POST, 'category-add-label', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if ($label !== '') {
            $parentId = filter_input(INPUT_POST, 'category-add-parentId', FILTER_VALIDATE_INT) ?? 0;
            $choosenCats[] = $this->categoriesManager->createCategory($label, $parentId);
        }
        
        // Sauvegarder la page
        if ($this->wikiPageManager->saveWikiPage($wikiPage, $changeDescription)) {
            // Sauvegarder les associations dans le système de catégories
            WikiCategoriesManager::saveItemToCategories($wikiPage->getId(), $choosenCats);
            
            // Mettre à jour la propriété categories de la page (optionnel, pour l'affichage)
            $wikiPage->categories = ['categories' => []];
            foreach ($choosenCats as $catId) {
                $category = $this->categoriesManager->getCategory($catId);
                if ($category) {
                    $wikiPage->categories['categories'][$catId] = [
                        'label' => $category->label,
                        'url' => Router::getInstance()->generate('docs-category', ['name' => Util::strToUrl($category->label), 'id' => $catId]),
                        'id' => $catId
                    ];
                }
            }
            
            // Recharger les catégories pour mettre à jour les compteurs
            $this->categoriesManager = new WikiCategoriesManager();
            
            // Enregistrer l'activité
            $activityManager = new WikiActivityManager();
            $categoryName = '';
            if (!empty($choosenCats)) {
                    $category = $this->categoriesManager->getCategory($choosenCats[0]);
                if ($category) {
                    $categoryName = $category->label;
                }
            }
            
            if ($isNewPage) {
                $activityManager->addActivity('add', $wikiPage->getName(), $categoryName, $wikiPage->getId());
            } else {
                $activityManager->addActivity('edit', $wikiPage->getName(), $categoryName, $wikiPage->getId());
            }
            
            Show::msg(Lang::get('core-changes-saved'), 'success');
        } else {
            Show::msg(Lang::get('core-changes-not-saved'), 'error');
        }
        $this->core->redirect($this->router->generate('admin-docs-edit-page', ['id' => $wikiPage->getId()]));
    }

}