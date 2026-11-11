<?php

namespace Forum {

use Core\Plugin\PluginsManager;

defined('ROOT') or exit('No direct script access allowed');

class Hooks
{
    public static function install(): void
    {
        $dbDir = DATA_PLUGIN . 'forum';
        if (!is_dir($dbDir)) {
            @mkdir($dbDir, 0755, true);
        }
    }

    public static function endFrontHead($param = ''): string
    {
        $plugin = PluginsManager::getInstance()->getPlugin('forum');
        if (!$plugin || !$plugin->getConfigVal('activate')) {
            return $param;
        }
        
        $baseUrl = rtrim(\Core\Core::getInstance()->getConfigVal('siteUrl'), '/');
        $output = $param;
        
        // Ajouter le CSS
        $output .= '<link rel="stylesheet" href="' . \Utils\Util::urlBuild(PLUGINS . 'forum/template/public.css') . '" type="text/css"/>' . "\n";
        
        // Ajouter HTMX pour les interactions fluides
        $output .= '<script src="https://unpkg.com/htmx.org@1.9.10" integrity="sha384-D1Kt99CQMDuVetoL1lrYwg5t+9QdHe7NLX/SoJYkXDFfX37iInKRy5xLSi8nO7UC" crossorigin="anonymous"></script>' . "\n";
        
        // NOTE: TinyMCE est chargé par le plugin tinymce via tinymceFrontHead
        // Le plugin tinymce détecte automatiquement les pages du forum et charge TinyMCE
        // Pas besoin de charger TinyMCE ici pour éviter les doublons
        
        return $output;
    }

    public static function userProfileStats($stats = []): array
    {
        $user = \Core\Auth\UsersManager::getCurrentUser();
        if (!$user) {
            return $stats;
        }

        try {
            $userId = (int)$user->attributes['id'];
            
            $postManager = new \Forum\Entities\PostManager();
            $topicManager = new \Forum\Entities\TopicManager();
            
            $postCount = \Forum\Entities\Post::query()->where('author_id', '=', $userId)->count();
            $topicCount = \Forum\Entities\Topic::query()->where('author_id', '=', $userId)->count();

            if ($topicCount > 0) {
                $stats[] = [
                    'label' => 'Sujets sur le Forum',
                    'value' => $topicCount,
                    'color' => '#3498db',
                    'icon' => 'fa-solid fa-comments'
                ];
            }

            if ($postCount > 0) {
                $stats[] = [
                    'label' => 'Messages sur le Forum',
                    'value' => $postCount,
                    'color' => '#2980b9',
                    'icon' => 'fa-solid fa-comment'
                ];
            }
        } catch (\Exception $e) {
            // Ignorer
        }

        return $stats;
    }

    public static function userProfileActivity($user): array
    {
        $activities = [];
        if (!$user) {
            return $activities;
        }

        try {
            $posts = \Forum\Entities\Post::query()
                ->where('author_id', '=', $user->attributes['id'])
                ->orderBy('created_at', 'DESC')
                ->limit(5)
                ->get();

            if (!empty($posts)) {
                $items = [];
                foreach ($posts as $post) {
                    $topic = \Forum\Entities\Topic::findPK($post->topic_id);
                    $items[] = [
                        'text' => 'A posté dans : ' . ($topic ? (string)$topic->title : 'Sujet inconnu'),
                        'subtext' => substr(strip_tags($post->content), 0, 100) . '...',
                        'date' => date('d/m/Y', strtotime($post->created_at)),
                        'url' => \Core\Router\Router::getInstance()->generate('forum-topic', ['id' => $post->topic_id, 'slug' => ($topic ? (string)$topic->slug : 'topic')]) . '#p' . $post->id,
                        'icon' => 'fa-solid fa-comment'
                    ];
                }
                $activities[] = [
                    'title' => 'Forum',
                    'items' => $items
                ];
            }
        } catch (\Exception $e) {
            // Ignorer les erreurs
        }

        return $activities;
    }

