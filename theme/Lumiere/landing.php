<?php
## Code PHP pour la landing page personnalisée
## Ce fichier est chargé avant le template landing.tpl

defined('ROOT') or exit('Access denied!');

// Récupérer les instances nécessaires
$core = \Core\Core::getInstance();
$router = \Core\Router\Router::getInstance();
$pluginsManager = \Core\Plugin\PluginsManager::getInstance();
$searchPlugin = $pluginsManager->getPlugin('search');

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
    if (!empty($cfg['hero_title'])) {
        $landingData['hero_title'] = $cfg['hero_title'];
    }
    if (!empty($cfg['hero_subtitle'])) {
        $landingData['hero_subtitle'] = $cfg['hero_subtitle'];
    }
    
    // Récupérer les URLs depuis la config (avec valeurs par défaut si vides)
    if (!empty($cfg['landing_cta_primary_text'])) {
        $landingData['cta_primary_text'] = $cfg['landing_cta_primary_text'];
    }
    if (!empty($cfg['landing_cta_primary_url'])) {
        $landingData['cta_primary_url'] = $cfg['landing_cta_primary_url'];
    }
    if (!empty($cfg['landing_cta_secondary_text'])) {
        $landingData['cta_secondary_text'] = $cfg['landing_cta_secondary_text'];
    }
    if (!empty($cfg['landing_cta_secondary_url'])) {
        $landingData['cta_secondary_url'] = $cfg['landing_cta_secondary_url'];
    }
    if (!empty($cfg['landing_contribute_text'])) {
        $landingData['contribute_text'] = $cfg['landing_contribute_text'];
    }
    if (!empty($cfg['landing_contribute_url'])) {
        $landingData['contribute_url'] = $cfg['landing_contribute_url'];
    }
    
    // URLs pour les cards "Comment ça marche ?"
    if (!empty($cfg['landing_card_osm_url'])) {
        $landingData['card_osm_url'] = $cfg['landing_card_osm_url'];
    } else {
        $landingData['card_osm_url'] = '/page/openstreetmap';
    }
    if (!empty($cfg['landing_card_contribute_url'])) {
        $landingData['card_contribute_url'] = $cfg['landing_card_contribute_url'];
    } else {
        $landingData['card_contribute_url'] = '/page/contribuer';
    }
    
    // URL pour la roadmap
    if (!empty($cfg['landing_roadmap_url'])) {
        $landingData['roadmap_url'] = $cfg['landing_roadmap_url'];
    } else {
        $landingData['roadmap_url'] = '/page/roadmap';
    }
    
    // URL pour le CTA final
    if (!empty($cfg['landing_final_cta_url'])) {
        $landingData['final_cta_url'] = $cfg['landing_final_cta_url'];
    } else {
        $landingData['final_cta_url'] = $router->generate('register');
    }
}

// Passer les données au template
$tpl->set('landing', $landingData);
// Utiliser IS_LOGGED comme dans le layout.tpl pour la cohérence
$tpl->set('IS_LOGGED', $core->auth()->isLogged());
$tpl->set('isLoggedIn', $core->auth()->isLogged()); // Alias pour compatibilité
$tpl->set('registerUrl', $router->generate('register'));

// Récupérer les stats de l'agglomération si le plugin search est disponible
if ($searchPlugin && $searchPlugin->isInstalled()) {
    try {
        // Charger la configuration du plugin search
        $configFile = DATA_PLUGIN . 'search' . DS . 'search_conf.json';
        $cfg = [];
        if (file_exists($configFile)) {
            $cfg = json_decode(file_get_contents($configFile), true) ?: [];
        }
        
        // Créer le service avec la configuration
        $searchService = new \Search\Services\SimpleSearchService($cfg);
        $homeStats = $searchService->getHomeStats();
        $tpl->set('homeStats', $homeStats);
        
        // Récupérer la liste des villes de l'agglomération
        $communeService = new \Search\Services\CommuneService();
        $communes = $communeService->loadAggloCommunes();
        
        // Formater les villes pour le template
        $cities = [];
        foreach ($communes as $commune) {
            if (is_string($commune)) {
                $cities[] = ['name' => $commune];
            } elseif (is_array($commune) && isset($commune['name'])) {
                $cities[] = $commune;
            }
        }
        $tpl->set('cities', $cities);
    } catch (\Exception $e) {
        // En cas d'erreur, continuer sans les stats
        $tpl->set('homeStats', []);
        $tpl->set('cities', []);
    }
} else {
    $tpl->set('homeStats', []);
    $tpl->set('cities', []);
}

