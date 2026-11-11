<?php

namespace Forum\Controllers;

use Core\Controllers\AdminController;
use Core\Plugin\PluginsManager;
use Core\Responses\AdminResponse;
use Forum\Entities\CategoryManager;
use Core\Auth\User;
use Core\Security\Csrf;
use Utils\Show;

defined('ROOT') or exit('Access denied!');

class ForumAdminController extends AdminController
{
    private CategoryManager $categoryManager;

    public function __construct()
    {
        parent::__construct();
        $this->categoryManager = new CategoryManager();
    }

    public function home()
    {
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('forum', 'admin-home');

        // Récupérer la configuration du plugin
        $plugin = PluginsManager::getInstance()->getPlugin('forum');
        $config = $plugin->getConfig();
        $tpl->set('config', $config);
        $tpl->set('configSaveUrl', $this->router->generate('admin-forum-config-save'));

        $categories = $this->categoryManager->getAll();
        $pendingPostsEntities = \Forum\Entities\Post::query()
            ->where('is_approved', '=', 0)
            ->orderBy('created_at', 'DESC')
            ->get();

        $pendingPosts = [];
        foreach ($pendingPostsEntities as $post) {
            $data = $post->toArray();
            try {
                $data['created_at_formatted'] = date('d/m/Y à H:i', strtotime($post->created_at));
            } catch (\Exception $e) {
                $data['created_at_formatted'] = $post->created_at;
            }
            $topic = \Forum\Entities\Topic::findPK($post->topic_id);
            $data['topic_title'] = $topic ? (string)$topic->title : 'Sujet inconnu';
            $pendingPosts[] = $data;
        }

        $stats = [
            'categoriesCount' => \Forum\Entities\Category::query()->count(),
            'topicsCount' => \Forum\Entities\Topic::query()->count(),
            'postsCount' => \Forum\Entities\Post::query()->count(),
            'pendingCount' => \Forum\Entities\Post::query()->where('is_approved', '=', 0)->count()
        ];

        // Récupérer toutes les catégories avec leur hiérarchie pour le formulaire
        $allCategories = $this->categoryManager->getAllWithSubcategories();
        
        // Préparer les catégories avec leurs URLs et leur hiérarchie
        $categoriesPrepared = [];
        $parentCategories = [];
        foreach ($allCategories as $category) {
            $catData = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'description' => $category->getDescription(),
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
            
            // Ajouter les sous-catégories
            $subcategories = $category->getSubcategories();
            foreach ($subcategories as $subcategory) {
                $catData['subcategories'][] = [
                    'id' => $subcategory->getId(),
                    'name' => $subcategory->getName(),
                    'description' => $subcategory->getDescription(),
                    'topic_count' => $subcategory->getTopicCount(),
                    'post_count' => $subcategory->getPostCount(),
                    'editUrl' => $this->router->generate('admin-forum-categories-edit', ['id' => $subcategory->getId()]),
                    'deleteUrl' => $this->router->generate('admin-forum-categories-delete', ['id' => $subcategory->getId()]),
                ];
            }
            
            $categoriesPrepared[] = $catData;
            
            // Pour le select parent : seulement les catégories racine
            if ($category->getParentId() === null) {
                $parentCategories[] = [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                ];
            }
        }
        
        $tpl->set('csrfToken', Csrf::token());
        $tpl->set('categories', $categoriesPrepared);
        $tpl->set('parentCategories', $parentCategories);
        $tpl->set('pendingPosts', $pendingPosts);
        $tpl->set('stats', $stats);
        $tpl->set('categoryAddUrl', $this->router->generate('admin-forum-categories-add-send'));
        $tpl->set('moderationApproveUrl', $this->router->generate('admin-forum-moderate-approve'));
        $tpl->set('moderationDeleteUrl', $this->router->generate('admin-forum-moderate-delete'));
        $tpl->set('categoriesUrl', $this->router->generate('admin-forum-categories'));
        $tpl->set('moderationUrl', $this->router->generate('admin-forum-moderate'));
        $tpl->set('forumPublicUrl', $this->router->generate('forum-index'));
        
        // Récupérer tous les modérateurs (Désactivé car trop lourd sur de grandes bases de données)
        $moderators = [];
        /*
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext(null); 
        foreach (User::all() as $user) {
            $ctx = new \Core\Navigation\ConditionContext($user);
            if ($evaluator->canAccess(new \Core\Navigation\GenericResource('forum:moderate'), $ctx)) {
                $moderators[] = [
                    'id' => $user->attributes['id'],
                    'username' => $user->username(),
                    'email' => $user->attributes['email'] ?? '',
                    'status' => $user->status(),
                ];
            }
        }
        */
        $tpl->set('moderators', $moderators);
        
        // URL vers l'administration des utilisateurs
        try {
            $tpl->set('usersAdminUrl', $this->router->generate('users-admin-home'));
        } catch (\Exception $e) {
            $tpl->set('usersAdminUrl', '/admin/users');
        }
        
        $tpl->set('maintenanceUrl', $this->router->generate('admin-forum-maintenance'));

        $response->addTemplate($tpl);
        return $response;
    }

    public function saveConfig()
    {
        if ($this->request->getMethod() !== 'POST') {
            $this->core->redirect($this->router->generate('admin-forum-home'));
        }

        if (!Csrf::validate($this->request->post('_csrf'))) {
           Show::msg('Token CSRF invalide', 'error');
           $this->core->redirect($this->router->generate('admin-forum-home'));
        }

        $plugin = PluginsManager::getInstance()->getPlugin('forum');
        
        // Récupérer les valeurs du formulaire
        $forumBreadcrumbLabel = trim($this->request->post('forum_breadcrumb_label'));
        $forumTableHeaderLabel = trim($this->request->post('forum_table_header_label'));
        $postsPerPage = (int) $this->request->post('postsPerPage');
        $topicsPerPage = (int) $this->request->post('topicsPerPage');
        
        // Mettre à jour la configuration en mémoire
        // Si vide, on garde la valeur par défaut (on n'écrase pas avec vide)
        // Ou alors on autorise le vide ? Pour l'instant on exige une valeur.
        
        if (!empty($forumBreadcrumbLabel)) {
            $plugin->setConfigVal('forum_breadcrumb_label', $forumBreadcrumbLabel);
        }
        
        if (!empty($forumTableHeaderLabel)) {
            $plugin->setConfigVal('forum_table_header_label', $forumTableHeaderLabel);
        }
        
        if ($postsPerPage > 0) {
            $plugin->setConfigVal('postsPerPage', $postsPerPage);
        }
        
        if ($topicsPerPage > 0) {
            $plugin->setConfigVal('topicsPerPage', $topicsPerPage);
        }

        // Sauvegarder dans le fichier JSON via PluginsManager
        if (PluginsManager::getInstance()->savePluginConfig($plugin)) {
            Show::msg('Configuration enregistrée avec succès', 'success');
        } else {
            Show::msg('Erreur lors de l\'enregistrement de la configuration', 'error');
        }

        return $this->core->redirect($this->router->generate('admin-forum-home'));
    }
}
