<?php

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Utils\Show;
use Core\Lang;
use Utils\Util;
use Core\Router\Router;
use Blog\Entities\NewsManager;
use Blog\Entities\CategoriesManager;
use Blog\Entities\Comment;
use Core\Auth\UsersManager;

/**
 * @copyright (C) 2023, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 *
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class BlogReadController extends PublicController
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->runPlugin) {
            $this->runPlugin = $this->pluginsManager->getPlugin('blog');
        }
    }

    public function read($name, $id)
    {
        $newsManager = new NewsManager();
        $categoriesManager = new CategoriesManager();

        $antispamData = $this->core->callHook('antispam:get-captcha', ['field_html' => '']);
        $antispamField = $antispamData['field_html'] ?? '';

        $item = $newsManager->create($id);
        if (!$item) {
            $this->core->error404();
        }

        // Si l'article est en brouillon, seuls les administrateurs peuvent le prévisualiser
        $isDraft = ((string)$item->getDraft() === "1" || (int)$item->getDraft() === 1);
        if ($isDraft) {
            $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
            $ctx = new \Core\Navigation\ConditionContext($this->user);
            if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('blog:admin'), $ctx)) {
                $this->core->error404();
            }
        }

        $newsManager->loadComments($item->getId());
        $this->addMetas($item);

        // Traitements divers : métas, fil d'ariane...
        $response = new PublicResponse();
        $response->setTitle($item->getName());
        
        // Injecter la description courte de l'article dans le hero (siteDesc)
        $this->core->setConfigVal('siteDesc', $item->getSEODesc());

        $generatedHTML = Util::generateIdForTitle(htmlspecialchars_decode($item->getParsedContent()));
        $toc = $this->generateTOC($generatedHTML);

        $categories = [];
        $itemId = (int) $item->getId();
        foreach ($categoriesManager->getCategories() as $cat) {
            // Convertir les items en entiers pour la comparaison stricte
            $catItems = array_map('intval', $cat->items ?? []);
            if (in_array($itemId, $catItems, true)) {
                $cleanLabel = html_entity_decode($cat->label, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $categories[] = [
                    'label' => $cleanLabel,
                    'url' => $this->router->generate('blog-category', ['name' => Util::strToUrl($cleanLabel), 'id' => $cat->id]),
                ];
            }
        }

        $tpl = $response->createPluginTemplate('blog', 'read');

        Show::addSidebarPublicModule('Catégories du blog', $this->generateCategoriesSidebar());
        Show::addSidebarPublicModule('Derniers commentaires', $this->generateLastCommentsSidebar());

        $tpl->set('antispamField', $antispamField);
        $tpl->set('antispam', !empty($antispamField));
        $tpl->set('item', $item);
        $tpl->set('formattedDate', Lang::get('blog.posted-date', $item->getReadableDate()));
        $tpl->set('generatedHtml', $generatedHTML);
        $tpl->set('TOC', $toc ?: '');
        $tpl->set('categories', $categories);
        $tpl->set('newsManager', $newsManager);
        $tpl->set('commentSendUrl', $this->router->generate('blog-send'));
        $tpl->set('isDraft', $isDraft);
        // Passer l'état d'autorisation au template
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        $tpl->set('isLogged', \Core\Navigation\AccessPolicyEvaluator::getInstance()->canAccess(new \Core\Navigation\GenericResource('blog:comment'), $ctx));
        // Vérifier si le plugin SEO est activé et si au moins un réseau est configuré
        $seoActive = $this->pluginsManager->isActivePlugin('seo');
        $tpl->set('seoActive', $seoActive);
        $blogShareUrl = \Utils\Util::getCurrentURL();
        $tpl->set('fb_share_url', 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($blogShareUrl));
        $tpl->set('HIDE_PAGE_TITLE', true);

        // Récupération des likes
        $likesCount = 0;
        $isLiked = false;
        try {
            $pdo = \Core\Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM blog_likes WHERE post_id = :pid");
            $stmt->execute([':pid' => $itemId]);
            $likesCount = (int)$stmt->fetchColumn();

            $userId = $this->user ? (int)$this->user->id : null;
            $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            if ($userId) {
                $stmtCheck = $pdo->prepare("SELECT 1 FROM blog_likes WHERE post_id = :pid AND user_id = :uid LIMIT 1");
                $stmtCheck->execute([':pid' => $itemId, ':uid' => $userId]);
                $isLiked = (bool)$stmtCheck->fetchColumn();
            } else {
                $stmtCheck = $pdo->prepare("SELECT 1 FROM blog_likes WHERE post_id = :pid AND ip_hash = :ip LIMIT 1");
                $stmtCheck->execute([':pid' => $itemId, ':ip' => $ipHash]);
                $isLiked = (bool)$stmtCheck->fetchColumn();
            }
        } catch (\Throwable $e) {
            // Table non créée ou fallback
        }
        $tpl->set('likes_count', $likesCount);
        $tpl->set('is_liked', $isLiked);

        $response->addTemplate($tpl);
        return $response;

    }

    public function like($id)
    {
        header('Content-Type: application/json');
        $postId = (int)$id;
        $userId = $this->user ? (int)$this->user->id : null;
        $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

        try {
            $pdo = \Core\Database::getInstance()->getPdo();
            if ($userId) {
                $stmt = $pdo->prepare("SELECT id FROM blog_likes WHERE post_id = :pid AND user_id = :uid LIMIT 1");
                $stmt->execute([':pid' => $postId, ':uid' => $userId]);
                $existing = $stmt->fetchColumn();

                if ($existing) {
                    $del = $pdo->prepare("DELETE FROM blog_likes WHERE id = :id");
                    $del->execute([':id' => $existing]);
                    $liked = false;
                } else {
                    $ins = $pdo->prepare("INSERT INTO blog_likes (post_id, user_id, ip_hash, created_at) VALUES (:pid, :uid, :ip, NOW())");
                    $ins->execute([':pid' => $postId, ':uid' => $userId, ':ip' => $ipHash]);
                    $liked = true;
                }
            } else {
                $stmt = $pdo->prepare("SELECT id FROM blog_likes WHERE post_id = :pid AND ip_hash = :ip LIMIT 1");
                $stmt->execute([':pid' => $postId, ':ip' => $ipHash]);
                $existing = $stmt->fetchColumn();

                if ($existing) {
                    $del = $pdo->prepare("DELETE FROM blog_likes WHERE id = :id");
                    $del->execute([':id' => $existing]);
                    $liked = false;
                } else {
                    $ins = $pdo->prepare("INSERT INTO blog_likes (post_id, user_id, ip_hash, created_at) VALUES (:pid, NULL, :ip, NOW())");
                    $ins->execute([':pid' => $postId, ':ip' => $ipHash]);
                    $liked = true;
                }
            }

            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM blog_likes WHERE post_id = :pid");
            $countStmt->execute([':pid' => $postId]);
            $count = (int)$countStmt->fetchColumn();

            echo json_encode(['success' => true, 'liked' => $liked, 'likes_count' => $count]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    protected function generateTOC($html)
    {
        $displayTOC = $this->runPlugin->getConfigVal('displayTOC');
        $toc = false;

        if ($displayTOC === 'content') {
            $toc = Util::generateTableOfContents($html, Lang::get('blog-toc-title'));
            if (!$toc) {
                return false;
            }
        } elseif ($displayTOC === 'sidebar') {
            $toc = Util::generateTableOfContentAsModule($html);
            if ($toc) {
                Show::addSidebarPublicModule(Lang::get('blog-toc-title'), $toc);
                return false;
            }
        }
        return $toc;
    }

    protected function addMetas($item)
    {
        $currentUrl = Util::getCurrentURL();
        $this->core->addMeta('<meta property="og:site_name" content="Vivre Bourges" />');

        // Facebook App ID si configuré
        $authConfFile = ROOT . 'data' . DIRECTORY_SEPARATOR . 'plugin' . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'auth_conf.json';
        if (file_exists($authConfFile)) {
            $authConf = json_decode(@file_get_contents($authConfFile), true);
            if (!empty($authConf['facebook_app_id'])) {
                $this->core->addMeta('<meta property="fb:app_id" content="' . htmlspecialchars(trim($authConf['facebook_app_id']), ENT_QUOTES, 'UTF-8') . '" />');
            }
        }

        $this->core->addMeta('<meta property="og:url" content="' . $currentUrl . '" />');
        $this->core->addMeta('<meta property="twitter:url" content="' . $currentUrl . '" />');
        $this->core->addMeta('<meta property="og:type" content="article" />');
        $this->core->addMeta('<meta property="og:title" content="' . htmlspecialchars($item->getName(), ENT_QUOTES, 'UTF-8') . '" />');
        $this->core->addMeta('<meta name="twitter:title" content="' . htmlspecialchars($item->getName(), ENT_QUOTES, 'UTF-8') . '" />');
        $this->core->addMeta('<meta property="og:description" content="' . htmlspecialchars($item->getSEODesc(), ENT_QUOTES, 'UTF-8') . '" />');
        $this->core->addMeta('<meta name="twitter:description" content="' . htmlspecialchars($item->getSEODesc(), ENT_QUOTES, 'UTF-8') . '" />');
        $this->core->addMeta('<meta name="twitter:card" content="summary_large_image" />');

        $imgUrl = '';
        $rawImg = $item->getImg();
        if (!empty($rawImg)) {
            if (str_starts_with($rawImg, 'http://') || str_starts_with($rawImg, 'https://')) {
                $imgUrl = $rawImg;
            } elseif (str_starts_with($rawImg, 'data/')) {
                $imgUrl = Util::urlBuild($rawImg);
            } elseif ($this->pluginsManager->isActivePlugin('galerie') && galerie::searchByfileName($rawImg)) {
                $imgUrl = Util::urlBuild(UPLOAD . 'galerie/' . $rawImg);
            } else {
                $imgUrl = Util::urlBuild(UPLOAD . 'blog/' . $rawImg);
            }
        }

        // Fallback image si aucune illustration d'article
        if (empty($imgUrl)) {
            $currentTheme = $this->core->getConfigVal('theme') ?: 'VivreBourges';
            $imgUrl = Util::urlBuild('theme/' . $currentTheme . '/logo.webp');
            if (!file_exists(ROOT . 'theme/' . $currentTheme . '/logo.webp')) {
                $imgUrl = Util::urlBuild('theme/' . $currentTheme . '/logo.png');
            }
        }

        if (!empty($imgUrl)) {
            $ogImgUrl = $imgUrl;
            $localRel = preg_replace('#^https?://[^/]+/#', '', $imgUrl);
            $localPath = ROOT . str_replace('/', DIRECTORY_SEPARATOR, $localRel);

            // Préférer le JPG pour les réseaux sociaux (Facebook, WhatsApp) si disponible
            if (str_ends_with(strtolower($localPath), '.webp')) {
                $jpgPath = substr($localPath, 0, -5) . '.jpg';
                if (file_exists($jpgPath)) {
                    $ogImgUrl = substr($imgUrl, 0, -5) . '.jpg';
                    $localPath = $jpgPath;
                }
            }

            $this->core->addMeta('<meta property="og:image" content="' . htmlspecialchars($ogImgUrl, ENT_QUOTES, 'UTF-8') . '" />');
            $this->core->addMeta('<meta property="og:image:secure_url" content="' . htmlspecialchars($ogImgUrl, ENT_QUOTES, 'UTF-8') . '" />');
            $this->core->addMeta('<meta name="twitter:image" content="' . htmlspecialchars($ogImgUrl, ENT_QUOTES, 'UTF-8') . '" />');

            // Métadonnées d'\''image si le fichier est local
            if (file_exists($localPath)) {
                $imageInfo = @getimagesize($localPath);
                if ($imageInfo) {
                    $this->core->addMeta('<meta property="og:image:width" content="' . (int)$imageInfo[0] . '" />');
                    $this->core->addMeta('<meta property="og:image:height" content="' . (int)$imageInfo[1] . '" />');
                    if (!empty($imageInfo['mime'])) {
                        $this->core->addMeta('<meta property="og:image:type" content="' . htmlspecialchars($imageInfo['mime'], ENT_QUOTES, 'UTF-8') . '" />');
                    }
                }
            }
        }
    }

    protected function generateLastCommentsSidebar(int $nbComments = 10)
    {
        $comments = NewsManager::getLatestComments($nbComments);
        $str = '<ul class="comments-recent-list">';
        foreach ($comments as $comment) {
            $str .= "<li class='comment-recent'>";
            $str .= "<span class='comment-recent-author'>";
            if ($comment['comment']->getAuthorWebsite()) {
                $str .= "<a href='" . $comment['comment']->getAuthorWebsite() . "'>" . $comment['comment']->getAuthor() . "</a>";
            } else {
                $str .= $comment['comment']->getAuthor();
            }
            $str .= "</span> ";
            $str .= Lang::get('blog.comments.in');
            $str .= " <span class='comment-recent-news'>";
            $str .= "<a href='" . $comment['news']->getUrl() . "#comment" . $comment['comment']->getId() . "'>" . $comment['news']->getName() . "</a></span>";
            $str .= "</li>";
        }
        $str .= "</ul>";
        return $str;
    }

    public function send()
    {
        $newsManager = new NewsManager();
        // quelques contrôle et temps mort volontaire avant le send...
        sleep(2);

        $currentUser = UsersManager::getCurrentUser();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($currentUser);
        $canComment = $evaluator->canAccess(new \Core\Navigation\GenericResource('blog:comment'), $ctx);

        if ($this->runPlugin->getConfigVal('comments')) {
            
            // Validation conditions
            $honeypotValid = true;
            $antispamValid = true;

            if (!$canComment) {
                // Check honeypot
                $honeypotValid = (isset($_POST['_author']) && $_POST['_author'] == '');
                
                // Check captcha
                $antispamResult = $this->core->callHook('antispam:validate', ['is_valid' => true]);
                if (!$antispamResult['is_valid']) {
                    $antispamValid = false;
                }
            }

            if ($honeypotValid && $antispamValid) {
                $idNews = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?? 0;
                $item = $newsManager->create($idNews);
                if ($item && $item->getCommentsOff() == false) {
                    $newsManager->loadComments($idNews);
                    $comment = new Comment();
                    $comment->setIdNews($idNews);

                    if ($canComment) {
                        // Utilisateur connecté : utiliser ses données
                        $comment->setAuthor($currentUser->username());
                        $comment->setAuthorEmail($currentUser->email);
                        // Pas de website pour les utilisateurs auth
                        $comment->setAuthorWebsite(null);
                    } else {
                        // Fallback pour utilisateurs non connectés
                        $comment->setAuthor($_POST['author'] ?? 'Anonyme');
                        $email = filter_input(INPUT_POST, 'authorEmail', FILTER_VALIDATE_EMAIL);
                        if ($email !== false) {
                            $comment->setAuthorEmail($_POST['authorEmail']);
                        } else {
                            Show::msg(Lang::get("blog.comments.bad-mail"), 'error');
                            header('location:' . $_POST['back']);
                            die();
                        }
                        $comment->setAuthorWebsite(filter_input(INPUT_POST, 'authorWebsite', FILTER_VALIDATE_URL) ?? null);
                    }
                    $comment->setDate('');

                    // Sanitize Input (XSS Protection)
                    require_once COMMON . 'Utils/Sanitizer.php';
                    $comment->setContent(\Utils\Sanitizer::cleanHtml($_POST['commentContent']));

                    $parentId = filter_input(INPUT_POST, 'commentParentId', FILTER_VALIDATE_INT) ?? 0;
                    if ($parentId !== 0) {
                        $newsManager->addReplyToComment($comment, $parentId);
                    }
                    if ($newsManager->saveComment($comment)) {
                        // Hook de domaine pour notification
                        $this->core->callHook('blog:comment-saved', [
                            'item' => $item,
                            'comment' => $comment,
                            'actor' => $canComment ? [
                                'id' => $currentUser->attributes['id'],
                                'name' => $currentUser->username()
                            ] : [
                                'id' => null,
                                'name' => $comment->getAuthor()
                            ]
                        ]);
                        
                        header('location:' . $_POST['back'] . '#comment' . $comment->getId());
                        die();
                    }
                }

            } else {
                Show::msg(Lang::get("antispam.invalid-captcha"), 'error');
            }
        }
        header('location:' . $_POST['back']);
        die();
    }

    public function rss()
    {
        $newsManager = new NewsManager();
        echo $newsManager->rss();
        die();
    }

    protected function generateCategoriesSidebar()
    {
        $content = '';
        $categoriesManager = new CategoriesManager();
        $categories = $categoriesManager->getNestedCategories();
        if (empty($categories)) {
            return false;
        }
        $content .= '<ul>';
        foreach ($categories as $category) {
            $content .= $this->generateCategorySidebar($category);
        }
        $content .= '</ul>';
        return $content;
    }

    protected function generateCategorySidebar($category)
    {
        $router = Router::getInstance();
        $cleanLabel = html_entity_decode($category->label, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $content = '<li><a href="' . $router->generate('blog-category', ['name' => Util::strToUrl($cleanLabel), 'id' => $category->id]) . '">' .
            htmlspecialchars($cleanLabel, ENT_QUOTES, 'UTF-8') . '</a>';
        if (!empty($category->children)) {
            $content .= '<ul>';
            foreach ($category->children as $child) {
                $content .= $this->generateCategorySidebar($child);
            }
            $content .= '</ul>';
        }
        $content .= '</li>';
        return $content;
    }
}