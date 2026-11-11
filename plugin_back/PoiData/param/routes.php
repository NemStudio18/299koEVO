<?php

defined('ROOT') or exit('No direct script access allowed');

$router = \Core\Router\Router::getInstance();

// Admin routes for poi-data
$router->map('GET', '/admin/poidata[/?]', 'PoiData\\Controllers\\PoiDataAdminController#index', 'admin-poidata-home');
$router->map('GET', '/admin/poi-data[/?]', 'PoiData\\Controllers\\PoiDataAdminController#index', 'admin-poi-data-home');
