<?php

defined('ROOT') or exit('No direct script access allowed');

$router = \Core\Router\Router::getInstance();

// Admin routes for search-maps
$router->map('GET', '/admin/searchmaps[/?]', 'SearchMaps\\Controllers\\SearchMapsAdminController#home', 'admin-SearchMaps-home');
$router->map('POST', '/admin/searchmaps/save[/?]', 'SearchMaps\\Controllers\\SearchMapsAdminController#save', 'admin-SearchMaps-save');
$router->map('GET', '/searchmaps/static[/?]', 'SearchMaps\\Controllers\\AdminController#staticMap', 'searchmaps-static');
