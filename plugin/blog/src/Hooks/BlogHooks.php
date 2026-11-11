<?php

namespace Blog\Hooks;

use Core\Core;
use Core\Router\Router;
use Core\Lang;
use Utils\Util;
use Blog\Entities\NewsManager;
use Blog\Entities\CategoriesManager;

defined('ROOT') or exit('Access denied!');

class BlogHooks
{
    /**
     * Registers all hooks for the Blog plugin.
     */
    /**
     * Registers all hooks for the Blog plugin.
     * @deprecated Now handled via hooks.json
     */
    public static function registerHooks(): void
    {
        // Hooks are now registered via hooks.json
    }

    /**
     * Installation logic
     */
    public static function install(): void
    {
        // No SQL install needed for Blog (JSON based)
        // Check writable folders ?
        $dataDir = (defined('DATA_PLUGIN') ? DATA_PLUGIN : ROOT . 'data/plugin/') . 'blog/';
        if (!is_dir($dataDir)) {
            @mkdir($dataDir, 0755, true);
        }
    }

    public static function onEndFrontHead($param = ''): string
    {
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('blog');
        if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
            return (string)$param;
        }

        $core = Core::getInstance();
        $siteUrl = $core->getConfigVal('siteUrl');
        $output = (string)$param;
        $output .= '<link rel="stylesheet" href="' . $siteUrl . '/plugin/blog/template/public.css?v=' . VERSION . '">' . "\n";
        $output .= '<link rel="alternate" type="application/rss+xml" href="' . Router::getInstance()->generate('blog-rss') . '" title="' . $core->getConfigVal('siteName') . '">' . "\n";
        return $output;
    }

    public static function onBeforeRunPlugin(): void
    {
        if (class_exists('\Content\ContentParser')) {
            \Content\ContentParser::addShortcode('blogLink', [self::class, 'blogLinkShortcode']);
        }
    }

    public static function blogLinkShortcode(array $attributes): string
    {
        $newsManager = new NewsManager();
        $news = $newsManager->create((int) $attributes['id']);
        if (!$news) {
            return '';
        }
        if (!isset($attributes['name'])) {
            $attributes['name'] = $news->getName();
        }
        return '<a href="' . Router::getInstance()->generate('blog-read', ['id' => $news->getId(), 'name' => Util::strToUrl($news->getName())]) . '">' . $attributes['name'] . '</a>';
    }


    /**
     * Hook: user:profile-stats
     * Envoie les statistiques du blog vers le profil utilisateur
     */
    public static function userProfileStats(array $stats): array
    {
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('blog');
        if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
            return $stats;
        }

        $commentCount = 0;
        $user = \Core\Auth\UsersManager::getCurrentUser();
        $userEmail = $user ? ($user->attributes['email'] ?? '') : '';

        if (!empty($userEmail)) {
            $rawComments = Util::readJsonFile(DATA_PLUGIN . 'blog/comments.json');
            if (is_array($rawComments)) {
                foreach ($rawComments as $newsId => $comments) {
                    if (is_array($comments)) {
                        foreach ($comments as $comment) {
                            if (isset($comment['authorEmail']) && $comment['authorEmail'] === $userEmail) {
                                $commentCount++;
                            }
                        }
                    }
                }
            }
        }

        $stats[] = [
            'label' => 'Commentaires sur le Blog',
            'value' => $commentCount,
            'color' => '#e67e22',
            'icon' => 'fa-solid fa-comment-dots'
        ];

        return $stats;
    }

    /**
     * Hook: search:get-results
     * Fournit les résultats du blog pour la recherche globale
     */
    public static function searchResults(array $data): array
    {
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('blog');
        if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
            return $data;
        }

        $query = $data['query'];
        $results = [];
        try {
            $newsManager = new NewsManager();
            $newsList = $newsManager->getItems();
            
            foreach ($newsList as $item) {
                if ($item->getDraft() === "0" && (stripos($item->getName(), $query) !== false || stripos($item->getContent(), $query) !== false)) {
                    $results[] = [
                        'title' => $item->getName(),
                        'excerpt' => substr(strip_tags($item->getContent()), 0, 150) . '...',
                        'url' => Router::getInstance()->generate('blog-read', ['id' => $item->getId(), 'name' => Util::strToUrl($item->getName())]),
                        'icon' => 'fa-solid fa-newspaper',
                        'source' => 'blog',
                        'source_label' => 'Blog',
                        'date' => $item->getDate(),
                        'date_formatted' => date('d/m/Y', strtotime($item->getDate())),
                        'score' => (stripos($item->getName(), $query) !== false ? 10 : 0) + (stripos($item->getContent(), $query) !== false ? 5 : 0)
                    ];
                }
            }
            $data['results'] = array_merge($data['results'], $results);
        } catch (\Exception $e) {
            // Ignorer
        }
        return $data;
    }

    /**
     * Hook: user:notification-preferences
     */
    public static function userNotificationPreferences($user): string
    {
        $notifBlog = $user->attributes['notif_blog'] ?? true;
        
        return '
        <label class="setting-checkbox">
            <input type="checkbox" data-setting="notif_blog" ' . ($notifBlog ? 'checked' : '') . '>
            <span class="checkbox-label">Actualités du Blog</span>
        </label>';
    }

    /**
     * Hook: user:profile-allowed-settings
     */
    public static function userProfileAllowedSettings(array $allowed): array
    {
        $allowed[] = 'notif_blog';
        return $allowed;
    }

    /**
     * Listener: blog:comment-saved
     */
    public static function onCommentSaved(array $data): void
    {
        $item = $data['item'] ?? null;
        $comment = $data['comment'] ?? null;
        $actor = $data['actor'] ?? null;

        if (!$item || !$comment) return;

        // On notifie l'Admin par défaut (ID 1) car il n'y a pas d'article author_id
        // TODO: On pourrait notifier tous les Admins
        $core = \Core\Core::getInstance();
        $core->callHook('notifications:send', [
            'user_id' => 1, 
            'type' => 'blog.comment',
            'source_plugin' => 'blog',
            'data' => [
                'key' => 'blog.comment',
                'params' => [
                    'actor' => $actor['name'] ?? 'Anonyme',
                    'article' => $item->getName()
                ],
                'url' => rtrim($core->getConfigVal('siteUrl'), '/') . $item->getUrl() . '#comment' . $comment->getId(),
                'actor' => $actor,
                'entity' => [
                    'type' => 'news',
                    'id' => $item->getId()
                ]
            ],
            'external_id' => 'blog:comment:' . $item->getId() . ':' . $comment->getId()
        ]);

        // Email Alert to Admin
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('blog');
        $adminEmail = $plugin->getConfigVal('notify_admin_email');
        if (!empty($adminEmail)) {
            $subject = "[" . $core->getConfigVal('siteName') . "] Nouveau commentaire sur " . $item->getName();
            $message = "Un nouveau commentaire a été posté sur l'article : " . $item->getName() . "\n\n";
            $message .= "Auteur : " . ($actor['name'] ?? 'Anonyme') . "\n";
            $message .= "Contenu : \n" . strip_tags($comment->getContent()) . "\n\n";
            $message .= "Lien : " . rtrim($core->getConfigVal('siteUrl'), '/') . $item->getUrl() . "#comment" . $comment->getId();
            
            $headers = "From: " . $core->getConfigVal('siteName') . " <noreply@" . parse_url($core->getConfigVal('siteUrl'), PHP_URL_HOST) . ">";
            @mail($adminEmail, $subject, $message, $headers);
        }
    }

    /**
     * Listener: blog:news-published
     */
    public static function onNewsPublished(array $data): void
    {
        $item = $data['item'] ?? null;
        if (!$item) return;

        \Core\Core::getInstance()->callHook('notifications:send', [
            'user_id' => 0, // Broadcast
            'type' => 'blog.news',
            'source_plugin' => 'blog',
            'data' => [
                'key' => 'blog.news',
                'params' => [
                    'title' => $item->getName()
                ],
                'url' => rtrim(\Core\Core::getInstance()->getConfigVal('siteUrl'), '/') . $item->getUrl(),
                'entity' => [
                    'type' => 'news',
                    'id' => $item->getId()
                ]
            ],
            'external_id' => 'blog:news:' . $item->getId()
        ]);
    }

    /**
     * Hook: core:stats-collector
     */
    public static function collectStats(array $stats): array
    {
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('blog');
        if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
            return $stats;
        }

        try {
            $newsManager = new NewsManager();
            $news = $newsManager->getItems();
            $totalArticles = count($news);
            
            // Filter recent articles
            $recentArticles = [];
            foreach (array_slice($news, 0, 3) as $article) {
                $recentArticles[] = [
                    'id' => $article->getId(),
                    'name' => $article->getName(),
                    'date' => $article->getDate(),
                    'date_formatted' => date('d/m/Y', strtotime($article->getDate())),
                    'description' => substr(strip_tags($article->getIntro() ?: $article->getContent()), 0, 150) . '...'
                ];
            }

            $stats['blog'] = [
                'total_articles' => $totalArticles,
                'recent_articles' => $recentArticles
            ];
        } catch (\Exception $e) {
            // Ignorer
        }
        return $stats;
    }

    /**
     * Hook: SearchUi:home-sections
     */
    public static function homeSections($data = []): array
    {
        // Si $data n'est pas un tableau (ex: null), on l'initialise
        if (!is_array($data)) {
            $data = [];
        }
        // Check if user is logged in
        $isLoggedIn = $data['isLoggedIn'] ?? false;
        
        // If not logged in, we verify if there is a guest override or just return
        if (!$isLoggedIn) {
             return $data;
        }

        try {
            $latestPost = self::getLatestBlogPost();
            if ($latestPost) {
                $html = self::renderLatestPost($latestPost);
                $data['sections'][] = $html;
            }
        } catch (\Exception $e) {
            // Ignorer
        }
        return $data;
    }

    private static function getLatestBlogPost(): ?array
    {
        try {
            $newsManager = new NewsManager();
            $news = $newsManager->getItems();
            if (empty($news)) return null;

            $article = $news[0];
            $baseUrl = \Core\Core::getInstance()->getConfigVal('siteUrl');
            
            $router = \Core\Router\Router::getInstance();
            try {
                $postUrl = $router->generate('blog-read', [
                    'name' => Util::strToUrl($article->getName()),
                    'id' => $article->getId()
                ]);
            } catch (\Exception $e) {
                // Fallback format
                $postUrl = '/blog/' . Util::strToUrl($article->getName()) . '-' . $article->getId() . '.html';
            }

            // Basic data
            $data = [
                'id' => $article->getId(),
                'title' => $article->getName(),
                'url' => $postUrl,
                'date' => $article->getDate(),
                'date_formatted' => date('d/m/Y', strtotime($article->getDate())),
                'img' => $article->getImg(),
                'imgUrl' => $article->getImg() ? $baseUrl . '/' . str_replace('./', '', UPLOAD) . 'news/' . $article->getImg() : null,
                'content' => $article->getContent(),
                'excerpt' => $article->getIntro(),
                'commentsOff' => (bool)$article->getCommentsOff(),
                'comments_count' => 0 // Fallback
            ];

            return $data;
        } catch (\Exception $e) {
            return null;
        }
    }

    private static function renderLatestPost(array $post): string
    {
        $html = '<details class="blog-latest-post-details">';
        $html .= '<summary class="blog-latest-post-summary">';
        $html .= '<h3 class="blog-latest-post-header">';
        $html .= '<i class="fa-solid fa-newspaper" style="color: #007bff;"></i>'; // Icon color kept for now or move to CSS
        $html .= '<span>Le dernier article du blog</span></h3>';
        $html .= '<i class="fa-solid fa-chevron-down" style="color: #666; transition: transform 0.2s;"></i></summary>';
        
        $html .= '<div id="content" class="blog">';
        $html .= '<article><header style="margin-top: 1.5rem;">'; // Small margin-top kept or move to CSS
        
        if ($post['img']) {
            $html .= '<a href="' . $post['url'] . '"><img class="featured blog-latest-post-img" src="' . $post['imgUrl'] . '" alt="' . htmlspecialchars($post['title']) . '" style="max-width: 100%; width: 100%; height: auto;"/></a>';
        }
        
        $html .= '<div class="item-head">';
        $html .= '<h2 class="blog-latest-post-title"><a href="' . $post['url'] . '">' . htmlspecialchars($post['title']) . '</a></h2>';
        $html .= '<div class="meta blog-latest-post-meta">';
        $html .= '<span class="date"><i class="fa-regular fa-calendar"></i> ' . $post['date_formatted'] . '</span>';
        if (!$post['commentsOff']) {
            $html .= '<span class="comments"><i class="fa-regular fa-comments"></i> ' . $post['comments_count'] . ' commentaire(s)</span>';
        }
        $html .= '</div></div></header>';
        
        $html .= '<div class="excerpt blog-latest-post-excerpt">';
        $html .= htmlspecialchars_decode($post['excerpt'] ?: $post['content']);
        $html .= '</div>';
        $html .= '<a href="' . $post['url'] . '" class="blog-latest-post-readmore">Lire la suite &rarr;</a>';
        $html .= '</article></div></details>';
        
        return $html;
    }

    /**
     * Hook: dashboard:inject-widgets
     * Injecte les derniers articles du blog dans le dashboard
     */
    public static function onDashboardInjectWidgets($widgets)
    {
        if (!is_array($widgets)) {
            $widgets = [];
        }
        
        $router = Router::getInstance();
        $lastNewsPrepared = [];
        
        try {
            if (class_exists('\Blog\Entities\NewsManager')) {
                $newsManager = new NewsManager();
                $newsItems = $newsManager->getItems();
                $lastNewsRaw = array_slice($newsItems, 0, 5);

                foreach ($lastNewsRaw as $k => $item) {
                    if (is_object($item)) {
                        $lastNewsPrepared[] = [
                            'name' => $item->getName(),
                            'intro' => Util::cutStr($item->getIntro(), 150),
                            'url' => $item->getUrl(),
                            'imgUrl' => $item->getImgUrl(),
                            'active' => ($k === 0),
                            'index' => $k,
                            'fallback' => false
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Erreur lors du chargement des news
        }
        
        // Fallback si aucune news
        if (empty($lastNewsPrepared)) {
            try {
                $url = $router->generate('blog-home');
            } catch (\Exception $e) {
                $url = '#';
            }
            
            $lastNewsPrepared[] = [
                'name' => 'Aucune actualité pour le moment',
                'intro' => 'Retrouvez tous nos articles sur la page dédiée au blog de l\'Atelier.',
                'url' => $url,
                'imgUrl' => null,
                'active' => true,
                'index' => 0,
                'fallback' => true
            ];
        }
        
        $widgets['lastNews'] = $lastNewsPrepared;
        return $widgets;
    }

    /**
     * Hook: blog:get-latest-post
     * Retourne les données du dernier article de blog
     */
    public static function onGetLatestPost(array $data): array
    {
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('blog');
        
        if ($plugin && $plugin->isInstalled() && $plugin->getConfigVal('activate')) {
            try {
                if (class_exists('\Blog\Entities\NewsManager')) {
                    $newsManager = new NewsManager();
                    $news = $newsManager->getItems();
                    if (!empty($news)) {
                        // Trier par date décroissante
                        usort($news, function ($a, $b) {
                            return strcmp($b->getDate(), $a->getDate());
                        });
    
                        $firstNews = $news[0];
                        $newsId = $firstNews->getId();
                        $newsName = $firstNews->getName();
    
                        // Tentative de génération d'URL
                        $router = Router::getInstance();
                        $postUrl = '';
                        try {
                            $postUrl = $router->generate('blog-read', [
                                'name' => Util::strToUrl($newsName ?: ''),
                                'id' => $newsId
                            ]);
                        } catch (\Exception $e) {
                            $postUrl = '/blog/' . Util::strToUrl($newsName ?: '') . '-' . $newsId . '.html';
                        }
    
                        // Extraire les catégories
                        $categories = [];
                        if (!empty($firstNews->categories) && is_array($firstNews->categories)) {
                            foreach ($firstNews->categories as $cat) {
                                $categories[] = [
                                    'label' => is_array($cat) ? ($cat['label'] ?? '') : (string) $cat,
                                    'url' => is_array($cat) ? ($cat['url'] ?? '#') : '#'
                                ];
                            }
                        }
    
                        // Récupérer l'extrait/intro
                        $excerpt = '';
                        if (method_exists($firstNews, 'getIntro') && $firstNews->getIntro()) {
                            $excerpt = strip_tags(html_entity_decode($firstNews->getIntro(), ENT_QUOTES, 'UTF-8'));
                        } else {
                            $content = method_exists($firstNews, 'getContent') ? $firstNews->getContent() : '';
                            $excerpt = substr(strip_tags(html_entity_decode($content, ENT_QUOTES, 'UTF-8')), 0, 250) . '...';
                        }
    
                        $data['latest_post'] = [
                            'id' => $newsId,
                            'title' => $newsName,
                            'url' => $postUrl,
                            'date' => $firstNews->getDate(),
                            'date_formatted' => method_exists($firstNews, 'getDate') ? date('d/m/Y', strtotime($firstNews->getDate())) : '',
                            'excerpt' => $excerpt,
                            'content' => method_exists($firstNews, 'getContent') ? $firstNews->getContent() : '',
                            'categories' => $categories,
                            'img' => $firstNews->getImg() ?? null,
                            'imgUrl' => $firstNews->getImg() ? '/data/blog/' . $firstNews->getImg() : null,
                            'comments_count' => method_exists($newsManager, 'countComments') ? $newsManager->countComments($newsId) : 0,
                            'commentsOff' => method_exists($firstNews, 'getCommentsOff') ? $firstNews->getCommentsOff() : false
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Ignorer
            }
        }
    
        return $data;
    }
}
