<?php

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Responses\ApiResponse;
use Content\Editor;
use Utils\Show;
use Core\Lang;
use Blog\Entities\NewsManager;
use Blog\Entities\CategoriesManager;
use Blog\Entities\News;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class BlogAdminPostsController extends AdminController
{

    public CategoriesManager $categoriesManager;

    public NewsManager $newsManager;

    public function __construct()
    {
        parent::__construct();
        $this->categoriesManager = new CategoriesManager();
        $this->newsManager = new NewsManager();
    }

    public function list()
    {
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('blog', 'admin-home');

        $tpl->set('activeTab', 'posts');
        $tpl->set('newsManager', $this->newsManager);
        $tpl->set('posts', $this->newsManager->getItems());
        $tpl->set('categoriesManager', $this->categoriesManager);
        $tpl->set('token', $this->user->token);

        $response->addTemplate($tpl);
        return $response;
    }

    public function deletePost()
    {
        $response = new ApiResponse();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('blog:admin'), $ctx)) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            return $this->list();
        }
        $newsManager = new NewsManager();
        $id = (int) $this->jsonData['id'] ?? 0;
        $item = $newsManager->create($id);
        if (!$item) {
            $response->status = ApiResponse::STATUS_NOT_FOUND;
            return $response;
        }
        $title = $item->getName();
        if ($newsManager->delNews($item)) {
            \Core\Core::getInstance()->getLogger()->log('INFO', $this->user->email . ' deleted post ' . $title);
            $cacheManager = new \Core\Storage\CacheManager();
            $cacheManager->clearCache();
            $this->core->invalidateAllCache();
            $response->status = ApiResponse::STATUS_NO_CONTENT;
        } else {
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
        }
        return $response;
    }

    public function editPost($id = false)
    {
        if ($id === false) {
            $news = new News();
            $news->setDate(date('Y-m-d'));
        } else {
            $news = $this->newsManager->create($id);
            if ($news === false) {
                // News id dont exist
                Show::msg(Lang::get('blog-item-dont-exist'), 'error');
                $this->core->redirect($this->router->generate('admin-blog-list'));
            }
        }
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('blog', 'admin-edit');

        $contentEditor = new Editor('blogContent', $news->getContent(), Lang::get('blog-content'));

        $tpl->set('contentEditor', $contentEditor);
        $tpl->set('news', $news);
        $tpl->set('categoriesManager', $this->categoriesManager);
        $tpl->set('token', $this->user->token);

        $response->addTemplate($tpl);
        return $response;
    }

    public function savePost()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('blog:admin'), $ctx)) {
            return $this->list();
        }

        $contentEditor = new Editor('blogContent', '', Lang::get('blog-content'));

        $news = ($_REQUEST['id']) ? $this->newsManager->create($_REQUEST['id']) : new News();
        $news->setName($_REQUEST['name']);
        $news->setContent($contentEditor->getPostContent());
        $news->setIntro($this->core->callHook('beforeSaveEditor', htmlspecialchars($_REQUEST['intro'])));
        $news->setSEODesc($_REQUEST['seoDesc']);
        $news->setDraft((isset($_POST['draft']) ? 1 : 0));
        if (!isset($_REQUEST['date']) || $_REQUEST['date'] == "") {
            $news->setDate($news->getDate());
        } else {
            $news->setDate($_REQUEST['date']);
        }
        $imgInput = trim($this->request->post('news-image', ''));
        if ($imgInput !== '' && preg_match('~^https?://~i', $imgInput)) {
            $siteUrl = \Core\Core::getInstance()->getConfigVal('siteUrl');
            $isOwnDomain = !empty($siteUrl) && str_starts_with($imgInput, $siteUrl);
            if (!$isOwnDomain) {
                // Rapatriement automatique, conversion en WebP et stockage local sécurisé
                $uploadDir = UPLOAD . 'blog' . DS;
                $optimizedPath = \Utils\ImageOptimizer::downloadAndOptimizeUrl(
                    $imgInput,
                    $uploadDir,
                    \Utils\Util::strToUrl($_REQUEST['name'] ?? 'blog')
                );
                if ($optimizedPath) {
                    $imgInput = $optimizedPath;
                }
            }
        }
        $news->setImg($imgInput);
        $news->setCommentsOff(isset($_POST['commentsOff']) ? 1 : 0);
        if ($this->newsManager->saveNews($news)) {
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
            CategoriesManager::saveItemToCategories($news->getId(), $choosenCats);
            $cacheManager = new \Core\Storage\CacheManager();
            $cacheManager->clearCache();
            $this->core->invalidateAllCache();
            Show::msg(Lang::get('core-changes-saved'), 'success');
        } else {
            Show::msg(Lang::get('core-changes-not-saved'), 'error');
        }
        $this->core->redirect($this->router->generate('admin-blog-edit-post', ['id' => $news->getId()]));
    }
}