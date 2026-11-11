<?php

namespace Forum\Controllers;

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Core\Responses\ApiResponse;
use Utils\Show;
use Utils\Util;
use Core\Lang;
use Core\Core;
use Core\Logger;
use Core\Auth\UsersManager;
use Core\Router\Router;
use Core\Security\Csrf;
use Forum\Services\UserEnrichmentService;

defined('ROOT') or exit('Access denied!');

class ForumController extends PublicController
{
    private $categoryManager;
    private $topicManager;
    private $postManager;
    private $userEnrichment;
    private $config;
    private $forumBreadcrumbLabel;
    private $forumTableHeaderLabel;

    public function __construct()
    {
        parent::__construct();
        $this->categoryManager = new \Forum\Entities\CategoryManager();
        $this->topicManager = new \Forum\Entities\TopicManager();
        $this->postManager = new \Forum\Entities\PostManager();
        $this->userEnrichment = new UserEnrichmentService(Logger::getInstance());
        
        // Charger la configuration
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('forum');
        $this->config = $plugin->getConfig();
        $this->forumBreadcrumbLabel = !empty($this->config['forum_breadcrumb_label']) ? $this->config['forum_breadcrumb_label'] : (!empty($this->config['forum_home_label']) ? $this->config['forum_home_label'] : 'Forum');
        $this->forumTableHeaderLabel = !empty($this->config['forum_table_header_label']) ? $this->config['forum_table_header_label'] : (!empty($this->config['forum_home_label']) ? $this->config['forum_home_label'] : 'Forum');
    }

    private function resolveAuthor(array $record, array $enrichedUsers, bool $isAdmin): array
    {
        $authorId = $record['author_id'] ?? null;
        if ($authorId && isset($enrichedUsers[$authorId]) && !empty($enrichedUsers[$authorId]['has_cms_data'])) {
            $author = $enrichedUsers[$authorId];
            if (!$isAdmin && isset($author['email'])) {
                $author['email'] = \Utils\Util::maskEmail($author['email']);
            }
        } else {
            $fallbackName = trim((string)($record['author_name'] ?? ''));
            $author = [
                'id' => $authorId,
                'username' => $fallbackName !== '' ? $fallbackName : 'Anonyme',
                'email' => $isAdmin ? ($record['author_email'] ?? '') : \Utils\Util::maskEmail($record['author_email'] ?? ''),
                'avatar' => null,
                'has_mysql_data' => false,
            ];
        }

        if (isset($author['avatar'])) {
            $avatarValue = $author['avatar'];
            if ($avatarValue === '/avatar' || $avatarValue === 'avatar' || trim((string)$avatarValue) === '/avatar') {
                $author['avatar'] = null;
            }
        }
        return $this->enrichAuthorAvatar($author);
    }

    public function index()
    {
        $response = new PublicResponse();
        $response->setTitle('<i class="fa-solid fa-comments"></i> Forum');
        $tpl = $response->createPluginTemplate('forum', 'index');

        // Breadcrumb
        $breadcrumb = $this->buildBreadcrumb([
            ['label' => $this->forumBreadcrumbLabel, 'url' => '', 'is_active' => true],
        ]);
        $tpl->set('breadcrumb', $breadcrumb);
        $tpl->set('forumLabel', $this->forumBreadcrumbLabel); // Keep for back-compat if needed, but prefer specific vars
        $tpl->set('forumBreadcrumbLabel', $this->forumBreadcrumbLabel);
        $tpl->set('forumTableHeaderLabel', $this->forumTableHeaderLabel);

        // Récupérer les catégories avec leurs sous-catégories (récursif via Manager)
        $categories = $this->categoryManager->getAll();

        // Préparer les données récursivement
        $categoriesPrepared = [];
        foreach ($categories as $category) {
            $categoriesPrepared[] = $this->prepareCategoryDataPublic($category);
        }

        $tpl->set('categories', $categoriesPrepared);
        $tpl->set('ROUTER', $this->router);

        // Générer les URLs dans le contrôleur
        $tpl->set('searchUrl', $this->router->generate('forum-search'));
        $tpl->set('notificationsUrl', $this->router->generate('forum-notifications'));

        // Token pour les notifications
        $user = UsersManager::getCurrentUser();
        $tpl->set('token', $user ? Csrf::token() : null);

        $response->addTemplate($tpl);
        return $response;
    }

    public function category($id = null)
    {
        $response = new PublicResponse();
        // Si $id n'est pas passé en paramètre, le récupérer depuis le routeur
        if ($id === null) {
            $id = (int) $this->router->getParam('id');
        } else {
            $id = (int) $id;
        }

        $category = $this->categoryManager->getById($id);
        if (!$category) {
            return Core::getInstance()->redirect('/forum');
        }

        // Vérifier si c'est une catégorie parente (a des sous-catégories)
        $subcategories = $this->categoryManager->getSubcategories($id);
        $hasSubcategories = !empty($subcategories);

        // Si la catégorie a des sous-catégories, afficher les sous-catégories au lieu des topics
        // (sauf si c'est un admin qui peut créer des sujets directement)
        $user = UsersManager::getCurrentUser();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);
        $isAdmin = $evaluator->isAdmin($ctx);

        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('forum');
        $perPage = (int) $plugin->getConfigVal('topicsPerPage');
        $page = (int) ($this->router->getParam('page') ?? 1);

        // Récupérer les topics seulement si pas de sous-catégories ou si admin
        $topics = [];
        $topicsPrepared = [];
        $totalTopics = 0;
        $totalPages = 0;
        $paginationPages = [];

        if (!$hasSubcategories || $isAdmin) {
            // Récupérer les topics
            $topics = $this->topicManager->getByCategory($id, $page, $perPage);
            $totalTopics = $this->topicManager->countByCategory($id);
            $totalPages = max(1, ceil($totalTopics / $perPage));

            // Compter le total pour la pagination
            $totalTopics = $this->topicManager->countByCategory($id);
            $totalPages = max(1, ceil($totalTopics / $perPage));

            // Enrichir les topics avec les données utilisateurs
            $topicsPrepared = [];
            $userIds = [];
            foreach ($topics as $topic) {
                $topicData = $topic->toArray();
                if ($topicData['author_id']) {
                    $userIds[] = $topicData['author_id'];
                }
                $topicsPrepared[] = $topicData;
            }

            // Enrichir les utilisateurs depuis MySQL si possible
            $enrichedUsers = [];
            if (!empty($userIds)) {
                $enrichedUsers = $this->userEnrichment->enrichUsers($userIds);
            }

            // Ajouter les données utilisateurs enrichies aux topics
            foreach ($topicsPrepared as &$topicData) {
                $topicData['author'] = $this->resolveAuthor($topicData, $enrichedUsers, $isAdmin);

                // Formater les dates
                if (!empty($topicData['created_at'])) {
                    try {
                        $topicData['created_at_formatted'] = date('d/m/Y H:i', strtotime($topicData['created_at']));
                    } catch (\Exception $e) {
                        $topicData['created_at_formatted'] = $topicData['created_at'];
                    }
                } else {
                    $topicData['created_at_formatted'] = '';
                }

                if (!empty($topicData['last_post_at'])) {
                    try {
                        $topicData['last_post_at_formatted'] = date('d/m/Y H:i', strtotime($topicData['last_post_at']));
                    } catch (\Exception $e) {
                        $topicData['last_post_at_formatted'] = $topicData['last_post_at'];
                    }
                } else {
                    // Si pas de dernier post, utiliser la date de création
                    $topicData['last_post_at_formatted'] = $topicData['created_at_formatted'];
                }

                // Générer l'URL du topic
                try {
                    $topicData['url'] = $this->router->generate('forum-topic', ['id' => $topicData['id']]);
                } catch (\Exception $e) {
                    $topicData['url'] = '/forum/topic/' . $topicData['id'];
                }
            }

            // Préparer la pagination
            for ($i = 1; $i <= $totalPages; $i++) {
                try {
                    $pageUrl = $this->router->generate('forum-category', ['id' => $id, 'page' => $i]);
                } catch (\Exception $e) {
                    $pageUrl = '/forum/category/' . $id . '?page=' . $i;
                }
                $paginationPages[] = [
                    'number' => $i,
                    'url' => $pageUrl,
                    'is_current' => $i === $page,
                ];
            }
        }

        // Préparer les sous-catégories récursivement
        $subcategoriesPrepared = [];
        foreach ($subcategories as $subcat) {
            $subcategoriesPrepared[] = $this->prepareCategoryDataPublic($subcat);
        }

        $response->setTitle('<i class="fa-solid fa-folder"></i> ' . $category->getName() . ' - Forum');
        $tpl = $response->createPluginTemplate('forum', 'category');

        // Détecter si on doit afficher les sous-catégories comme des "Discussion Rooms" (Niveau 3)
        // C'est le cas si la catégorie ACTUELLE est de Niveau 2.
        $isLevel2 = $this->categoryManager->isLevel2($category);
        $tpl->set('isLevel3DiscussionRooms', $isLevel2);

