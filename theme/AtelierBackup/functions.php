<?php
/**
 * Atelier Theme Functions
 */

use Template\Template;

// 1. Fetch Latest Poll (Votre Voix)
$latestPoll = null;
try {
    if (file_exists(PLUGINS . 'sondage/entities/SondageManager.php')) {
        require_once PLUGINS . 'sondage/entities/SondageManager.php';
        require_once PLUGINS . 'sondage/entities/Sondage.php';
        $pollManager = new \SondageManager();
        $activePolls = $pollManager->getActiveItems();
        if (!empty($activePolls)) {
            $latestPoll = $activePolls[0];
        }
    }
} catch (\Exception $e) {}
Template::addGlobal('atelier_poll', $latestPoll);

// 2. Fetch Next Events (Prochaines Rencontres) - Limit 3
$nextEvents = [];
try {
    if (file_exists(PLUGINS . 'agenda/entities/EventManager.php')) {
        require_once PLUGINS . 'agenda/entities/EventManager.php';
        require_once PLUGINS . 'agenda/entities/Event.php';
        $eventManager = new \Agenda\Entities\EventManager();
        $futureEvents = $eventManager->getAll(false, true); // False = future only, True = approved only
        if (!empty($futureEvents)) {
            $nextEvents = array_slice($futureEvents, 0, 3);
        }
    }
} catch (\Exception $e) {}
Template::addGlobal('atelier_events', $nextEvents);

// 4. Fetch Latest News (Actualités) - Limit 3
$latestNews = [];
try {
    if (file_exists(PLUGINS . 'blog/entities/NewsManager.php')) {
        require_once PLUGINS . 'blog/entities/NewsManager.php';
        require_once PLUGINS . 'blog/entities/news.php';
        require_once PLUGINS . 'blog/entities/BlogCategoriesManager.php';
        require_once PLUGINS . 'blog/entities/BlogCategory.php';
        $newsManager = new \newsManager();
        $allNews = $newsManager->getItems();
        // Filter out drafts and limit to 3
        $publishedNews = array_filter($allNews, function($n) {
            return $n->getDraft() === "0";
        });
        // Sort by date desc just in case
        usort($publishedNews, function($a, $b) {
            return strtotime($b->getDate()) - strtotime($a->getDate());
        });
        $latestNews = array_slice($publishedNews, 0, 3);
    }
} catch (\Exception $e) {}
Template::addGlobal('atelier_news', $latestNews);

// 3. Fetch User Count (Le Baromètre)
$userCount = 0;
try {
    $userCount = count(\Core\Auth\User::all());
} catch (\Exception $e) {}
Template::addGlobal('atelier_users_count', $userCount);
Template::addGlobal('Util', \Utils\Util::class);
