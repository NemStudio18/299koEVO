<?php
## Code PHP pour la landing page personnalisée
## Ce fichier est chargé avant le template landing.tpl

defined('ROOT') or exit('Access denied!');

// Récupérer les instances nécessaires
$core = \Core\Core::getInstance();
$router = \Core\Router\Router::getInstance();
$pluginsManager = \Core\Plugin\PluginsManager::getInstance();
$searchPlugin = $pluginsManager->getPlugin('SearchUi');

// Créer la connexion PDO via le service centralisé Core\Database
try {
    // S'assurer que la classe est chargée (si pas d'autoloader optimisé)
    if (!class_exists('\Core\Database')) {
        require_once COMMON . 'Core/Database.php';
    }
    $pdo = \Core\Database::getInstance()->getPdo();
} catch (\Exception $e) {
    // En cas d'erreur de connexion, on désactive les stats avancées
    $pdo = null;
    $core->getLogger()->log(\Core\Logger::LEVEL_ERROR, "Lumiere: Erreur connexion DB centrale: " . $e->getMessage());
}

// =========================================
// PRÉPARATION DES DONNÉES DE BASE (LANDING)
// =========================================

// Préparer les données pour le template
$landingData = [
    'hero_title' => 'Tous les lieux utiles de Bourges, construits par ses habitants',
    'hero_subtitle' => 'Un annuaire local collaboratif pour découvrir, corriger et soutenir les commerces, assos et services du territoire.',
    'cta_primary_text' => 'Je veux aider',
    'cta_primary_url' => $router->generate('register'),
    'cta_secondary_text' => 'Je suis commerçant / asso – réclamer ma fiche',
    'cta_secondary_url' => $router->generate('register'),
    'search_placeholder' => 'Rechercher un commerce, un lieu, une activité, un service…',
    'collaborative_text' => 'Ces lieux proviennent en grande partie d\'OpenStreetMap. Grâce aux habitants et aux professionnels, ils sont enrichis et mis à jour.',
    'contribute_text' => 'Pour corriger ou enrichir une fiche : Créer un compte',
    'contribute_url' => $router->generate('register'),
];

// Récupérer la configuration du plugin search depuis search_conf.json
if ($searchPlugin && $searchPlugin->isInstalled()) {
    // Lire directement depuis search_conf.json comme le fait SearchAdminController
    $configFile = DATA_PLUGIN . 'search' . DS . 'search_conf.json';
    $cfg = [];
    if (file_exists($configFile)) {
        $cfg = json_decode(file_get_contents($configFile), true) ?: [];
    }

    // Utiliser les valeurs de config si disponibles
    if (!empty($cfg['hero_title']))
        $landingData['hero_title'] = $cfg['hero_title'];
    if (!empty($cfg['hero_subtitle']))
        $landingData['hero_subtitle'] = $cfg['hero_subtitle'];

    // Récupérer les URLs depuis la config
    if (!empty($cfg['landing_cta_primary_text']))
        $landingData['cta_primary_text'] = $cfg['landing_cta_primary_text'];
    if (!empty($cfg['landing_cta_primary_url']))
        $landingData['cta_primary_url'] = $cfg['landing_cta_primary_url'];
    if (!empty($cfg['landing_cta_secondary_text']))
        $landingData['cta_secondary_text'] = $cfg['landing_cta_secondary_text'];
    if (!empty($cfg['landing_cta_secondary_url']))
        $landingData['cta_secondary_url'] = $cfg['landing_cta_secondary_url'];
    if (!empty($cfg['landing_contribute_text']))
        $landingData['contribute_text'] = $cfg['landing_contribute_text'];
    if (!empty($cfg['landing_contribute_url']))
        $landingData['contribute_url'] = $cfg['landing_contribute_url'];

    // URLs pour les cards "Comment ça marche ?"
    $landingData['card_osm_url'] = !empty($cfg['landing_card_osm_url']) ? $cfg['landing_card_osm_url'] : '/page/openstreetmap';
    $landingData['card_contribute_url'] = !empty($cfg['landing_card_contribute_url']) ? $cfg['landing_card_contribute_url'] : '/page/contribuer';

    // URL pour la roadmap
    $landingData['roadmap_url'] = !empty($cfg['landing_roadmap_url']) ? $cfg['landing_roadmap_url'] : '/page/roadmap';

    // URL pour le CTA final
    $landingData['final_cta_url'] = !empty($cfg['landing_final_cta_url']) ? $cfg['landing_final_cta_url'] : $router->generate('register');
}