        $categoryData = $category->toArray();

        // Breadcrumb - DOIT être défini AVANT tout autre set() pour être disponible dans le template inclus
        // Récupérer le chemin complet de la catégorie (tous les parents)
        $breadcrumbItems = [
            ['label' => $this->forumBreadcrumbLabel, 'url' => $this->router->generate('forum-index')],
        ];

        $categoryPath = $this->categoryManager->getCategoryPath($id);
        foreach ($categoryPath as $cat) {
            // La dernière catégorie du chemin est la catégorie actuelle (sans URL = actif)
            if ($cat->getId() === $id) {
                $breadcrumbItems[] = ['label' => $cat->getName(), 'url' => ''];
            } else {
                try {
                    $categoryUrl = $this->router->generate('forum-category', ['id' => $cat->getId()]);
                } catch (\Exception $e) {
                    $categoryUrl = '/forum/category/' . $cat->getId();
                }
                $breadcrumbItems[] = ['label' => $cat->getName(), 'url' => $categoryUrl];
            }
        }

        $breadcrumb = $this->buildBreadcrumb($breadcrumbItems);
        // Définir le breadcrumb EN PREMIER pour qu'il soit disponible dans le template inclus
        $tpl->set('breadcrumb', $breadcrumb);
        $tpl->set('forumLabel', $this->forumBreadcrumbLabel);
        $tpl->set('forumTableHeaderLabel', $this->forumTableHeaderLabel);

        $tpl->set('category', $categoryData);
        // Dernière vérification : forcer avatar à null si c'est /avatar pour tous les topics
        foreach ($topicsPrepared as &$topicData) {
            $authorId = $topicData['author']['id'] ?? '?';
            if (isset($topicData['author']['avatar']) && ($topicData['author']['avatar'] === '/avatar' || trim($topicData['author']['avatar']) === '/avatar')) {
                $topicData['author']['avatar'] = null;
                $topicData['author']['avatar_color'] = $this->generateAvatarColor($topicData['author']['username'] ?? '?');
                $topicData['author']['avatar_initials'] = $this->generateAvatarInitials($topicData['author']['username'] ?? '?');
            }
        }
        unset($topicData);

        $allowAnonymous = (bool) $plugin->getConfigVal('allowAnonymous');
        $canPostTopic = ($this->user !== null || $allowAnonymous) && (!$hasSubcategories || $isAdmin);

        $tpl->set('topics', $topicsPrepared);
        $tpl->set('subcategories', $subcategoriesPrepared);
        $tpl->set('hasSubcategories', $hasSubcategories);
        $tpl->set('canCreateTopic', !$hasSubcategories || $isAdmin);
        $tpl->set('canPostTopic', $canPostTopic);
        $tpl->set('isAdmin', $isAdmin);
        $tpl->set('token', ($this->user !== null || $allowAnonymous) ? Csrf::token() : null);
        $tpl->set('currentPage', $page);
        $tpl->set('totalPages', $totalPages);
        $tpl->set('paginationPages', $paginationPages);
        $tpl->set('hasPagination', $totalPages > 1);
        $tpl->set('ROUTER', $this->router);

        // URLs pour le formulaire
        try {
            $tpl->set('createTopicFormUrl', $this->router->generate('forum-topic-create-form') . '?category_id=' . $id);
            $tpl->set('createTopicUrl', $this->router->generate('forum-topic-create'));
        } catch (\Exception $e) {
            $tpl->set('createTopicFormUrl', '/forum/topic/create?category_id=' . $id);
            $tpl->set('createTopicUrl', '/forum/topic/create');
        }

