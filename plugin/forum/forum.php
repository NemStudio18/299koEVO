<?php

namespace Forum {

use Core\Core;
use Core\Plugin\PluginsManager;
use Utils\Util;

defined('ROOT') or exit('No direct script access allowed');

spl_autoload_register(function (string $class) {
    if (strpos($class, 'Forum\\') === 0) {
        $rel = substr($class, strlen('Forum\\'));
        $parts = explode('\\', $rel);
        if (count($parts) >= 2) {
            $folder = strtolower(array_shift($parts));
            $file = __DIR__ . DS . $folder . DS . implode(DS, $parts) . '.php';
            if (is_file($file)) {
                require_once $file;
            }
        }
    }
});

class Hooks
{
    public static function install(): void
    {
        $dbDir = DATA_PLUGIN . 'forum' . DS;
        if (!is_dir($dbDir)) {
            @mkdir($dbDir, 0755, true);
        }
        $files = ['categories.json', 'topics.json', 'posts.json', 'post_likes.json', 'notifications.json'];
        foreach ($files as $file) {
            if (!is_file($dbDir . $file)) {
                Util::writeJsonFile($dbDir . $file, []);
            }
        }
    }

    public static function endFrontHead($param = ''): string
    {
        $plugin = PluginsManager::getInstance()->getPlugin('forum');
        if (!$plugin || !$plugin->getConfigVal('activate')) {
            return (string) $param;
        }

        $cssFile = __DIR__ . '/template/public.css';
        $v = is_file($cssFile) ? (string) filemtime($cssFile) : VERSION;
        $href = Util::urlBuild(PLUGINS . 'forum/template/public.css') . '?v=' . $v;

        return (string) $param . '<link rel="stylesheet" href="' . $href . '" type="text/css"/>' . "\n";
    }

    public static function endAdminHead($param = ''): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/admin/forum') === false) {
            return (string) $param;
        }

        $cssFile = __DIR__ . '/template/admin.css';
        $v = is_file($cssFile) ? (string) filemtime($cssFile) : VERSION;
        $href = Util::urlBuild(PLUGINS . 'forum/template/admin.css') . '?v=' . $v;