    public static function searchResults(array $data): array
    {
        $queryText = $data['query'];
        $results = [];
        try {
            $postManager = new \Forum\Entities\PostManager();
            $searchHits = $postManager->search($queryText, 20);

            foreach ($searchHits as $hit) {
                $post = $hit['post'];
                $results[] = [
                    'title' => $hit['topic_title'],
                    'excerpt' => substr(strip_tags($post->content), 0, 150) . '...',
                    'url' => \Core\Router\Router::getInstance()->generate('forum-topic', ['id' => $post->topic_id, 'slug' => $hit['topic_slug']]) . '#p' . $post->id,
                    'icon' => 'fa-solid fa-comments',
                    'source' => 'forum',
                    'source_label' => 'Forum',
                    'date' => $post->created_at,
                    'date_formatted' => date('d/m/Y', strtotime($post->created_at)),
                    'score' => (stripos($hit['topic_title'], $queryText) !== false ? 10 : 0) + (stripos($post->content, $queryText) !== false ? 5 : 0)
                ];
            }
            $data['results'] = array_merge($data['results'], $results);
        } catch (\Exception $e) {
            // Ignorer
        }
        return $data;
    }

    public static function notificationsCollect($data): array
    {
        if (!isset($data['userId'])) {
            return $data;
        }

        try {
            $notificationService = new \Forum\Services\NotificationService();
            $forumUnread = $notificationService->getUnreadCount($data['userId']);
            
            $data['breakdown']['forum'] = $forumUnread;
            $data['totalUnread'] += $forumUnread;
        } catch (\Exception $e) {
            // Silencieux
        }

        return $data;
    }

    /**
     * Hook: user:notification-preferences
     */
    public static function userNotificationPreferences($user): string
    {
        $notifForum = $user->attributes['notif_forum'] ?? true;
        
        return '
        <label class="setting-checkbox">
            <input type="checkbox" data-setting="notif_forum" ' . ($notifForum ? 'checked' : '') . '>
            <span class="checkbox-label">Activité du Forum</span>
        </label>';
    }

    /**
     * Hook: user:profile-allowed-settings
     */
    public static function userProfileAllowedSettings(array $allowed): array
    {
        $allowed[] = 'notif_forum';
        return $allowed;
    }

    /**
     * Listener: forum:reply-created
     * Pont entre le domaine (Forum) et l'action (Notifications)
     */
    public static function onReplyCreated(array $data): void
    {
        $topic = $data['topic'] ?? null;
        $post = $data['post'] ?? null;
        $actor = $data['actor'] ?? null;

        if (!$topic || !$post || !$actor) {
            return;
        }

        $targetUserId = $topic->getAuthorId();
        if (!$targetUserId) return;

        // Préparer le payload pour notifications:send
        $notifData = [
            'user_id' => $targetUserId,
            'type' => 'forum.reply',
            'source_plugin' => 'forum',
            'data' => [
                'key' => 'forum.reply',
                'params' => [
                    'actor' => $actor['name'],
                    'topic' => (string)$topic->title
                ],
                'url' => rtrim(\Core\Core::getInstance()->getConfigVal('siteUrl'), '/') . $topic->getUrl() . '#p' . $post->getId(),
                'actor' => $actor,
                'entity' => [
                    'type' => 'topic',
                    'id' => $topic->getId()
                ]
            ],
            // external_id pour déduplication (topic + post)
            'external_id' => 'forum:reply:' . $topic->getId() . ':' . $post->getId()
        ];

        // Action Hook (Communication inter-plugin via events)
        \Core\Core::getInstance()->callHook('notifications:send', $notifData);
    }

