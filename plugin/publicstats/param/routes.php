<?php

defined('ROOT') OR exit('Access denied!');

$router = \Core\Router\Router::getInstance();

// Admin routes
$router->map('GET', '/admin/publicstats', 'PublicStats\Controllers\PublicStatsAdminController#index', 'publicstats-admin');
$router->map('POST', '/admin/publicstats/save', 'PublicStats\Controllers\PublicStatsAdminController#save', 'publicstats-admin-save');

// Public routes
$router->map('GET', '/statistiques', 'PublicStats\Controllers\PublicStatsController#index', 'publicstats-index');
$router->map('GET', '/statistiques/api', 'PublicStats\Controllers\PublicStatsController#api', 'publicstats-api');