        return (string) $param . '<link rel="stylesheet" href="' . $href . '" type="text/css"/>' . "\n";
    }

    public static function userProfileStats($stats = []): array
    {
        $user = \Core\Auth\UsersManager::getCurrentUser();
        if (!$user) {
            return $stats;
        }

        try {
            $userId = (int) ($user->attributes['id'] ?? 0);
            if ($userId <= 0) {
                return $stats;
            }

            $postCount = \Forum\Entities\Post::query()->where('author_id', '=', $userId)->count();
            $topicCount = \Forum\Entities\Topic::query()->where('author_id', '=', $userId)->count();

            if ($topicCount > 0) {
                $stats[] = [
                    'label' => 'Sujets sur le Forum',
                    'value' => $topicCount,
                    'color' => 'var(--evo-accent)',
                    'icon' => 'fa-solid fa-comments'
                ];
            }

            if ($postCount > 0) {
                $stats[] = [
                    'label' => 'Messages sur le Forum',
                    'value' => $postCount,
                    'color' => 'var(--evo-info)',
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
            $userId = (int) ($user->attributes['id'] ?? 0);
            $posts = \Forum\Entities\Post::query()
                ->where('author_id', '=', $userId)
                ->orderBy('created_at', 'DESC')
                ->limit(5)
                ->get();

            if (!empty($posts)) {
                $items = [];
                foreach ($posts as $post) {
                    $topic = \Forum\Entities\Topic::findPK($post->topic_id);
                    $items[] = [
                        'text' => 'A posté dans : ' . ($topic ? (string) $topic->title : 'Sujet inconnu'),
                        'subtext' => substr(strip_tags((string) $post->content), 0, 100) . '...',
                        'date' => date('d/m/Y', strtotime((string) $post->created_at)),
                        'url' => \Core\Router\Router::getInstance()->generate('forum-topic', ['id' => $post->topic_id]) . '#p' . $post->id,
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
        $queryText = (string) ($data['query'] ?? '');
        if ($queryText === '') {
            return $data;
        }
        $results = [];
        try {
            $postManager = new \Forum\Entities\PostManager();
            $searchHits = $postManager->search($queryText, 20);

            foreach ($searchHits as $hit) {
                $post = $hit['post'];
                $results[] = [
                    'title' => $hit['topic_title'],
                    'excerpt' => substr(strip_tags((string) $post->content), 0, 150) . '...',
                    'url' => \Core\Router\Router::getInstance()->generate('forum-topic', ['id' => $post->topic_id]) . '#p' . $post->id,
                    'icon' => 'fa-solid fa-comments',
                    'source' => 'forum',
                    'source_label' => 'Forum',
                    'date' => $post->created_at,
                    'date_formatted' => date('d/m/Y', strtotime((string) $post->created_at)),
                    'score' => (stripos((string) $hit['topic_title'], $queryText) !== false ? 10 : 0) + (stripos((string) $post->content, $queryText) !== false ? 5 : 0)
                ];
            }
            $data['results'] = array_merge($data['results'] ?? [], $results);
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
            $forumUnread = $notificationService->getUnreadCount((int) $data['userId']);

            $data['breakdown']['forum'] = $forumUnread;
            $data['totalUnread'] = (int) ($data['totalUnread'] ?? 0) + $forumUnread;
        } catch (\Exception $e) {
            // Silencieux
        }

        return $data;
    }

    public static function userNotificationPreferences($user): string
    {
        $notifForum = $user->attributes['notif_forum'] ?? true;

        return '
        <label class="setting-checkbox">
            <input type="checkbox" data-setting="notif_forum" ' . ($notifForum ? 'checked' : '') . '>
            <span class="checkbox-label">Activité du Forum</span>
        </label>';
    }

    public static function userProfileAllowedSettings(array $allowed): array
    {
        $allowed[] = 'notif_forum';
        return $allowed;
    }

    public static function onReplyCreated(array $data): void
    {
        $topic = $data['topic'] ?? null;
        $post = $data['post'] ?? null;
        $actor = $data['actor'] ?? null;

        if (!$topic || !$post || !$actor) {
            return;
        }

        $targetUserId = $topic->getAuthorId();
        if (!$targetUserId) {
            return;
        }

        $notifData = [
            'user_id' => $targetUserId,
            'type' => 'forum.reply',
            'source_plugin' => 'forum',
            'data' => [
                'key' => 'forum.reply',
                'params' => [
                    'actor' => $actor['name'],
                    'topic' => (string) $topic->title
                ],
                'url' => rtrim(Core::getInstance()->getConfigVal('siteUrl'), '/') . $topic->getUrl() . '#p' . $post->getId(),
                'actor' => $actor,
                'entity' => [
                    'type' => 'topic',
                    'id' => $topic->getId()
                ]
            ],
            'external_id' => 'forum:reply:' . $topic->getId() . ':' . $post->getId()
        ];

        Core::getInstance()->callHook('notifications:send', $notifData);
    }

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

            $sevenDaysAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
            $data['stats']['new_topics_7d'] = \Forum\Entities\Topic::query()->where('created_at', '>', $sevenDaysAgo)->count();
            $data['stats']['new_posts_7d'] = \Forum\Entities\Post::query()->where('created_at', '>', $sevenDaysAgo)->count();

            $topicGrowth = [];
            for ($i = 11; $i >= 0; $i--) {
                $key = date('Y-m', strtotime("-$i months"));
                $topicGrowth[$key] = 0;
            }

            $oneYearAgo = date('Y-m-d H:i:s', strtotime('-12 months'));
            $topics = \Forum\Entities\Topic::query()->where('created_at', '>', $oneYearAgo)->get();
            foreach ($topics as $topic) {
                $month = date('Y-m', strtotime((string) $topic->created_at));
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

    public static function collectStats(array $stats): array
    {
        $forumStats = self::getStats([]);
        if (!empty($forumStats['stats'])) {
            $stats['forum'] = $forumStats['stats'];
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
        foreach (glob(PLUGINS . 'forum/entities/*.php') as $file) {
            require_once $file;
        }
        if (is_dir(PLUGINS . 'forum/services')) {
            foreach (glob(PLUGINS . 'forum/services/*.php') as $file) {
                require_once $file;
            }
        }
    }

    function forumEndFrontHead($param = ''): string
    {
        return \Forum\Hooks::endFrontHead($param);
    }

    function forumEndAdminHead($param = ''): string
    {
        return \Forum\Hooks::endAdminHead($param);
    }

    function forumSearchResults(array $data): array
    {
        return \Forum\Hooks::searchResults($data);
    }

    function forumNotificationsCollect($data): array
    {
        return \Forum\Hooks::notificationsCollect($data);
    }

    function forumUserNotificationPreferences($user): string
    {
        return \Forum\Hooks::userNotificationPreferences($user);
    }

    function forumUserProfileAllowedSettings(array $allowed): array
    {
        return \Forum\Hooks::userProfileAllowedSettings($allowed);
    }

    function forumUserProfileStats($stats = []): array
    {
        return \Forum\Hooks::userProfileStats($stats);
    }

    function forumUserProfileActivity($user): array
    {
        return \Forum\Hooks::userProfileActivity($user);
    }

    function forumOnReplyCreated(array $data): void
    {
        \Forum\Hooks::onReplyCreated($data);
    }

    function forumGetStats(array $data): array
    {
        return \Forum\Hooks::getStats($data);
    }

    function forumCollectStats(array $stats): array
    {
        return \Forum\Hooks::collectStats($stats);
    }

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
