<?php

namespace Forum\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Lang;
use Core\Security\Csrf;
use Utils\Show;
use Utils\Util;

defined('ROOT') or exit('Access denied!');

class ForumAdminController extends AdminController
{
    public function home()
    {
        $categoryManager = new \Forum\Entities\CategoryManager();
        $topicManager = new \Forum\Entities\TopicManager();
        $postManager = new \Forum\Entities\PostManager();

        $rootCategories = $categoryManager->getAllWithSubcategories();
        $categoriesData = [];
        foreach ($rootCategories as $cat) {
            $categoriesData[] = $this->prepareCategoryData($cat);
        }

        // Aplatir les catégories (niveau 1 & 2) pour le formulaire rapide d'ajout
        $parentCategories = [];
        foreach ($this->flattenCategories($rootCategories) as $catItem) {
            if ($catItem['level'] <= 2) {
                $parentCategories[] = $catItem;
            }
        }

        // Compter le nombre total de catégories tous niveaux confondus
        $totalCategoriesCount = count($this->flattenCategories($rootCategories));

        $pendingPostsEntities = $postManager->getPending();
        $pendingPosts = [];
        foreach ($pendingPostsEntities as $post) {
            $pData = $post->toArray();
            $topic = $topicManager->getById($post->getTopicId());
            $pData['topic_title'] = $topic ? $topic->getTitle() : '';
            $pData['topic_url'] = $topic ? $topic->getUrl() : '#';
            $pData['created_at_formatted'] = Util::FormatDate($post->getCreatedAt(), 'fr', 'fr');
            $pendingPosts[] = $pData;
        }

        $orphanSubcatsCount = count($categoryManager->getOrphanSubcategories());
        $orphanTopicsCount = count($topicManager->getOrphanTopics());
        $orphanPostsCount = count($postManager->getOrphanPosts());

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('forum', 'admin-home');

        $tpl->set('categoriesCount', $totalCategoriesCount);
        $tpl->set('topicsCount', $topicManager->countAll());
        $tpl->set('postsCount', $postManager->countAll());
        $tpl->set('pendingCount', count($pendingPosts));
        $tpl->set('pendingPosts', $pendingPosts);

        $tpl->set('orphanSubcatsCount', $orphanSubcatsCount);
        $tpl->set('orphanTopicsCount', $orphanTopicsCount);
        $tpl->set('orphanPostsCount', $orphanPostsCount);
        $tpl->set('totalOrphansCount', $orphanSubcatsCount + $orphanTopicsCount + $orphanPostsCount);

        $tpl->set('categories', $categoriesData);
        $tpl->set('parentCategories', $parentCategories);
        $tpl->set('categoriesUrl', $this->router->generate('admin-forum-categories'));
        $tpl->set('moderationUrl', $this->router->generate('admin-forum-moderate'));
        $tpl->set('maintenanceUrl', $this->router->generate('admin-forum-maintenance'));
        $tpl->set('maintenanceRunUrl', $this->router->generate('admin-forum-maintenance-run'));
        $tpl->set('approveUrl', $this->router->generate('admin-forum-moderate-approve'));
        $tpl->set('deleteModerationUrl', $this->router->generate('admin-forum-moderate-delete'));
        $tpl->set('publicUrl', $this->router->generate('forum-index'));
        $tpl->set('configSaveUrl', $this->router->generate('admin-forum-config-save'));
        $tpl->set('addCategoryUrl', $this->router->generate('admin-forum-categories-add-send'));

        $tpl->set('moderatePosts', $this->runPlugin->getConfigVal('moderatePosts') == '1');
        $tpl->set('allowAnonymous', $this->runPlugin->getConfigVal('allowAnonymous') == '1');
        $tpl->set('enableLikes', $this->runPlugin->getConfigVal('enableLikes') != '0');
        $tpl->set('enableNotifications', $this->runPlugin->getConfigVal('enableNotifications') != '0');
        $tpl->set('topicsPerPage', $this->runPlugin->getConfigVal('topicsPerPage') ?: 20);
        $tpl->set('postsPerPage', $this->runPlugin->getConfigVal('postsPerPage') ?: 15);
        $tpl->set('level1Label', $this->runPlugin->getConfigVal('level1Label') ?: 'Catégorie');
        $tpl->set('level2Label', $this->runPlugin->getConfigVal('level2Label') ?: 'Sous-catégorie');
        $tpl->set('level3Label', $this->runPlugin->getConfigVal('level3Label') ?: 'Salon de discussion');
        $tpl->set('csrfToken', Csrf::token());

        $response->addTemplate($tpl);
        return $response;
    }

    private function prepareCategoryData(\Forum\Entities\Category $category): array
    {
        $data = $category->toArray();
        $data['editUrl'] = $this->router->generate('admin-forum-categories-edit', ['id' => $category->getId()]);
        $data['deleteUrl'] = $this->router->generate('admin-forum-categories-delete', ['id' => $category->getId()]);
        $data['subcategories'] = [];
        foreach ($category->getSubcategories() as $sub) {
            $data['subcategories'][] = $this->prepareCategoryData($sub);
        }
        return $data;
    }

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
            $subs = $cat->getSubcategories();
            if (!empty($subs)) {
                $flat = array_merge($flat, $this->flattenCategories($subs, $level + 1));
            }
        }
        return $flat;
    }

    public function saveConfig()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        $csrf = $_POST['_csrf'] ?? $_POST['token'] ?? null;
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('forum:admin'), $ctx) || !Csrf::validate($csrf, false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect($this->router->generate('admin-forum-home'));
        }

        $this->runPlugin->setConfigVal('moderatePosts', isset($_POST['moderate_posts']) ? '1' : '0');
        $this->runPlugin->setConfigVal('allowAnonymous', isset($_POST['allow_anonymous']) ? '1' : '0');
        $this->runPlugin->setConfigVal('enableLikes', isset($_POST['enable_likes']) ? '1' : '0');
        $this->runPlugin->setConfigVal('enableNotifications', isset($_POST['enable_notifications']) ? '1' : '0');

        $topicsPerPage = max(5, min(100, (int) ($_POST['topics_per_page'] ?? 20)));
        $postsPerPage = max(5, min(100, (int) ($_POST['posts_per_page'] ?? 15)));
        $this->runPlugin->setConfigVal('topicsPerPage', (string) $topicsPerPage);
        $this->runPlugin->setConfigVal('postsPerPage', (string) $postsPerPage);

        $this->runPlugin->setConfigVal('level1Label', trim($_POST['level1_label'] ?? 'Catégorie') ?: 'Catégorie');
        $this->runPlugin->setConfigVal('level2Label', trim($_POST['level2_label'] ?? 'Sous-catégorie') ?: 'Sous-catégorie');
        $this->runPlugin->setConfigVal('level3Label', trim($_POST['level3_label'] ?? 'Salon de discussion') ?: 'Salon de discussion');

        if ($this->pluginsManager->savePluginConfig($this->runPlugin)) {
            Show::msg(Lang::get('core-changes-saved'), 'success');
        } else {
            Show::msg(Lang::get('core-changes-not-saved'), 'error');
        }

        return $this->core->redirect($this->router->generate('admin-forum-home'));
    }
}
