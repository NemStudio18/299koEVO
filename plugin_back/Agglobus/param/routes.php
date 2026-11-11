<?php

defined('ROOT') or exit('Access denied!');

$router = \Core\Router\Router::getInstance();

// Route publique principale
$router->map('GET', '/agglobus[/?]', 'Agglobus\\Controllers\\AgglobusPublicController#home', 'agglobus-home');
$router->map('GET', '/Agglobus[/?]', 'Agglobus\\Controllers\\AgglobusPublicController#home', 'agglobus-home-cap');

// Route pour les stops d'une ligne
$router->map('GET', '/agglobus/api/route-stops[/?]', 'Agglobus\\Controllers\\AgglobusPublicController#getRouteStops', 'agglobus-api-route-stops');

// Route publique pour le dashboard bus (API proxy)
$router->map('GET', '/agglobus/api/rt[/?]', 'Agglobus\\Controllers\\AgglobusPublicController#apiProxy', 'agglobus-api-rt');
$router->map('GET', '/agglobus/api/proxy[/?]', 'Agglobus\\Controllers\\AgglobusPublicController#apiProxy', 'agglobus-api-proxy');
$router->map('GET', '/Agglobus/api/rt[/?]', 'Agglobus\\Controllers\\AgglobusPublicController#apiProxy', 'agglobus-api-rt-cap');

// Route pour les arrêts proches
$router->map('GET', '/agglobus/api/near[/?]', 'Agglobus\\Controllers\\AgglobusPublicController#getNearStops', 'agglobus-api-near');
$router->map('GET', '/Agglobus/api/near[/?]', 'Agglobus\\Controllers\\AgglobusPublicController#getNearStops', 'agglobus-api-near-cap');

// Route pour la recherche d'arrêts
$router->map('GET', '/agglobus/api/search[/?]', 'Agglobus\\Controllers\\AgglobusPublicController#searchStops', 'agglobus-api-search');
$router->map('GET', '/Agglobus/api/search[/?]', 'Agglobus\\Controllers\\AgglobusPublicController#searchStops', 'agglobus-api-search-cap');

// Route pour les lignes
$router->map('GET', '/agglobus/api/routes[/?]', 'Agglobus\\Controllers\\AgglobusPublicController#getRoutes', 'agglobus-api-routes');

// Route pour les shapes
$router->map('GET', '/agglobus/api/shape[/?]', 'Agglobus\\Controllers\\AgglobusPublicController#getShape', 'agglobus-api-shape');

// Route de test
$router->map('GET', '/agglobus/test[/?]', 'Agglobus\\Controllers\\AgglobusPublicController#test', 'agglobus-test');

// Routes Admin
$router->map('GET|POST', '/admin/agglobus[/?]', 'Core\\Controllers\\CoreController#renderAdminHome', 'agglobus-admin');
$router->map('GET|POST', '/admin/Agglobus[/?]', 'Core\\Controllers\\CoreController#renderAdminHome', 'agglobus-admin-legacy');
