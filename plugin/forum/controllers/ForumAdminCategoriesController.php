<?php

namespace Forum\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Utils\Show;
use Utils\Util;
use Core\Lang;
use Core\Security\Csrf;

defined('ROOT') or exit('Access denied!');

class ForumAdminCategoriesController extends AdminController
{
    private $categoryManager;

    public function __construct()
    {
        parent::__construct();
        $this->categoryManager = new \Forum\Entities\CategoryManager();
    }

    public function list()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('forum:admin'), $ctx)) {
            Show::msg('Non autorisé', 'error');
            return $this->core->redirect($this->router->generate('admin-home'));
        }
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('forum', 'admin-categories-list');
        
        // Récupérer toutes les catégories avec leur hiérarchie
        $rootCategories = $this->categoryManager->getAllWithSubcategories();
        
        // Préparer les catégories récursivement
        $categoriesPrepared = [];
        foreach ($rootCategories as $category) {
            $categoriesPrepared[] = $this->prepareCategoryData($category);
        }
        
        $tpl->set('categories', $categoriesPrepared);
        $tpl->set('csrfToken', Csrf::token());
        $tpl->set('addUrl', $this->router->generate('admin-forum-categories-add'));
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function add()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('forum:admin'), $ctx)) {
            Show::msg('Non autorisé', 'error');
            return $this->core->redirect($this->router->generate('admin-home'));
        }
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('forum', 'admin-category-form');
        
        // Récupérer toutes les catégories dans une liste à plat pour le select parent
        $rootCategories = $this->categoryManager->getAllWithSubcategories();
        $flatCategories = $this->flattenCategories($rootCategories);
        
        $parentCategories = [];
        foreach ($flatCategories as $catItem) {
            // On permet une profondeur plus grande pour éviter de bloquer la sélection
            if ($catItem['level'] <= 10) {
                $parentCategories[] = [
                    'id' => $catItem['id'],
                    'name' => $catItem['name'],
                ];
            }
        }
        
        $tpl->set('category', null);
        $tpl->set('parentCategories', $parentCategories);
        $tpl->set('title', Lang::get('forum.category.add'));
        $tpl->set('actionUrl', $this->router->generate('admin-forum-categories-add-send'));
        $tpl->set('cancelUrl', $this->router->generate('admin-forum-categories'));
        $tpl->set('csrfToken', Csrf::token());
        $response->addTemplate($tpl);
        return $response;
    }

    public function addSend()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('forum:admin'), $ctx) || !Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect('/admin/forum/categories');
        }

        $data = $this->extractCategoryData($_POST);
        if ($data === null) {
            Show::msg(Lang::get('forum.category.invalid'), 'error');
            return $this->core->redirect('/admin/forum/categories/add');
        }

        if ($this->categoryManager->create($data)) {
            Show::msg(Lang::get('forum.category.created'), 'success');
        } else {
            Show::msg(Lang::get('forum.category.save-error'), 'error');
        }

        return $this->core->redirect('/admin/forum/categories');
    }

    public function edit()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('forum:admin'), $ctx)) {
            Show::msg('Non autorisé', 'error');
            return $this->core->redirect($this->router->generate('admin-home'));
        }
        $id = (int)$this->router->getParam('id');
        $category = $this->categoryManager->getById($id);
        if (!$category) {
            Show::msg(Lang::get('forum.category.not-found'), 'error');
            return $this->core->redirect('/admin/forum/categories');
        }

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('forum', 'admin-category-form');
        
        // Récupérer toutes les catégories dans une liste à plat pour le select parent
        $rootCategories = $this->categoryManager->getAllWithSubcategories();
        $flatCategories = $this->flattenCategories($rootCategories);
        
        $parentCategories = [];
        foreach ($flatCategories as $catItem) {
            // On peut être parent si on est Niveau 1 ou Niveau 2 (pour créer un Niveau 3)
            // Et bien sûr, ce ne doit pas être la catégorie en édition (pour éviter les boucles)
            if ($catItem['level'] <= 2 && $catItem['id'] !== $id) {
                $parentCategories[] = [
                    'id' => $catItem['id'],
                    'name' => $catItem['name'],
                ];
            }
        }
        
        $tpl->set('category', $category);
        $tpl->set('parentCategories', $parentCategories);
        $tpl->set('title', Lang::get('forum.category.edit'));
        $tpl->set('actionUrl', $this->router->generate('admin-forum-categories-edit-send', ['id' => $id]));
        $tpl->set('cancelUrl', $this->router->generate('admin-forum-categories'));
        $tpl->set('csrfToken', Csrf::token());
        $response->addTemplate($tpl);
        return $response;
    }

    public function editSend()
    {
        $id = (int)$this->router->getParam('id');

        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('forum:admin'), $ctx) || !Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect('/admin/forum/categories');
        }

        $category = $this->categoryManager->getById($id);
        if (!$category) {
            Show::msg(Lang::get('forum.category.not-found'), 'error');
            return $this->core->redirect('/admin/forum/categories');
        }

        $data = $this->extractCategoryData($_POST, false);
        if ($data === null) {
            Show::msg(Lang::get('forum.category.invalid'), 'error');
            return $this->core->redirect($this->router->generate('admin-forum-categories-edit', ['id' => $id]));
        }

        if ($this->categoryManager->update($id, $data)) {
            Show::msg(Lang::get('forum.category.updated'), 'success');
        } else {
            Show::msg(Lang::get('forum.category.save-error'), 'error');
        }

        return $this->core->redirect('/admin/forum/categories');
    }

    public function delete()
    {
        $id = (int)$this->router->getParam('id');
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('forum:admin'), $ctx) || !Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect('/admin/forum/categories');
        }

        $category = $this->categoryManager->getById($id);
        if (!$category) {
            Show::msg(Lang::get('forum.category.not-found'), 'error');
            return $this->core->redirect('/admin/forum/categories');
        }

        // Check for subcategories
        $subcategories = $this->categoryManager->getSubcategories($id);
        if (!empty($subcategories)) {
            Show::msg('Impossible de supprimer une catégorie contenant des sous-catégories. Veuillez d\'abord supprimer ou déplacer les sous-catégories.', 'error');
            return $this->core->redirect('/admin/forum/categories');
        }

        // Check for topics (getById hydrates the counts safely)
        if ($category->getTopicCount() > 0) {
            Show::msg('Impossible de supprimer une catégorie contenant des sujets. Veuillez d\'abord supprimer ou déplacer les sujets.', 'error');
            return $this->core->redirect('/admin/forum/categories');
        }

        if ($this->categoryManager->delete($id)) {
            Show::msg(Lang::get('forum.category.deleted'), 'success');
        } else {
            Show::msg(Lang::get('forum.category.delete-error'), 'error');
        }

        return $this->core->redirect('/admin/forum/categories');
    }

    private function extractCategoryData(array $input, bool $slugOptional = true): ?array
    {
        $name = trim($input['name'] ?? '');
        if ($name === '') {
            return null;
        }

        $slug = trim($input['slug'] ?? '');
        if ($slug === '' && !$slugOptional) {
            $slug = Util::strToUrl($name);
        } elseif ($slug !== '') {
            $slug = Util::strToUrl($slug);
        }

        $data = [
            'name' => $name,
            'slug' => $slug ?: Util::strToUrl($name),
            'description' => trim($input['description'] ?? ''),
            'order_index' => (int)($input['order_index'] ?? 0),
        ];
        
        // Gérer parent_id (catégorie parent pour créer un sous-forum)
        if (isset($input['parent_id']) && $input['parent_id'] !== '' && $input['parent_id'] !== '0') {
            $data['parent_id'] = (int)$input['parent_id'];
        } else {
            $data['parent_id'] = null;
        }
        
        // Gérer icon et color
        if (isset($input['icon'])) {
            $data['icon'] = trim($input['icon'] ?? '');
        }
        if (isset($input['color'])) {
            $data['color'] = trim($input['color'] ?? '');
        }
        
        return $data;
    }

    /**
     * Prépare les données d'une catégorie et ses sous-catégories récursivement
     */
    private function prepareCategoryData(\Forum\Entities\Category $category): array
    {
        $data = [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getSlug(),
            'order_index' => $category->getOrderIndex(),
            'topic_count' => $category->getTopicCount(),
            'post_count' => $category->getPostCount(),
            'parent_id' => $category->getParentId(),
            'is_subcategory' => $category->isSubcategory(),
            'icon' => $category->getIcon(),
            'color' => $category->getColor(),
            'editUrl' => $this->router->generate('admin-forum-categories-edit', ['id' => $category->getId()]),
            'deleteUrl' => $this->router->generate('admin-forum-categories-delete', ['id' => $category->getId()]),
            'subcategories' => [],
        ];

        foreach ($category->getSubcategories() as $sub) {
            $data['subcategories'][] = $this->prepareCategoryData($sub);
        }

        return $data;
    }

    /**
     * Aplatit la hiérarchie des catégories pour le select parent
     */
    private function flattenCategories(array $categories, int $level = 0): array
    {
        $flat = [];
        foreach ($categories as $cat) {
            $prefix = str_repeat('↳ ', $level);
            $flat[] = [
                'id' => $cat->getId(),
                'name' => $prefix . $cat->getName(),
                'level' => $level + 1,
            ];
            
            $subcategories = $cat->getSubcategories();
            if (!empty($subcategories)) {
                $flat = array_merge($flat, $this->flattenCategories($subcategories, $level + 1));
            }
        }
        return $flat;
    }

    public function maintenance()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('forum:admin'), $ctx)) {
            Show::msg('Non autorisé', 'error');
            return $this->core->redirect($this->router->generate('admin-home'));
        }
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('forum', 'admin-maintenance');
        
        $topicManager = new \Forum\Entities\TopicManager();
        $postManager = new \Forum\Entities\PostManager();
        
        $orphanSubcats = $this->categoryManager->getOrphanSubcategories();
        $orphanTopics = $topicManager->getOrphanTopics();
        $orphanPosts = $postManager->getOrphanPosts();
        
        $tpl->set('orphanSubcatsCount', count($orphanSubcats));
        $tpl->set('orphanTopicsCount', count($orphanTopics));
        $tpl->set('orphanPostsCount', count($orphanPosts));
        
        $tpl->set('csrfToken', Csrf::token());
        $tpl->set('actionUrl', $this->router->generate('admin-forum-maintenance-run'));
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function runMaintenance()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('forum:admin'), $ctx) || !Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect($this->router->generate('admin-forum-maintenance'));
        }
        
        $topicManager = new \Forum\Entities\TopicManager();
        $postManager = new \Forum\Entities\PostManager();
        
        $orphanSubcats = $this->categoryManager->getOrphanSubcategories();
        $orphanTopics = $topicManager->getOrphanTopics();
        $orphanPosts = $postManager->getOrphanPosts();
        
        $recoverCount = 0;
        
        if (!empty($orphanSubcats) || !empty($orphanTopics) || !empty($orphanPosts)) {
            // 1. Create Recovery Category if needed
            $recoveryCatSlug = 'corbeille-recuperation';
            
            $allCats = $this->categoryManager->getAll();
            $recoveryCat = null;
            foreach ($allCats as $cat) {
                 if ($cat->getSlug() === $recoveryCatSlug) {
                     $recoveryCat = $cat;
                     break;
                 }
            }
            
            if (!$recoveryCat) {
                $recoveryCat = $this->categoryManager->create([
                    'name' => 'Corbeille / Récupération',
                    'slug' => $recoveryCatSlug,
                    'description' => 'Catégorie de récupération automatique des éléments orphelins.',
                    'order_index' => 999,
                    'icon' => 'fa-solid fa-recycle',
                    'color' => 'var(--evo-text-muted)'
                ]);
            }
            
            if ($recoveryCat) {
                // 2. Recover Subcats
                if (!empty($orphanSubcats)) {
                    $this->categoryManager->recoverOrphans($recoveryCat->getId());
                    $recoverCount += count($orphanSubcats);
                }
                
                // 3. Recover Topics
                if (!empty($orphanTopics)) {
                    $topicManager->recoverOrphans($recoveryCat->getId());
                    $recoverCount += count($orphanTopics);
                }
                
                // 4. Recover Posts
                if (!empty($orphanPosts)) {
                    // Check if Recovery Topic exists
                    $recoveryTopicSlug = 'repechage-de-posts';
                    $topics = $topicManager->getByCategory($recoveryCat->getId(), 1, 100); // Pagination 1, 100 assumed safe
                    $recoveryTopic = null;
                    if (is_array($topics)) {
                        foreach ($topics as $t) {
                            if ($t->getSlug() === $recoveryTopicSlug) {
                                $recoveryTopic = $t;
                                break;
                            }
                        }
                    }
                    
                    if (!$recoveryTopic) {
                        $recoveryTopic = $topicManager->create([
                            'category_id' => $recoveryCat->getId(),
                            'title' => 'Repêchage des posts',
                            'slug' => $recoveryTopicSlug,
                            'author_name' => 'Système',
                            'author_id' => $this->user->attributes['id']
                        ]);
                    }
                    
                    if ($recoveryTopic) {
                         $postManager->recoverOrphans($recoveryTopic->getId());
                         $recoverCount += count($orphanPosts);
                    }
                }
                
                Show::msg("$recoverCount éléments ont été restaurés dans la catégorie Corbeille.", 'success');
            } else {
                 Show::msg("Erreur lors de la création de la catégorie de récupération.", 'error');
            }
        } else {
            Show::msg("Aucun élément orphelin à restaurer.", 'info');
        }
        
        return $this->core->redirect($this->router->generate('admin-forum-maintenance'));
    }
}

