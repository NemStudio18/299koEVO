<?php

defined('ROOT') or exit('No direct script access allowed');

$router = \Core\Router\Router::getInstance();

// Admin routes for search-engine
$router->map('GET', '/admin/searchengine[/?]', 'SearchEngine\\Controllers\\SearchEngineAdminController#index', 'admin-SearchEngine-index');
$router->map('POST', '/admin/searchengine/save[/?]', 'SearchEngine\\Controllers\\SearchEngineAdminController#save', 'admin-SearchEngine-save');
$router->map('POST', '/admin/searchengine/clear-cache[/?]', 'SearchEngine\\Controllers\\SearchEngineAdminController#clearCache', 'admin-SearchEngine-clear-cache');
$router->map('GET', '/admin/search-engine[/?]', 'SearchEngine\\Controllers\\SearchEngineAdminController#index', 'admin-search-engine-home');
