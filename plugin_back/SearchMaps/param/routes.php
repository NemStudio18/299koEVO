<?php

defined('ROOT') or exit('No direct script access allowed');

$router = \Core\Router\Router::getInstance();

// Admin routes for search-maps
$router->map('GET', '/admin/searchmaps[/?]', 'SearchMaps\\Controllers\\SearchMapsAdminController#home', 'admin-SearchMaps-home');
$router->map('GET', '/searchmaps/static[/?]', 'SearchMaps\\Controllers\\AdminController#staticMap', 'searchmaps-static');
