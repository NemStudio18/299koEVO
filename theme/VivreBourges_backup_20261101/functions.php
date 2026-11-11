<?php
/**
 * Theme Vivre Bourges - functions.php
 * Initialisation globale du thème, configuration des assets et variables de template
 */

defined('ROOT') or exit('Access denied!');

use Template\Template;
use Core\Core;
use Core\Router\Router;
use Core\Plugin\PluginsManager;
use Utils\Util;

$core = Core::getInstance();
$router = Router::getInstance();
$pluginsManager = PluginsManager::getInstance();

// Connexion base de données si nécessaire
try {
    if (!class_exists('\Core\Database')) {
        require_once COMMON . 'Core/Database.php';
    }
    $pdo = \Core\Database::getInstance()->getPdo();
} catch (\Exception $e) {
    $pdo = null;
    $core->getLogger()->log(\Core\Logger::LEVEL_ERROR, "VivreBourges: Erreur connexion DB: " . $e->getMessage());
}

// Données de base landing
$landingData = [
    'hero_title' => 'Vivre-Bourges.fr',
    'hero_subtitle' => 'Bienvenue à Bourges : L\'Art de Vivre au Cœur du Cher !',
    'hero_cta_text' => 'Explorez la Ville',
    'hero_cta_url' => '#decouvrir',
    'search_placeholder' => 'Rechercher un commerce, un lieu, une activité, un service…',
    'collaborative_text' => 'Ces lieux proviennent en grande partie d\'OpenStreetMap et sont enrichis par les habitants et les professionnels.',
];

// Configuration SearchUi si disponible
$searchPlugin = $pluginsManager->getPlugin('SearchUi') ?: $pluginsManager->getPlugin('search');
if ($searchPlugin && $searchPlugin->isInstalled()) {
    $configFile = DATA_PLUGIN . 'search' . DS . 'search_conf.json';
    if (file_exists($configFile)) {
        $cfg = json_decode(file_get_contents($configFile), true) ?: [];
        if (!empty($cfg['hero_title'])) $landingData['hero_title'] = $cfg['hero_title'];
        if (!empty($cfg['hero_subtitle'])) $landingData['hero_subtitle'] = $cfg['hero_subtitle'];
    }
}

Template::addGlobal('landing', $landingData);

// Authentification & droits
$currentUser = \Core\Auth\UsersManager::getCurrentUser();
$isLogged = ($currentUser !== null && $currentUser->getRole() !== 'guest');
Template::addGlobal('IS_LOGGED', $isLogged);
Template::addGlobal('isLoggedIn', $isLogged);
Template::addGlobal('isLogged', $isLogged);
Template::addGlobal('registerUrl', $router->generate('register'));

// Statistiques globales via le hook centralisé
$allStats = $core->callHook('core:stats-collector', [
    'counts' => [],
    'blog' => ['total_articles' => 0, 'recent_articles' => []],
    'forum' => ['topics' => 0, 'posts' => 0, 'new_topics_7d' => 0],
    'claim' => ['claimed_pois' => 0],
    'poi' => ['total_pois' => 0, 'total_cities' => 0, 'recent_updates' => 0],
    'traffic' => ['total_visitors' => 25, 'total_pages' => 100],
    'agenda' => ['today_events' => [], 'upcoming_events' => []],
    'offers' => ['active_offers' => []]
]);

$homeStats = $allStats ?: [];
$todayEvents = $allStats['agenda']['today_events'] ?? [];
$activeOffers = $allStats['offers']['active_offers'] ?? [];
$blogStats = $allStats['blog'] ?? ['total_articles' => 0, 'recent_articles' => []];
$randomPois = $allStats['poi']['random_pois'] ?? [];
$cities = $allStats['poi']['cities_list'] ?? [];

Template::addGlobal('homeStats', $homeStats);
Template::addGlobal('todayEvents', $todayEvents);
Template::addGlobal('hasTodayEvents', !empty($todayEvents));
Template::addGlobal('activeOffers', $activeOffers);
Template::addGlobal('hasActiveOffers', !empty($activeOffers));
Template::addGlobal('blogStats', $blogStats);
Template::addGlobal('hasRecentArticles', !empty($blogStats['recent_articles']));
Template::addGlobal('randomPois', $randomPois);
Template::addGlobal('hasRandomPois', !empty($randomPois));
Template::addGlobal('cities', $cities);