        $response->addTemplate($tpl);
        return $response;
    }

    public function topic($id = null)
    {
        $response = new PublicResponse();
        // Si $id n'est pas passé en paramètre, le récupérer depuis le routeur
        if ($id === null) {
            $id = (int) $this->router->getParam('id');
        } else {
            $id = (int) $id;
        }

        // Marquer automatiquement les notifications liées à ce topic comme lues
        if ($this->user) {
            $userId = (int) $this->user->attributes['id'];
            if ($userId > 0) {
                $notifications = \Forum\Entities\ForumNotification::query()
                    ->where('user_id', '=', $userId)
                    ->where('topic_id', '=', $id)
                    ->where('is_read', '=', 0)
                    ->get();
                
                foreach ($notifications as $notification) {
                    $notification->is_read = 1;
                    $notification->save();
                }
            }
        }

        $topic = $this->topicManager->getById($id);
        if (!$topic) {
            return Core::getInstance()->redirect('/forum');
        }

        $this->topicManager->incrementViews($id);

        $page = (int) ($this->router->getParam('page') ?? 1);
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('forum');
        $perPage = (int) $plugin->getConfigVal('postsPerPage');

        $user = UsersManager::getCurrentUser();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);
        $isAdmin = $evaluator->isAdmin($ctx);
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);
        $isModerator = $evaluator->canAccess(new \Core\Navigation\GenericResource('forum:moderate'), $ctx);

        // Compter le total de posts pour la pagination (sans compter le premier post)
        $totalPosts = $this->postManager->countByTopic($id);
        // Le premier post n'est pas paginé, donc on soustrait 1 pour les réponses
        $totalReplies = max(0, $totalPosts - 1);
        $totalPages = max(1, ceil($totalReplies / $perPage));

        // Toujours récupérer le premier post séparément
        $currentUserId = $user ? (int) $user->attributes['id'] : null;
        $currentUserEmail = $user ? ($user->email ?? null) : null;
        $firstPostRaw = $this->postManager->getByTopic($id, 1, 1, $isAdmin, $currentUserId, $currentUserEmail);
        $firstPost = null;

        // Récupérer les réponses avec pagination (en excluant le premier post)
        $repliesRaw = $this->postManager->getRepliesByTopic($id, $page, $perPage, $isAdmin, $currentUserId, $currentUserEmail);

        // Enrichir les posts avec les données utilisateurs
        $userIds = [];
        if (!empty($firstPostRaw)) {
            $firstPostData = $firstPostRaw[0]->toArray();
            if ($firstPostData['author_id']) {
                $userIds[] = $firstPostData['author_id'];
            }
        }
        foreach ($repliesRaw as $post) {
            $postData = $post->toArray();
            if ($postData['author_id']) {
                $userIds[] = $postData['author_id'];
            }
        }

        // Enrichir les utilisateurs depuis MySQL si possible
        $enrichedUsers = [];
        if (!empty($userIds)) {
            $enrichedUsers = $this->userEnrichment->enrichUsers($userIds);
        }

        // Préparer le premier post
        if (!empty($firstPostRaw)) {
            $postData = $firstPostRaw[0]->toArray();
            $postData['author'] = $this->resolveAuthor($postData, $enrichedUsers, $isAdmin);

            // Formater les dates
            if ($postData['created_at']) {
                try {
                    $postData['created_at_formatted'] = date('d/m/Y à H:i', strtotime($postData['created_at']));
                } catch (\Exception $e) {
                    $postData['created_at_formatted'] = $postData['created_at'];
                }
            } else {
                $postData['created_at_formatted'] = '';
            }

            if (!empty($postData['updated_at']) && $postData['updated_at'] !== ($postData['created_at'] ?? '')) {
                try {
                    $postData['updated_at_formatted'] = date('d/m/Y à H:i', strtotime($postData['updated_at']));
                    $postData['edited_at_formatted'] = $postData['updated_at_formatted'];
                    $postData['has_update'] = true;
                    $postData['is_edited'] = true;
                } catch (\Exception $e) {
                    $postData['updated_at_formatted'] = $postData['updated_at'];
                    $postData['edited_at_formatted'] = $postData['updated_at'];
                    $postData['has_update'] = true;
                    $postData['is_edited'] = true;
                }
            } else {
                $postData['updated_at_formatted'] = '';
                $postData['edited_at_formatted'] = '';
                $postData['has_update'] = false;
                $postData['is_edited'] = false;
            }

            // Ajouter les flags pour le template
            $postData['is_approved'] = (bool) $postData['is_approved'];
            $postData['is_pending'] = !$postData['is_approved'];

            // Générer les URLs pour le premier post
            try {
                $postData['like_url'] = $this->router->generate('forum-post-like', ['id' => $postData['id']]);
                $postData['edit_url'] = $this->router->generate('forum-post-edit', ['id' => $postData['id']]);
            } catch (\Exception $e) {
                $postData['like_url'] = '/forum/post/like/' . $postData['id'];
                $postData['edit_url'] = '/forum/post/edit/' . $postData['id'];
            }

            // Vérifier si l'utilisateur peut éditer ce post
            // Règle : Admin/Modo OU (Auteur ET Dernier message du sujet)
            $canEditBasic = $this->postManager->canEdit($postData['id'], $currentUserId, $currentUserEmail);
            $isLastPost = $this->postManager->isLastPost($id, $postData['id']);
            $postData['can_edit'] = $isModerator || ($canEditBasic && $isLastPost);

            // Compteur et statut des likes sur le premier post
            $postData['like_count'] = $this->postManager->countLikes($postData['id']);
            $postData['is_liked_by_current_user'] = $this->postManager->isLikedBy($postData['id'], $currentUserId, $currentUserEmail);

            // Vérifier si l'auteur autorise les messages privés
            $postData['author_allows_private_messages'] = $this->userAllowsPrivateMessages($postData['author_id']);

            $firstPost = $postData;
        }

        // Préparer les réponses
        $replies = [];
        foreach ($repliesRaw as $post) {
            $postData = $post->toArray();
            $postData['author'] = $this->resolveAuthor($postData, $enrichedUsers, $isAdmin);

            // Formater les dates
            if ($postData['created_at']) {
                try {
                    $postData['created_at_formatted'] = date('d/m/Y à H:i', strtotime($postData['created_at']));
                } catch (\Exception $e) {
                    $postData['created_at_formatted'] = $postData['created_at'];
                }
            } else {
                $postData['created_at_formatted'] = '';
            }

            if (!empty($postData['updated_at']) && $postData['updated_at'] !== ($postData['created_at'] ?? '')) {
                try {
                    $postData['updated_at_formatted'] = date('d/m/Y à H:i', strtotime($postData['updated_at']));
                    $postData['has_update'] = true;
                } catch (\Exception $e) {
                    $postData['updated_at_formatted'] = $postData['updated_at'];
                    $postData['has_update'] = true;
                }
            } else {
                $postData['updated_at_formatted'] = '';
                $postData['has_update'] = false;
            }

            // Ajouter les flags pour le template
            $postData['is_approved'] = (bool) $postData['is_approved'];
            $postData['is_pending'] = !$postData['is_approved'];

            // Générer les URLs pour les réponses
            try {
                $postData['like_url'] = $this->router->generate('forum-post-like', ['id' => $postData['id']]);
                $postData['edit_url'] = $this->router->generate('forum-post-edit', ['id' => $postData['id']]);
            } catch (\Exception $e) {
                $postData['like_url'] = '/forum/post/like/' . $postData['id'];
                $postData['edit_url'] = '/forum/post/edit/' . $postData['id'];
            }

            // Vérifier si l'utilisateur peut éditer ce post
            // Règle : Admin/Modo OU (Auteur ET Dernier message du sujet)
            $canEditBasic = $this->postManager->canEdit($postData['id'], $currentUserId, $currentUserEmail);
            $isLastPost = $this->postManager->isLastPost($id, $postData['id']);
            $postData['can_edit'] = $isModerator || ($canEditBasic && $isLastPost);

            // Vérifier si l'auteur autorise les messages privés
            $postData['author_allows_private_messages'] = $this->userAllowsPrivateMessages($postData['author_id']);

            $replies[] = $postData;
        }

        // Préparer la pagination
        $paginationPages = [];
        for ($i = 1; $i <= $totalPages; $i++) {
            try {
                $pageUrl = $this->router->generate('forum-topic', ['id' => $id, 'page' => $i]);
            } catch (\Exception $e) {
                $pageUrl = '/forum/topic/' . $id . '?page=' . $i;
            }
            $paginationPages[] = [
                'number' => $i,
                'url' => $pageUrl,
                'is_current' => $i === $page,
            ];
        }

        // Préparer les données du topic
        $topicData = $topic->toArray();
        try {
            $topicData['url'] = $this->router->generate('forum-topic', ['id' => $topicData['id']]);
        } catch (\Exception $e) {
            $topicData['url'] = '/forum/topic/' . $topicData['id'];
        }

        // Formater les dates du topic
        if ($topicData['created_at']) {
            try {
                $topicData['created_at_formatted'] = date('d/m/Y à H:i', strtotime($topicData['created_at']));
            } catch (\Exception $e) {
                $topicData['created_at_formatted'] = $topicData['created_at'];
            }
        } else {
            $topicData['created_at_formatted'] = '';
        }

        if ($topicData['last_post_at']) {
            try {
                $topicData['last_post_at_formatted'] = date('d/m/Y à H:i', strtotime($topicData['last_post_at']));
            } catch (\Exception $e) {
                $topicData['last_post_at_formatted'] = $topicData['last_post_at'];
            }
        } else {
            $topicData['last_post_at_formatted'] = $topicData['created_at_formatted'];
        }

        // Flags pour le template
        $topicData['is_locked'] = (bool) $topicData['is_locked'];
        $topicData['is_pinned'] = (bool) $topicData['is_pinned'];

        // Construire le breadcrumb avec la hiérarchie complète des catégories
        $breadcrumbItems = [
            ['label' => $this->forumBreadcrumbLabel, 'url' => $this->router->generate('forum-index')],
        ];

        // Récupérer le chemin complet de la catégorie (tous les parents)
        $categoryPath = $this->categoryManager->getCategoryPath($topic->getCategoryId());
        foreach ($categoryPath as $cat) {
            try {
                $categoryUrl = $this->router->generate('forum-category', ['id' => $cat->getId()]);
            } catch (\Exception $e) {
                $categoryUrl = '/forum/category/' . $cat->getId();
            }
            $breadcrumbItems[] = ['label' => $cat->getName(), 'url' => $categoryUrl];
        }

        // Ajouter le topic comme dernier élément (sans URL = actif)
        $breadcrumbItems[] = ['label' => (string)$topic->title, 'url' => ''];
        $breadcrumb = $this->buildBreadcrumb($breadcrumbItems);

        $response->setTitle('<i class="fa-solid fa-comment-dots"></i> ' . (string)$topic->title . ' - ' . $this->forumBreadcrumbLabel);
        $tpl = $response->createPluginTemplate('forum', 'topic');
        $tpl->set('breadcrumb', $breadcrumb);
        $tpl->set('forumLabel', $this->forumBreadcrumbLabel);
        $tpl->set('forumTableHeaderLabel', $this->forumTableHeaderLabel);
        $topicData['reply_count'] = max(0, (int)($topicData['post_count'] ?? 0) - 1);
        $tpl->set('topic', $topicData);
        // Dernière vérification : forcer avatar à null si c'est /avatar
        if (isset($firstPost['author']['avatar'])) {
            if ($firstPost['author']['avatar'] === '/avatar' || trim($firstPost['author']['avatar']) === '/avatar') {
                $firstPost['author']['avatar'] = null;
                $firstPost['author']['avatar_color'] = $this->generateAvatarColor($firstPost['author']['username'] ?? '?');
                $firstPost['author']['avatar_initials'] = $this->generateAvatarInitials($firstPost['author']['username'] ?? '?');
            }
        }
        foreach ($replies as &$reply) {
            if (isset($reply['author']['avatar'])) {
                if ($reply['author']['avatar'] === '/avatar' || trim($reply['author']['avatar']) === '/avatar') {
                    $reply['author']['avatar'] = null;
                    $reply['author']['avatar_color'] = $this->generateAvatarColor($reply['author']['username'] ?? '?');
                    $reply['author']['avatar_initials'] = $this->generateAvatarInitials($reply['author']['username'] ?? '?');
                }
            }
        }
        unset($reply);

        $tpl->set('firstPost', $firstPost);
        $tpl->set('replies', $replies);
        $tpl->set('currentPage', $page);
        $tpl->set('totalPages', $totalPages);
        $tpl->set('paginationPages', $paginationPages);
        $tpl->set('hasPagination', $totalPages > 1);

        $tpl->set('csrfToken', Csrf::token());
        $tpl->set('currentUser', $user);
        // Passer l'ID de l'utilisateur connecté comme variable séparée pour faciliter l'accès dans le template
        $tpl->set('currentUserId', $user ? (int) $user->attributes['id'] : null);
        // Seuls les utilisateurs connectés peuvent répondre
        $tpl->set('canPost', $user !== null);
        $tpl->set('isAdmin', $isAdmin);
        $tpl->set('isModerator', $isModerator);

        // Vérifier si le plugin messaging est activé
        $messagingPlugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('messaging');
        // Les URLs messaging ne sont plus nécessaires
        // Le plugin messaging les injectera via le hook forum:topic-post-actions

        // URLs pour les actions
        try {
            $tpl->set('moderationApproveUrl', $this->router->generate('forum-post-approve'));
            $tpl->set('moderationDeleteUrl', $this->router->generate('forum-post-delete-moderation'));
            $tpl->set('moderationTopicDeleteUrl', $this->router->generate('forum-topic-moderate-delete'));
            $tpl->set('createPostUrl', $this->router->generate('forum-post-create'));
            $tpl->set('replyPageUrl', $this->router->generate('forum-topic-reply', ['id' => $topicData['id']]));
            $tpl->set('topicPinUrl', $this->router->generate('forum-topic-pin', ['id' => $topicData['id']]));
            $tpl->set('topicLockUrl', $this->router->generate('forum-topic-lock', ['id' => $topicData['id']]));
        } catch (\Exception $e) {
            $tpl->set('moderationApproveUrl', '/forum/post/approve');
            $tpl->set('moderationDeleteUrl', '/forum/post/delete');
            $tpl->set('moderationTopicDeleteUrl', '/forum/topic/moderate/delete');
            $tpl->set('createPostUrl', '/forum/post/create');
            $tpl->set('replyPageUrl', '/forum/topic/' . $topicData['id'] . '/reply');
            $tpl->set('topicPinUrl', '/forum/topic/pin/' . $topicData['id']);
            $tpl->set('topicLockUrl', '/forum/topic/lock/' . $topicData['id']);
        }
        $tpl->set('ROUTER', $this->router);

        $response->addTemplate($tpl);
        return $response;
    }

    public function showCreateTopic()
    {
        $user = UsersManager::getCurrentUser();
        // Seuls les utilisateurs connectés peuvent créer un sujet
        if (!$user) {
            Show::msg(Lang::get('forum.login-required'), 'warning');
            return $this->core->redirect('/forum');
        }

        $categoryId = (int) ($_GET['category_id'] ?? 0);
        if ($categoryId <= 0) {
            Show::msg('Catégorie invalide.', 'error');
            return $this->core->redirect('/forum');
        }

        $category = $this->categoryManager->getById($categoryId);
        if (!$category) {
            Show::msg('Catégorie introuvable.', 'error');
            return $this->core->redirect('/forum');
        }

        // Vérifier si la catégorie a des sous-catégories
        $subcategories = $this->categoryManager->getSubcategories($categoryId);
        $hasSubcategories = !empty($subcategories);
        $isAdmin = $user && \Core\Navigation\AccessPolicyEvaluator::getInstance()->isAdmin(new \Core\Navigation\ConditionContext($user));

        if ($hasSubcategories && !$isAdmin) {
            Show::msg('Cette catégorie contient des sous-catégories. Veuillez sélectionner une sous-catégorie pour créer un sujet.', 'error');
            return $this->core->redirect($this->router->generate('forum-category', ['id' => $categoryId]));
        }

        $response = new PublicResponse();
        $response->setTitle('Créer un nouveau sujet');
        $tpl = $response->createPluginTemplate('forum', 'create-topic');

        $categoryData = $category->toArray();
        try {
            $categoryData['url'] = $this->router->generate('forum-category', ['id' => $categoryId]);
        } catch (\Exception $e) {
            $categoryData['url'] = '/forum/category/' . $categoryId;
        }

        // Breadcrumb
        $breadcrumb = $this->buildBreadcrumb([
            ['label' => 'Forum', 'url' => $this->router->generate('forum-index')],
            ['label' => $category->getName(), 'url' => $categoryData['url']],
            ['label' => 'Créer un sujet', 'url' => ''],
        ]);

        $tpl->set('category', $categoryData);
        $tpl->set('breadcrumb', $breadcrumb);
        $tpl->set('csrfToken', Csrf::token());
        try {
            $tpl->set('createTopicUrl', $this->router->generate('forum-topic-create'));
            $tpl->set('cancelUrl', $this->router->generate('forum-category', ['id' => $categoryId]));
        } catch (\Exception $e) {
            $tpl->set('createTopicUrl', '/forum/topic/create');
            $tpl->set('cancelUrl', '/forum/category/' . $categoryId);
        }

        $response->addTemplate($tpl);
        return $response;
    }

    public function showReply($id = null)
    {
        $user = UsersManager::getCurrentUser();
        if (!$user) {
            Show::msg(Lang::get('forum.login-required'), 'warning');
            return $this->core->redirect('/forum');
        }

        if ($id === null) {
            $id = (int) $this->router->getParam('id');
        } else {
            $id = (int) $id;
        }

        $topic = $this->topicManager->getById($id);
        if (!$topic) {
            Show::msg('Sujet introuvable.', 'error');
            return $this->core->redirect('/forum');
        }

        if ($topic->isLocked()) {
            Show::msg('Ce sujet est verrouillé. Vous ne pouvez plus y répondre.', 'error');
            return $this->core->redirect($topic->getUrl());
        }

        $response = new PublicResponse();
        $response->setTitle('Répondre à : ' . (string)$topic->title);
        $tpl = $response->createPluginTemplate('forum', 'reply-topic');

        $topicData = $topic->toArray();
        $topicData['url'] = $topic->getUrl();

        // Breadcrumb
        $category = $this->categoryManager->getById($topic->getCategoryId());
        $categoryUrl = $category ? $this->router->generate('forum-category', ['id' => $category->getId()]) : '/forum';
        $breadcrumb = $this->buildBreadcrumb([
            ['label' => 'Forum', 'url' => $this->router->generate('forum-index')],
            ['label' => $category ? $category->getName() : 'Forum', 'url' => $categoryUrl],
            ['label' => $topic->getTitle(), 'url' => $topicData['url']],
            ['label' => 'Répondre', 'url' => ''],
        ]);

        $tpl->set('topic', $topicData);
        $tpl->set('breadcrumb', $breadcrumb);
        $tpl->set('csrfToken', Csrf::token());
        try {
            $tpl->set('createPostUrl', $this->router->generate('forum-post-create'));
            $tpl->set('cancelUrl', $topicData['url']);
        } catch (\Exception $e) {
            $tpl->set('createPostUrl', '/forum/post/create');
            $tpl->set('cancelUrl', $topicData['url']);
        }

        $response->addTemplate($tpl);
        return $response;
    }

    public function createTopic()
    {
        $response = new ApiResponse();
        $isAjax = $this->request->isAjax();

        if (!Csrf::validate($_POST['_csrf'] ?? $_POST['token'] ?? null, false)) {
            if ($isAjax) {
                $response->status = ApiResponse::STATUS_BAD_REQUEST;
                $response->body = ['message' => Lang::get('core.bad-token')];
                return $response;
            }
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect('/forum');
        }

        $user = UsersManager::getCurrentUser();
        // Seuls les utilisateurs connectés peuvent créer un sujet
        if (!$user) {
            if ($isAjax) {
                $response->status = ApiResponse::STATUS_NOT_AUTHORIZED;
                $response->body = ['message' => Lang::get('forum.login-required')];
                return $response;
            }
            Show::msg(Lang::get('forum.login-required'), 'warning');
            return $this->core->redirect('/forum');
        }

        $categoryId = (int) ($_POST['category_id'] ?? 0);

        // Vérifier si la catégorie a des sous-catégories
        $subcategories = $this->categoryManager->getSubcategories($categoryId);
        $hasSubcategories = !empty($subcategories);
        $isAdmin = $user && \Core\Navigation\AccessPolicyEvaluator::getInstance()->isAdmin(new \Core\Navigation\ConditionContext($user));

        if ($hasSubcategories && !$isAdmin) {
            if ($isAjax) {
                $response->status = ApiResponse::STATUS_BAD_REQUEST;
                $response->body = ['message' => 'Cette catégorie contient des sous-catégories. Veuillez sélectionner une sous-catégorie.'];
                return $response;
            }
            Show::msg('Cette catégorie contient des sous-catégories. Veuillez sélectionner une sous-catégorie.', 'error');
            return $this->core->redirect($this->router->generate('forum-category', ['id' => $categoryId]));
        }

        // Récupérer le contenu (TinyMCE sera géré par le hook beforeSaveEditor)
        $content = trim($_POST['content'] ?? '');
        // Appeler le hook beforeSaveEditor si disponible (pour TinyMCE)
        $content = $this->core->callHook('beforeSaveEditor', $content);

        $data = [
            'category_id' => $categoryId,
            'title' => trim($_POST['title'] ?? ''),
            'content' => \Utils\Sanitizer::cleanHtml(trim($_POST['content'] ?? '')),
            'author_id' => (int) $user->attributes['id'],
            'author_name' => $user->username(),
            'author_email' => $user->email ?? '',
        ];

        if (empty($data['category_id']) || empty($data['title']) || empty($data['content'])) {
            if ($isAjax) {
                $response->status = ApiResponse::STATUS_BAD_REQUEST;
                $response->body = ['message' => Lang::get('forum.invalid-data')];
                return $response;
            }
            Show::msg(Lang::get('forum.invalid-data'), 'error');
            return $this->core->redirect('/forum');
        }

        $topic = $this->topicManager->create($data);
        if ($topic) {
            // Create first post
            $postData = [
                'topic_id' => $topic->getId(),
                'content' => $data['content'],
                'author_id' => $data['author_id'],
                'author_name' => $data['author_name'],
                'author_email' => $data['author_email'],
                'force_approve' => true,
            ];
            $this->postManager->create($postData);

            // Notification broadcast pour le nouveau sujet
            try {
                $dataNotif = [
                    'user_id' => 0, // Broadcast
                    'source_plugin' => 'forum',
                    'entity_type' => 'topic',
                    'entity_id' => $topic->getId(),
                    'notification_type' => 'new_topic',
                    'title' => 'Nouveau sujet sur le Forum',
                    'message' => $user->username() . ' a ouvert un nouveau sujet : "' . $topic->getTitle() . '"',
                    'link_url' => $topic->getUrl()
                ];
                \Core\Core::getInstance()->callHook('notifications:send', $dataNotif);
            } catch (\Exception $e) {
                \Core\Core::getInstance()->getLogger()->log('ERROR', "[Forum] Erreur broadcast nouveau sujet: " . $e->getMessage());
            }

            $redirectUrl = Util::urlBuild($topic->getUrl());
            if ($isAjax) {
                $response->status = ApiResponse::STATUS_SUCCESS;
                $response->body = ['message' => Lang::get('forum.topic-created'), 'redirect' => $redirectUrl];
                return $response;
            }
            Show::msg(Lang::get('forum.topic-created'), 'success');
            return $this->core->redirect($redirectUrl);
        }

        if ($isAjax) {
            $response->status = ApiResponse::STATUS_NOT_FOUND;
            $response->body = ['message' => Lang::get('forum.topic-create-error')];
            return $response;
        }
        Show::msg(Lang::get('forum.topic-create-error'), 'error');
        return $this->core->redirect('/forum');
    }

    public function createPost()
    {
        $response = new ApiResponse();
        $isAjax = $this->request->isAjax();

        if (!Csrf::validate($_POST['_csrf'] ?? $_POST['token'] ?? null, false)) {
            if ($isAjax) {
                $response->status = ApiResponse::STATUS_BAD_REQUEST;
                $response->body = ['message' => Lang::get('core.bad-token')];
                return $response;
            }
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect('/forum');
        }

        $user = UsersManager::getCurrentUser();
        // Seuls les utilisateurs connectés peuvent répondre
        if (!$user) {
            if ($isAjax) {
                $response->status = ApiResponse::STATUS_NOT_AUTHORIZED;
                $response->body = ['message' => Lang::get('forum.login-required')];
                return $response;
            }
            Show::msg(Lang::get('forum.login-required'), 'warning');
            return $this->core->redirect('/forum');
        }

        $topicId = (int) ($_POST['topic_id'] ?? 0);
        $topic = $this->topicManager->getById($topicId);

        if (!$topic || $topic->isLocked()) {
            if ($isAjax) {
                $response->status = ApiResponse::STATUS_NOT_FOUND;
                $response->body = ['message' => Lang::get('forum.topic-locked')];
                return $response;
            }
            Show::msg(Lang::get('forum.topic-locked'), 'error');
            return $this->core->redirect('/forum');
        }

        $data = [
            'topic_id' => $topicId,
            'content' => \Utils\Sanitizer::cleanHtml(trim($_POST['content'] ?? '')),
            'author_id' => (int) $user->attributes['id'],
            'author_name' => $user->username(),
            'author_email' => $user->email ?? '',
        ];

        if (empty($data['content'])) {
            if ($isAjax) {
                $response->status = ApiResponse::STATUS_BAD_REQUEST;
                $response->body = ['message' => Lang::get('forum.invalid-data')];
                return $response;
            }
            Show::msg(Lang::get('forum.invalid-data'), 'error');
            return $this->core->redirect($topic ? $topic->getUrl() : '/forum');
        }

        $post = $this->postManager->create($data);
        $redirectUrl = $topic ? Util::urlBuild($topic->getUrl()) : Util::urlBuild('/forum');
        if ($post) {
            // Déclencher le hook de domaine pour notification (Indépendant du plugin Notifications)
            $this->core->callHook('forum:reply-created', [
                'topic' => $topic,
                'post' => $post,
                'actor' => [
                    'id' => $data['author_id'],
                    'name' => $data['author_name']
                ]
            ]);

            if ($isAjax) {
                $response->status = ApiResponse::STATUS_SUCCESS;
                $response->body = ['message' => Lang::get('forum.post-created'), 'redirect' => $redirectUrl];
                return $response;
            }
            Show::msg(Lang::get('forum.post-created'), 'success');
            return $this->core->redirect($redirectUrl);
        }

        if ($isAjax) {
            $response->status = ApiResponse::STATUS_NOT_FOUND;
            $response->body = ['message' => Lang::get('forum.post-create-error')];
            return $response;
        }
        Show::msg(Lang::get('forum.post-create-error'), 'error');
        return $this->core->redirect($redirectUrl);
    }

    public function approvePost()
    {
        if (!Csrf::validate($_POST['_csrf'] ?? $_POST['token'] ?? null, false)) {
            Show::msg('Erreur de sécurité.', 'error');
            return $this->core->redirect('/forum');
        }
        $redirect = $_POST['redirect'] ?? '';
        $user = UsersManager::getCurrentUser();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);
        if (!$user || (!$evaluator->canAccess(new \Core\Navigation\GenericResource('forum:moderate'), $ctx))) {
            Show::msg(Lang::get('forum.moderation-not-allowed'), 'error');
            return $this->moderatorRedirect(null, $redirect);
        }
        $postId = (int) ($_POST['post_id'] ?? 0);
        $post = $this->postManager->getById($postId);
        if (!$post) {
            Show::msg(Lang::get('forum.moderation-invalid'), 'error');
            return $this->moderatorRedirect(null, $redirect);
        }
        if ($this->postManager->approve($postId)) {
            Show::msg(Lang::get('forum.post-approved'), 'success');
        } else {
            Show::msg(Lang::get('forum.moderation-failed'), 'error');
        }
        return $this->moderatorRedirect($post->getTopicId(), $redirect);
    }

    public function deletePostModeration()
    {
        if (!Csrf::validate($_POST['_csrf'] ?? $_POST['token'] ?? null, false)) {
            Show::msg('Erreur de sécurité.', 'error');
            return $this->core->redirect('/forum');
        }
        $redirect = $_POST['redirect'] ?? '';
        $user = UsersManager::getCurrentUser();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);

        if (!$user || !$evaluator->canAccess(new \Core\Navigation\GenericResource('forum:moderate'), $ctx)) {
            return $this->moderatorRedirect(null, $redirect);
        }
        $postId = (int) ($_POST['post_id'] ?? 0);
        $post = $this->postManager->getById($postId);
        if (!$post) {
            Show::msg(Lang::get('forum.moderation-invalid'), 'error');
            return $this->moderatorRedirect(null, $redirect);
        }
        if ($this->postManager->delete($postId)) {
            Show::msg(Lang::get('forum.post-deleted'), 'success');
        } else {
            Show::msg(Lang::get('forum.moderation-failed'), 'error');
        }
        return $this->moderatorRedirect($post->getTopicId(), $redirect);
    }

    public function deleteTopicModeration()
    {
        if (!Csrf::validate($_POST['_csrf'] ?? $_POST['token'] ?? null, false)) {
            Show::msg('Erreur de sécurité.', 'error');
            return $this->core->redirect('/forum');
        }
        $user = UsersManager::getCurrentUser();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);
        if (!$user || !$evaluator->isAdmin($ctx)) {
            return $this->core->redirect('/forum');
        }
        $topicId = (int) ($_POST['topic_id'] ?? 0);
        if ($topicId < 1) {
            Show::msg(Lang::get('forum.moderation-invalid'), 'error');
            return $this->core->redirect('/forum');
        }
        if ($this->topicManager->delete($topicId)) {
            Show::msg(Lang::get('forum.topic-deleted'), 'success');
        } else {
            Show::msg(Lang::get('forum.moderation-failed'), 'error');
        }
        return $this->core->redirect('/forum');
    }

    private function ensureAdminRequest(string $token): bool
    {
        $user = UsersManager::getCurrentUser();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);
        if (!$user || (!$evaluator->canAccess(new \Core\Navigation\GenericResource('forum:moderate'), $ctx))) {
            Show::msg(Lang::get('forum.moderation-not-allowed'), 'error');
            return false;
        }
        if (!Csrf::validate($token, false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return false;
        }
        return true;
    }

    /**
     * Prépare les données publiques d'une catégorie récursivement
     */
    private function prepareCategoryDataPublic(\Forum\Entities\Category $category): array
    {
        $data = $category->toArray();
        
        try {
            $data['url'] = $this->router->generate('forum-category', ['id' => $category->getId()]);
        } catch (\Exception $e) {
            $data['url'] = '/forum/category/' . $category->getId();
        }

        $data['subcategories'] = [];
        foreach ($category->getSubcategories() as $sub) {
            $data['subcategories'][] = $this->prepareCategoryDataPublic($sub);
        }

        if (!empty($data['last_post_at'])) {
            try {
                $data['last_post_at_formatted'] = date('d/m/Y H:i', strtotime($data['last_post_at']));
            } catch (\Exception $e) {
                $data['last_post_at_formatted'] = $data['last_post_at'];
            }
        } else {
            $data['last_post_at_formatted'] = '';
        }

        if (!empty($data['created_at'])) {
            try {
                $data['created_at_formatted'] = date('d/m/Y', strtotime($data['created_at']));
            } catch (\Exception $e) {
                $data['created_at_formatted'] = $data['created_at'];
            }
        } else {
            $data['created_at_formatted'] = '';
        }

        return $data;
    }

    private function buildBreadcrumb(array $items): array
    {
        $breadcrumb = [];
        foreach ($items as $item) {
            $url = $item['url'] ?? '';
            $isActive = empty($url);

            $breadcrumb[] = [
                'label' => $item['label'],
                'url' => $url,
                'is_active' => (int) $isActive, // Entier (1/0) pour compatibilité template
            ];
        }
        return $breadcrumb;
    }

    /**
     * Vérifie si un utilisateur autorise les messages privés
     * Par défaut, si l'attribut n'existe pas, on considère que les MP sont autorisés (rétrocompatibilité)
     * 
     * @param int|null $userId ID de l'utilisateur
     * @return bool True si l'utilisateur autorise les MP, false sinon
     */
    private function userAllowsPrivateMessages(?int $userId): bool
    {
        if (!$userId || $userId <= 0) {
            return false; // Pas d'utilisateur = pas de MP possible
        }

        try {
            $authorUser = \Core\Auth\User::findPK($userId);
            if (!$authorUser) {
                return false;
            }

            // Si l'attribut n'existe pas, on considère que les MP sont autorisés par défaut (rétrocompatibilité)
            // On vérifie d'abord 'allow_messaging' (nouveau standard) puis 'allow_private_messages' (ancien)
            if (isset($authorUser->attributes['allow_messaging'])) {
                 return (bool) $authorUser->attributes['allow_messaging'];
            }
            
            if (isset($authorUser->attributes['allow_private_messages'])) {
                return (bool) $authorUser->attributes['allow_private_messages'];
            }

            return true;
        } catch (\Exception $e) {
            // En cas d'erreur, on considère que les MP sont autorisés par défaut
            return true;
        }
    }

    private function moderatorRedirect(?int $topicId, string $fallback = '')
    {
        $url = $fallback !== '' ? $fallback : '/forum';
        if ($topicId) {
            $url = $this->router->generate('forum-topic', ['id' => $topicId]);
        }
        return $this->core->redirect(Util::urlBuild($url));
    }

    public function deleteTopic()
    {
        if (!Csrf::validate($_POST['_csrf'] ?? $_POST['token'] ?? null, false)) {
            Show::msg('Erreur de sécurité.', 'error');
            return $this->core->redirect('/forum');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $user = UsersManager::getCurrentUser();
        if (!$user) {
            return Core::getInstance()->redirect('/forum');
        }
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('forum:post'), $ctx)) {
            return Core::getInstance()->redirect('/forum');
        }

        $topic = $this->topicManager->getById($id);
        if (!$topic) {
            Show::msg('Sujet introuvable.', 'error');
            return Core::getInstance()->redirect('/forum');
        }

        // Vérification sticte du propriétaire ou admin
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);
        $isAdmin = $evaluator->canAccess(new \Core\Navigation\GenericResource('forum:moderate'), $ctx);
        if ($topic->getAuthorId() !== (int) $user->attributes['id'] && !$isAdmin) {
            Show::msg('Vous n\'êtes pas autorisé à supprimer ce sujet.', 'error');
            return Core::getInstance()->redirect('/forum');
        }

        $this->topicManager->delete($id);
        Show::msg('Sujet supprimer.', 'success');
        return Core::getInstance()->redirect('/forum');
    }

    public function deletePost()
    {
        if (!Csrf::validate($_POST['_csrf'] ?? $_POST['token'] ?? null, false)) {
            Show::msg('Erreur de sécurité.', 'error');
            return $this->core->redirect('/forum');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $user = UsersManager::getCurrentUser();
        if (!$user) {
            return Core::getInstance()->redirect('/forum');
        }
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('forum:post'), $ctx)) {
            return Core::getInstance()->redirect('/forum');
        }

        $post = $this->postManager->getById($id);
        if (!$post) {
            Show::msg('Message introuvable.', 'error');
            return Core::getInstance()->redirect('/forum');
        }

        // Vérification sticte du propriétaire ou admin
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);
        $isAdmin = $evaluator->canAccess(new \Core\Navigation\GenericResource('forum:moderate'), $ctx);
        if ($post->getAuthorId() !== (int) $user->attributes['id'] && !$isAdmin) {
            Show::msg('Vous n\'êtes pas autorisé à supprimer ce message.', 'error');
            return Core::getInstance()->redirect('/forum');
        }

        $this->postManager->delete($id);

        // Redirection vers le sujet (sauf si c'était le premier post, alors forum index)
        $topic = $this->topicManager->getById($post->getTopicId());
        if ($topic) {
            return Core::getInstance()->redirect($topic->getUrl());
        }
        return Core::getInstance()->redirect('/forum');
    }

    public function editPost($id = null)
    {
        // Si $id n'est pas passé en paramètre, le récupérer depuis le routeur
        if ($id === null) {
            $id = (int) $this->router->getParam('id');
        } else {
            $id = (int) $id;
        }
        $post = $this->postManager->getById($id);

        if (!$post) {
            Show::msg('Message introuvable.', 'error');
            return $this->core->redirect('/forum');
        }

        $user = UsersManager::getCurrentUser();
        $currentUserId = $user ? (int) $user->attributes['id'] : null;
        $currentUserEmail = $user ? ($user->email ?? null) : null;

        $isAdmin = $user && \Core\Navigation\AccessPolicyEvaluator::getInstance()->isAdmin(new \Core\Navigation\ConditionContext($user));
        $isModerator = $user && \Core\Navigation\AccessPolicyEvaluator::getInstance()->canAccess(new \Core\Navigation\GenericResource('forum:moderate'), new \Core\Navigation\ConditionContext($user));
        $isLastPost = $this->postManager->isLastPost($post->getTopicId(), $id);

        if (!($isModerator || ($this->postManager->canEdit($id, $currentUserId, $currentUserEmail) && $isLastPost))) {
            Show::msg('Ce message ne peut plus être modifié car il y a eu une réponse.', 'error');
            $topic = $this->topicManager->getById($post->getTopicId());
            return $this->core->redirect($topic ? $topic->getUrl() : '/forum');
        }

        $response = new PublicResponse();
        $response->setTitle('Modifier le message');
        $tpl = $response->createPluginTemplate('forum', 'post-edit');

        $topic = $this->topicManager->getById($post->getTopicId());
        $topicUrl = '/forum';
        if ($topic) {
            try {
                $topicUrl = $topic->getUrl();
            } catch (\Exception $e) {
                $topicUrl = '/forum/topic/' . $topic->getId();
            }
        }

        // Breadcrumb
        $category = $topic ? $this->categoryManager->getById($topic->getCategoryId()) : null;
        $breadcrumbItems = [
            ['label' => 'Forum', 'url' => $this->router->generate('forum-index')],
        ];
        if ($category) {
            try {
                $categoryUrl = $this->router->generate('forum-category', ['id' => $category->getId()]);
            } catch (\Exception $e) {
                $categoryUrl = '/forum/category/' . $category->getId();
            }
            $breadcrumbItems[] = ['label' => $category->getName(), 'url' => $categoryUrl];
        }
        if ($topic) {
            $breadcrumbItems[] = ['label' => $topic->getTitle(), 'url' => $topicUrl];
        }
        $breadcrumbItems[] = ['label' => 'Modifier', 'url' => ''];
        $breadcrumb = $this->buildBreadcrumb($breadcrumbItems);

        $tpl->set('post', $post->toArray());
        $tpl->set('breadcrumb', $breadcrumb);
        $tpl->set('csrfToken', Csrf::token());
        $tpl->set('topicUrl', $topicUrl);

        // Générer l'URL d'édition dans le contrôleur
        $tpl->set('editPostUrl', $this->router->generate('forum-post-edit-send', ['id' => $post->getId()]));

        $response->addTemplate($tpl);
        return $response;
    }

    public function editPostSend($id = null)
    {
        // Si $id n'est pas passé en paramètre, le récupérer depuis le routeur
        if ($id === null) {
            $id = (int) $this->router->getParam('id');
        } else {
            $id = (int) $id;
        }
        $post = $this->postManager->getById($id);

        if (!$post) {
            Show::msg('Message introuvable.', 'error');
            return $this->core->redirect('/forum');
        }

        if (!Csrf::validate($_POST['_csrf'] ?? $_POST['token'] ?? null, false)) {
            Show::msg('Erreur de sécurité.', 'error');
            $topic = $this->topicManager->getById($post->getTopicId());
            return $this->core->redirect($topic ? $topic->getUrl() : '/forum');
        }

        $user = UsersManager::getCurrentUser();
        $currentUserId = $user ? (int) $user->attributes['id'] : null;
        $currentUserEmail = $user ? ($user->email ?? null) : null;

        $isAdmin = $user && \Core\Navigation\AccessPolicyEvaluator::getInstance()->isAdmin(new \Core\Navigation\ConditionContext($user));
        $isModerator = $user && \Core\Navigation\AccessPolicyEvaluator::getInstance()->canAccess(new \Core\Navigation\GenericResource('forum:moderate'), new \Core\Navigation\ConditionContext($user));
        $isLastPost = $this->postManager->isLastPost($post->getTopicId(), $id);

        if (!($isModerator || ($this->postManager->canEdit($id, $currentUserId, $currentUserEmail) && $isLastPost))) {
            Show::msg('Ce message ne peut plus être modifié car il y a eu une réponse.', 'error');
            $topic = $this->topicManager->getById($post->getTopicId());
            return $this->core->redirect($topic ? $topic->getUrl() : '/forum');
        }

        $content = trim($_POST['content'] ?? '');
        if (empty($content)) {
            Show::msg('Le contenu ne peut pas être vide.', 'error');
            try {
                return $this->core->redirect($this->router->generate('forum-post-edit', ['id' => $id]));
            } catch (\Exception $e) {
                return $this->core->redirect('/forum/post/edit/' . $id);
            }
        }

        if ($this->postManager->update($id, ['content' => $content])) {
            Show::msg('Message modifié avec succès.', 'success');
        } else {
            Show::msg('Erreur lors de la modification.', 'error');
        }

        $topic = $this->topicManager->getById($post->getTopicId());
        return $this->core->redirect($topic ? $topic->getUrl() : '/forum');
    }

    public function toggleLike($id = null)
    {
        // Si $id n'est pas passé en paramètre, le récupérer depuis le routeur
        if ($id === null) {
            $id = (int) $this->router->getParam('id');
        } else {
            $id = (int) $id;
        }
        $post = $this->postManager->getById($id);

        if (!$post) {
            Show::msg('Message introuvable.', 'error');
            return $this->core->redirect('/forum');
        }

        if (!Csrf::validate($_POST['_csrf'] ?? $_POST['token'] ?? null, false)) {
            Show::msg('Erreur de sécurité.', 'error');
            $topic = $this->topicManager->getById($post->getTopicId());
            return $this->core->redirect($topic ? $topic->getUrl() : '/forum');
        }

        $user = UsersManager::getCurrentUser();
        $currentUserId = $user ? (int) $user->attributes['id'] : null;
        $currentUserEmail = $user ? ($user->email ?? null) : null;

        if (!$user && empty($currentUserEmail)) {
            Show::msg('Vous devez être connecté pour liker un message.', 'error');
            $topic = $this->topicManager->getById($post->getTopicId());
            return $this->core->redirect($topic ? $topic->getUrl() : '/forum');
        }

        $this->postManager->toggleLike($id, $currentUserId, $currentUserEmail);

        if ($this->request->isAjax()) {
            $post = $this->postManager->getById($id);
            $postData = $post->toArray();
            $postData['like_count'] = $this->postManager->countLikes($id);
            $postData['is_liked_by_current_user'] = $this->postManager->isLikedBy($id, $currentUserId, $currentUserEmail);
            $postData['like_url'] = $this->router->generate('forum-post-like', ['id' => $id]);

            $tpl = new \Template\Template(PLUGINS . 'forum/template/like-button.tpl');
            $tpl->set('post', $postData);
            $tpl->set('csrfToken', Csrf::token());
            echo $tpl->output();
            exit;
        }

        Show::msg('Like mis à jour.', 'success');
        $topic = $this->topicManager->getById($post->getTopicId());
        return $this->core->redirect($topic ? $topic->getUrl() : '/forum');
    }

    public function toggleLock($id = null)
    {
        $id = (int) ($id ?? $_POST['id'] ?? $this->router->getParam('id') ?? 0);
        $topic = $this->topicManager->getById($id);
        if (!$this->ensureAdminRequest($_POST['token'] ?? $_POST['_csrf'] ?? '')) {
            return $this->core->redirect($topic ? $topic->getUrl() : '/forum');
        }
        
        $this->topicManager->toggleLock($id);
        
        if ($this->request->isAjax()) {
            return new ApiResponse(['status' => 'success']);
        }
        
        $topic = $this->topicManager->getById($id);
        return $this->core->redirect($topic ? $topic->getUrl() : '/forum');
    }

    public function togglePin($id = null)
    {
        $id = (int) ($id ?? $_POST['id'] ?? $this->router->getParam('id') ?? 0);
        $topic = $this->topicManager->getById($id);
        if (!$this->ensureAdminRequest($_POST['token'] ?? $_POST['_csrf'] ?? '')) {
            return $this->core->redirect($topic ? $topic->getUrl() : '/forum');
        }
        
        $this->topicManager->togglePin($id);
        
        if ($this->request->isAjax()) {
            return new ApiResponse(['status' => 'success']);
        }
        
        $topic = $this->topicManager->getById($id);
        return $this->core->redirect($topic ? $topic->getUrl() : '/forum');
    }

    public function search()
    {
        $query = trim($_GET['q'] ?? '');

        $response = new PublicResponse();
        $response->setTitle('Recherche dans le forum');
        $tpl = $response->createPluginTemplate('forum', 'search');

        // Breadcrumb
        $breadcrumb = $this->buildBreadcrumb([
            ['label' => 'Forum', 'url' => $this->router->generate('forum-index')],
            ['label' => 'Recherche', 'url' => ''],
        ]);
        $tpl->set('breadcrumb', $breadcrumb);

        // Toujours afficher le formulaire de recherche
        $tpl->set('query', $query);
        $tpl->set('searchUrl', $this->router->generate('forum-search'));

        // Si une requête est fournie, effectuer la recherche
        if (!empty($query)) {
            $results = $this->postManager->search($query);

            // Préparer les résultats pour le template
            $resultsPrepared = [];
            foreach ($results as $result) {
                $postData = $result['post']->toArray();

                // Formater les dates
                if ($postData['created_at']) {
                    try {
                        $postData['created_at_formatted'] = date('d/m/Y à H:i', strtotime($postData['created_at']));
                    } catch (\Exception $e) {
                        $postData['created_at_formatted'] = $postData['created_at'];
                    }
                } else {
                    $postData['created_at_formatted'] = '';
                }

                // Générer l'URL du topic
                try {
                    $topic = $this->topicManager->getById($result['post']->getTopicId());
                    $postData['topic_url'] = $topic ? $topic->getUrl() : '/forum';
                } catch (\Exception $e) {
                    $postData['topic_url'] = '/forum';
                }

                $resultsPrepared[] = [
                    'post' => $postData,
                    'topic_title' => $result['topic_title'],
                    'category_name' => $result['category_name'],
                ];
            }

            $tpl->set('results', $resultsPrepared);
            $tpl->set('resultsCount', count($resultsPrepared));
        } else {
            // Pas de résultats si pas de requête
            $tpl->set('results', []);
            $tpl->set('resultsCount', 0);
        }

        $response->addTemplate($tpl);
        return $response;
    }

    public function notifications()
    {
        $user = UsersManager::getCurrentUser();
        if (!$user) {
            Show::msg('Vous devez être connecté pour voir vos notifications.', 'error');
            return $this->core->redirect('/forum');
        }

        $userId = (int) $user->attributes['id'];
        $notifications = $this->getNotifications($userId);

        $response = new PublicResponse();
        $response->setTitle('Mes notifications');
        $tpl = $response->createPluginTemplate('forum', 'notifications');

        // Breadcrumb
        $breadcrumb = $this->buildBreadcrumb([
            ['label' => 'Forum', 'url' => $this->router->generate('forum-index')],
            ['label' => 'Notifications', 'url' => ''],
        ]);
        $tpl->set('breadcrumb', $breadcrumb);

        // Préparer les notifications avec URLs et dates formatées
        $notificationsPrepared = [];
        foreach ($notifications as $notification) {
            // Formater la date
            if (!empty($notification['created_at'])) {
                try {
                    $notification['created_at_formatted'] = date('d/m/Y à H:i', strtotime($notification['created_at']));
                } catch (\Exception $e) {
                    $notification['created_at_formatted'] = $notification['created_at'];
                }
            } else {
                $notification['created_at_formatted'] = '';
            }

            // Générer l'URL de lecture
            $notification['readUrl'] = $this->router->generate('forum-notification-read', ['id' => $notification['id']]);

            // S'assurer que is_read est un booléen
            $notification['is_read'] = (bool) ($notification['is_read'] ?? false);

            $notificationsPrepared[] = $notification;
        }

        $tpl->set('notifications', $notificationsPrepared);
        $tpl->set('unreadCount', count(array_filter($notificationsPrepared, function ($n) {
            return !$n['is_read'];
        })));
        $tpl->set('csrfToken', Csrf::token());

        $response->addTemplate($tpl);
        return $response;
    }

    public function markNotificationRead($id = null)
    {
        // Si $id n'est pas passé en paramètre, le récupérer depuis le routeur
        if ($id === null) {
            $id = (int) $this->router->getParam('id');
        } else {
            $id = (int) $id;
        }
        $user = UsersManager::getCurrentUser();

        if (!$user) {
            return $this->core->redirect('/forum');
        }

        if (!Csrf::validate($_POST['_csrf'] ?? $_POST['token'] ?? null, false)) {
            Show::msg('Erreur de sécurité.', 'error');
            try {
                return $this->core->redirect($this->router->generate('forum-notifications'));
            } catch (\Exception $e) {
                return $this->core->redirect('/forum/notifications');
            }
        }

        $userId = (int) $user->attributes['id'];
        $notification = \Forum\Entities\ForumNotification::findPK($id);
        
        if ($notification && (int)$notification->user_id === $userId) {
            $notification->is_read = 1;
            $notification->save();
        }

        Show::msg('Notification marquée comme lue.', 'success');
        try {
            return $this->core->redirect($this->router->generate('forum-notifications'));
        } catch (\Exception $e) {
            return $this->core->redirect('/forum/notifications');
        }
    }

    private function getNotifications(int $userId): array
    {
        $rows = \Forum\Entities\ForumNotification::query()
            ->where('user_id', '=', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit(50)
            ->get();

        $notifications = [];
        foreach ($rows as $row) {
            $notification = [
                'id' => $row->id,
                'type' => $row->type,
                'message' => $row->message,
                'is_read' => (bool) $row->is_read,
                'created_at' => $row->created_at,
                'topic_id' => $row->topic_id,
                'post_id' => $row->post_id,
            ];

            // Formater la date
            if (!empty($row->created_at)) {
                try {
                    $notification['created_at_formatted'] = date('d/m/Y à H:i', strtotime((string) $row->created_at));
                } catch (\Exception $e) {
                    $notification['created_at_formatted'] = (string) $row->created_at;
                }
            } else {
                $notification['created_at_formatted'] = '';
            }

            if (!empty($row->topic_id)) {
                try {
                    $topic = $this->topicManager->getById((int) $row->topic_id);
                    if ($topic) {
                        $notification['topic_url'] = $topic->getUrl();
                    } else {
                        $notification['topic_url'] = null;
                    }
                } catch (\Exception $e) {
                    $notification['topic_url'] = null;
                }
            } else {
                $notification['topic_url'] = null;
            }

            $notifications[] = $notification;
        }

        return $notifications;
    }

    private function createNotification(int $userId, string $type, string $message, ?int $topicId = null, ?int $postId = null): void
    {
        $notification = new \Forum\Entities\ForumNotification();
        $notification->user_id = $userId;
        $notification->type = $type;
        $notification->message = $message;
        $notification->topic_id = $topicId;
        $notification->post_id = $postId;
        $notification->is_read = 0;
        $notification->save();

        // Bridge vers le système centralisé
        try {
            $linkUrl = null;
            if ($topicId) {
                // TopicManager est déjà chargé dans le constructeur
                $topic = $this->topicManager->getById($topicId);
                if ($topic) {
                    $linkUrl = $topic->getUrl();
                }
            }
            
            $data = [
                'user_id' => $userId,
                'source_plugin' => 'forum',
                'entity_type' => 'post',
                'entity_id' => $postId,
                'notification_type' => $type,
                'title' => 'Forum : ' . ($type === 'new_reply' ? 'Nouvelle réponse' : 'Notification'),
                'message' => $message,
                'link_url' => $linkUrl
            ];
            \Core\Core::getInstance()->callHook('notifications:send', $data);
        } catch (\Exception $e) {
            \Core\Core::getInstance()->getLogger()->log('ERROR', "[Forum] Erreur pont notification: " . $e->getMessage());
        }
    }

    /**
     * Génère une variable CSS de couleur d'avatar basée sur le nom d'utilisateur
     */
    private function generateAvatarColor(string $username): string
    {
        return 'var(--evo-accent)';
    }

    /**
     * Génère un indice de teinte déterministe (1..8)
     */
    private function generateAvatarTone(string $username): int
    {
        return \Forum\Services\UserEnrichmentService::getAvatarTone($username);
    }

    /**
     * Génère les initiales d'un utilisateur (1-2 lettres)
     * @param string $username
     * @return string Initiales en majuscules
     */
    private function generateAvatarInitials(string $username): string
    {
        $username = trim($username);
        if (empty($username)) {
            return '?';
        }

        // Extraire les mots (séparés par espaces, tirets, underscores)
        $words = preg_split('/[\s\-_]+/', $username);

        if (count($words) >= 2) {
            // Deux initiales si plusieurs mots
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        } else {
            // Une ou deux lettres du premier mot
            $firstWord = $words[0];
            if (mb_strlen($firstWord) >= 2) {
                return strtoupper(mb_substr($firstWord, 0, 2));
            } else {
                return strtoupper($firstWord);
            }
        }
    }

    /**
     * Valide si une URL d'avatar est valide (pas une valeur par défaut invalide)
     * @param string|null $avatarUrl
     * @return bool
     */
    private function isValidAvatarUrl(?string $avatarUrl): bool
    {
        if (empty($avatarUrl)) {
            return false;
        }

        // Normaliser l'URL (trim, lowercase pour comparaison)
        $normalized = trim($avatarUrl);
        $normalizedLower = strtolower($normalized);

        // Filtrer les valeurs invalides communes (exactes et variations)
        $invalidValues = ['/avatar', 'avatar', '/', ''];
        $invalidPatterns = ['/^\/avatar/', '/^avatar$/i', '/^\/$/'];

        if (in_array($normalized, $invalidValues, true) || in_array($normalizedLower, $invalidValues, true)) {
            return false;
        }

        // Vérifier les patterns invalides
        foreach ($invalidPatterns as $pattern) {
            if (preg_match($pattern, $normalized)) {
                return false;
            }
        }

        // Vérifier si c'est une URL valide (commence par http:// ou https://)
        if (preg_match('/^https?:\/\//', $normalized)) {
            return true;
        }

        // Vérifier si c'est un chemin relatif valide (ne commence pas par / seul)
        if ($normalized[0] === '/' && strlen($normalized) > 1) {
            // Vérifier si le fichier existe
            $filePath = ROOT . ltrim($normalized, '/');
            if (file_exists($filePath) && is_file($filePath)) {
                return true;
            }
        }

        // Vérifier si c'est un chemin relatif sans slash initial
        $filePath = ROOT . $normalized;
        if (file_exists($filePath) && is_file($filePath)) {
            return true;
        }

        return false;
    }

    /**
     * Enrichit les données d'un auteur avec les informations d'avatar généré
     * @param array $authorData
     * @return array
     */
    private function enrichAuthorAvatar(array $authorData): array
    {
        $username = $authorData['username'] ?? '?';
        $userId = $authorData['id'] ?? null;
        $avatar = $authorData['avatar'] ?? null;

        // TOUJOURS générer les données CSS (fallback)
        $authorData['avatar_color'] = $this->generateAvatarColor($username);
        $authorData['avatar_tone'] = $this->generateAvatarTone($username);
        $authorData['avatar_initials'] = $this->generateAvatarInitials($username);

        // Logique simple : 
        // - Si l'utilisateur CMS a uploadé un profile_photo, avatar contient l'URL
        // - Sinon, avatar est null et on utilise l'avatar CSS
        // - Ne JAMAIS utiliser l'avatar MySQL (déjà filtré dans UserEnrichmentService)

        // Vérifier si l'avatar existe et est valide (pas vide, pas null, pas /avatar)
        if (!empty($avatar) && $avatar !== null) {
            $avatarTrimmed = trim($avatar);

            // Rejeter explicitement les valeurs invalides
            if (
                $avatarTrimmed === '/avatar' ||
                $avatarTrimmed === 'avatar' ||
                $avatarTrimmed === '/' ||
                strtolower($avatarTrimmed) === 'avatar' ||
                preg_match('/^\/avatar/i', $avatarTrimmed)
            ) {
                $authorData['avatar'] = null;
                $authorData['has_valid_avatar'] = false;
            } else {
                // Avatar valide du CMS
                $authorData['has_valid_avatar'] = true;
            }
        } else {
            // Pas d'avatar CMS, utiliser le CSS
            $authorData['avatar'] = null;
            $authorData['has_valid_avatar'] = false;
        }

        // Initialiser les champs pour éviter l'affichage du nom de la variable dans le template
        // si le moteur de template ne trouve pas la clé (cas de created_at_formatted littéral)
        $authorData['created_at_formatted'] = '';
        $authorData['age'] = $authorData['age'] ?? null;
        $authorData['sex'] = $authorData['sex'] ?? null;
        $authorData['city'] = $authorData['city'] ?? null;
        $authorData['district'] = $authorData['district'] ?? null;
        $authorData['post_count'] = $authorData['post_count'] ?? 0;
        $authorData['group_name'] = $authorData['group_name'] ?? null;

        // Formater la date de création si elle existe
        if (!empty($authorData['created_at'])) {
            try {
                $authorData['created_at_formatted'] = date('d/m/Y', strtotime($authorData['created_at']));
            } catch (\Exception $e) {
                $authorData['created_at_formatted'] = $authorData['created_at'];
            }
        }

        return $authorData;
    }
}