    /**
     * Hook: forum:get-stats
     */
    public static function getStats(array $data): array
    {
        try {
            $categories = \Forum\Entities\Category::query()->all();
            $totalTopics = \Forum\Entities\Topic::query()->count();
            $totalPosts = \Forum\Entities\Post::query()->count();
            
            $data['stats'] = [
                'topics' => $totalTopics,
                'posts' => $totalPosts,
                'categories' => count($categories),
                'new_topics_7d' => 0,
                'new_posts_7d' => 0,
                'topic_growth' => []
            ];

            // Activity last 7 days
            $sevenDaysAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
            $data['stats']['new_topics_7d'] = \Forum\Entities\Topic::query()->where('created_at', '>', $sevenDaysAgo)->count();
            $data['stats']['new_posts_7d'] = \Forum\Entities\Post::query()->where('created_at', '>', $sevenDaysAgo)->count();

            // Monthly growth
            $topicGrowth = [];
            for ($i = 11; $i >= 0; $i--) {
                $key = date('Y-m', strtotime("-$i months"));
                $topicGrowth[$key] = 0;
            }

            // Since JsonActiveRecord doesn't support grouping by monthly format in generic query yet,
            // we can retrieve all and aggregate OR use a specific SQL if using SQL driver.
            // For compliance, let's aggregate manually or skip if too heavy. 
            // Better: use Topic::query() and sort by month.
            $oneYearAgo = date('Y-m-d H:i:s', strtotime('-12 months'));
            $topics = \Forum\Entities\Topic::query()->where('created_at', '>', $oneYearAgo)->get();
            foreach ($topics as $topic) {
                $month = date('Y-m', strtotime($topic->created_at));
                if (isset($topicGrowth[$month])) {
                    $topicGrowth[$month]++;
                }
            }
            $data['stats']['topic_growth'] = $topicGrowth;

        } catch (\Exception $e) {
            // Ignorer
        }
        return $data;
    }

    /**
     * Hook: core:stats-collector
     */
    public static function collectStats(array $stats): array
    {
        $forumStats = self::getStats([]);
        if (!empty($forumStats['stats'])) {
            $stats['forum'] = $forumStats['stats'];
            // Flat counts for backward compatibility/simplicity in landing page
            if (isset($forumStats['stats']['topics'])) {
                $stats['counts']['forum_topics'] = $forumStats['stats']['topics'];
            }
            if (isset($forumStats['stats']['posts'])) {
                $stats['counts']['forum_posts'] = $forumStats['stats']['posts'];
            }
        }
        return $stats;
    }
}

}

namespace {

    function forumInstall(): void
    {
        \Forum\Hooks::install();
    }

    function forumBeforeRunPlugin(): void
    {
        // Hooks are now registered in hooks.json

        // Charger les entités
        foreach (glob(PLUGINS . 'forum/entities/' . '*.php') as $file) {
            require_once $file;
        }
        
        // Charger les services
        if (is_dir(PLUGINS . 'forum/services')) {
            foreach (glob(PLUGINS . 'forum/services/' . '*.php') as $file) {
                require_once $file;
            }
        }
    }

    function forumEndFrontHead($param = '')
    {
        $logger = \Core\Logger::getInstance();
        $logger->log(\Core\Logger::LEVEL_DEBUG, "forumEndFrontHead - Fonction globale appelée, param: " . (is_string($param) ? strlen($param) . " chars" : gettype($param)));
        $result = \Forum\Hooks::endFrontHead($param);
        $logger->log(\Core\Logger::LEVEL_DEBUG, "forumEndFrontHead - Retour de " . strlen($result) . " chars");
        return $result;
    }

    function forumUserProfileStats($stats = [])
    {
        return \Forum\Hooks::userProfileStats($stats);
    }

    function forumUserProfileActivity($user)
    {
        return \Forum\Hooks::userProfileActivity($user);
    }

    function forumEndFrontBody(): void
    {
        // Désactivé : les notifications sont maintenant unifiées dans le menu avatar
        // via Show::userAvatarMenu() et getNotificationsData()
        return;
    }


    /**
     * Hook: user:avatar-menu
     */
    function forumUserAvatarMenu(array $data): array
    {
        $items = $data['items'] ?? [];
        $user = \Core\Auth\UsersManager::getCurrentUser();
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('forum');
        
        if ($plugin && $plugin->isInstalled() && $plugin->getConfigVal('activate') && $user) {
            $router = \Core\Router\Router::getInstance();
            try {
                $url = $router->generate('forum-index');
            } catch (\Exception $e) {
                $url = '/forum';
            }

            $items[] = [
                'label' => 'Forum',
                'url' => $url,
                'icon' => 'fa-solid fa-comments',
                'order' => 40
            ];
        }
        $data['items'] = $items;
        return $data;
    }


}


