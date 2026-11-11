<?php

defined('ROOT') OR exit('Access denied!');

$router = \Core\Router\Router::getInstance();

// Admin routes
$router->map('GET', '/admin/meteo[/?]', 'Meteo\\Controllers\\MeteoAdminController#home', 'admin-meteo-home');
$router->map('POST', '/admin/meteo/save', 'Meteo\\Controllers\\MeteoAdminController#save', 'admin-meteo-save');

// Public routes - Changed from /meteo to /meteo-widget to avoid conflict with POI
$router->map('GET', '/meteo-widget[/?]', 'Meteo\\Controllers\\MeteoController#index', 'meteo-index');
$router->map('GET', '/meteo-widget/api/current', 'Meteo\\Controllers\\MeteoController#apiCurrent', 'meteo-api-current');
$router->map('GET', '/meteo-widget/api/forecast', 'Meteo\\Controllers\\MeteoController#apiForecast', 'meteo-api-forecast');

