<?php

defined('ROOT') or exit('No direct script access allowed');

$router = \Core\Router\Router::getInstance();

// Ajouter le type de match 'slug' qui n'est pas défini par défaut dans AltoRouter
$router->addMatchTypes(['slug' => '[^/]+']);

// Type de match pour les POIs : exclut les routes système pour éviter les conflits
// Liste des mots réservés (préfixes de routes système et plugins)
$reserved = 'admin|users|api|login|logout|register|profile|lost-password|index|home|contact|about|legal|privacy|terms|sitemap|robots|favicon|meteo-widget|blog|docs|page|plugin|agenda|offres|offers|claim|subscription|messaging|parrainage|forum|poi-favorites|poi-edit|PoiEdit|SearchUi|SearchUi|search|recherche|gamification|data|assets|css|js|img|images|dons|BugTracker|BugTracker|galerie|guestbook|newsletter|sondage|search-evo|recap-tva|contact-evo|poidata|poireviews|qrpoi|searchengine|searchmaps|shorturl|ecommerce|lightstats|meteo|notifications|publicstats|pwa|tinymce';
$router->addMatchTypes(['poi_slug' => '(?i)(?!' . $reserved . '$)[^/]+']);

// Routes publiques pour SearchUi (PSR-4 compatible: nom du dossier)
$router->map('GET', '/comment-revendiquer[/?]', 'SearchUI\\Controllers\\HomeController#commentRevendiquer', 'comment-revendiquer');
$router->map('GET', '/SearchUi[/?]', 'SearchUI\\Controllers\\HomeController#home', 'SearchUi-home');

$router->map('GET', '/SearchUi/result[/?]', 'SearchUI\\Controllers\\ResultController#search', 'SearchUi-result');

// Routes API HTMX (si utilisées par le JS)
$router->map('GET', '/SearchUi/api/subcategory-pois-html', 'SearchUI\\Controllers\\HomeController#getSubcategoryPoisHtml', 'SearchUi-api-subcategory-pois-html');
$router->map('GET', '/SearchUi/api/categories-by-city-html', 'SearchUI\\Controllers\\HomeController#getCategoriesByCityHtml', 'SearchUi-api-categories-by-city-html');
$router->map('GET', '/SearchUi/api/subcategories-html', 'SearchUI\\Controllers\\HomeController#getSubcategoriesHtml', 'SearchUi-api-subcategories-html');
$router->map('GET|POST', '/SearchUi/api/admin-test-mode/toggle', 'SearchUI\\Controllers\\AdminTestModeController#toggle', 'SearchUi-api-admin-test-mode-toggle');
$router->map('POST', '/SearchUi/api/admin-test-mode/impersonate', 'SearchUI\\Controllers\\AdminTestModeController#impersonate', 'SearchUi-api-admin-test-mode-impersonate');
$router->map('POST', '/SearchUi/api/report-poi', 'SearchUI\\Controllers\\DetailController#reportPoi', 'SearchUi-api-report-poi');

// Routes de détail
$router->map('GET', '/SearchUi/[slug:type]/[slug:name]', 'SearchUI\\Controllers\\DetailController#detail', 'SearchUi-detail');

// Routes de recherche "jolies" (Pretty URLs)
// Ordre important : du plus spécifique au plus général
$router->map('GET', '/recherche/[slug:p1]/[slug:p2]/[slug:p3]', 'SearchUI\\Controllers\\HomeController#prettySearch', 'search-pretty-3');
$router->map('GET', '/recherche/[slug:p1]/[slug:p2]', 'SearchUI\\Controllers\\HomeController#prettySearch', 'search-pretty-2');
$router->map('GET', '/recherche/[slug:p1]', 'SearchUI\\Controllers\\HomeController#prettySearch', 'search-pretty-1');
$router->map('GET', '/recherche/ville/[slug:city]', 'SearchUI\\Controllers\\HomeController#prettySearchCityOnly', 'search-pretty-city');
$router->map('GET', '/recherche', 'SearchUI\\Controllers\\HomeController#home', 'search-pretty');

// Route courte à la racine (gère /nom-du-lieu)
// IMPORTANT: Utilise poi_slug pour ne pas capturer les routes système
$router->map('GET', '/[poi_slug:name]', 'SearchUI\\Controllers\\DetailController#detailShort', 'detail-short');

// Admin route
$router->map('GET|POST', '/admin/searchui[/?]', 'SearchUI\\Controllers\\SearchUIAdminController#home', 'admin-SearchUi-home');