// Variables globales de base
use Template\Template;
Template::addGlobal('landing', $landingData);
// Bridge theme auth globals to policy engine
$evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
$ctx = new \Core\Navigation\ConditionContext(\Core\Auth\UsersManager::getCurrentUser());
Template::addGlobal('IS_LOGGED', $evaluator->canAccess(new \Core\Navigation\GenericResource('system:access'), $ctx));
Template::addGlobal('isLoggedIn', $evaluator->canAccess(new \Core\Navigation\GenericResource('system:access'), $ctx));
Template::addGlobal('registerUrl', $router->generate('register'));


// =========================================
// RÉCUPÉRATION DES STATISTIQUES VIA HOOK CENTRALISÉ
// =========================================

// Initialisation via le hook core:stats-collector
$allStats = $core->callHook('core:stats-collector', [
    'counts' => [],
    'blog' => ['total_articles' => 0, 'recent_articles' => []],
    'forum' => ['topics' => 0, 'posts' => 0, 'new_topics_7d' => 0],
    'claim' => ['claimed_pois' => 0],
    'poi' => ['total_pois' => 0, 'total_cities' => 0, 'recent_updates' => 0],
    'traffic' => ['total_visitors' => 25, 'total_pages' => 100]
]);
$homeStats = $allStats;

$userCount = $allStats['counts']['users'] ?? 0;
$recentUpdates = $allStats['poi']['recent_updates'] ?? 0;
$trafficStats = $allStats['traffic'] ?? ['total_visitors' => 25, 'total_pages' => 100];
$forumStats = $allStats['forum'] ?? ['topics' => 0, 'posts' => 0, 'new_topics_7d' => 0];
// Adapt for template expectations (forumStats.total_topics, etc)
if (isset($forumStats['topics'])) $forumStats['total_topics'] = $forumStats['topics'];
if (isset($forumStats['posts'])) $forumStats['total_posts'] = $forumStats['posts'];

$favoritesStats = $allStats['favorites'] ?? ['total_favorites' => 0];
$gamificationStats = $allStats['gamification'] ?? ['total_contributions' => 0, 'active_contributors' => 0, 'has_contributions' => false];
$blogStats = $allStats['blog'] ?? ['total_articles' => 0, 'recent_articles' => []];
$randomPois = $allStats['poi']['random_pois'] ?? [];
$topContributors = $allStats['auth']['top_contributors'] ?? [];
$claimStats = $allStats['claim'] ?? ['claimed_pois' => 0];
$todayEvents = $allStats['agenda']['today_events'] ?? [];
$activeOffers = $allStats['offers']['active_offers'] ?? [];
$cities = $allStats['poi']['cities_list'] ?? []; // We might need a more specific hook for this if needed

// =========================================
// INJECTION DES VARIABLES GLOBALES
// =========================================

// Statistiques de base
Template::addGlobal('homeStats', $homeStats);
Template::addGlobal('cities', $cities);

// Nouvelles statistiques enrichies
Template::addGlobal('userCount', $userCount);
Template::addGlobal('recentUpdates', $recentUpdates);
Template::addGlobal('topContributors', $topContributors);
Template::addGlobal('claimStats', $claimStats);
Template::addGlobal('todayEvents', $todayEvents);
Template::addGlobal('activeOffers', $activeOffers);

// Nouvelles statistiques Phase 2
Template::addGlobal('trafficStats', $trafficStats);
Template::addGlobal('forumStats', $forumStats);
Template::addGlobal('favoritesStats', $favoritesStats);
Template::addGlobal('gamificationStats', $gamificationStats);
Template::addGlobal('blogStats', $blogStats);
Template::addGlobal('randomPois', $randomPois);

// Variables booléennes préparées pour les conditions du template
Template::addGlobal('hasTopContributors', !empty($topContributors));
Template::addGlobal('hasTodayEvents', !empty($todayEvents));
Template::addGlobal('hasActiveOffers', !empty($activeOffers));
Template::addGlobal('hasRecentArticles', !empty($blogStats['recent_articles']));
Template::addGlobal('hasRandomPois', !empty($randomPois));