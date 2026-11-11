<?php

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('No direct script access allowed');

$router = \Core\Router\Router::getInstance();

// Route principale - accepter avec ou sans slash (les paramètres GET sont gérés automatiquement par AltoRouter)
// Définir deux routes pour garantir le matching avec ou sans slash
$router->map('GET|POST', '/admin/lightstats', 'LightStats\Controllers\LightStatsAdminController#home', 'admin-lightstats-home');
$router->map('GET|POST', '/admin/lightstats/', 'LightStats\Controllers\LightStatsAdminController#home', 'admin-lightstats-home-slash');

// Endpoint API pour les micro-événements (Beacon JS)
$router->map('POST', '/lightstats/api/event', 'LightStats\Controllers\LightStatsApiController#logEvent', 'lightstats-api-event');
$router->map('POST', '/api/analytics/event', 'LightStats\Controllers\LightStatsApiController#logEvent', 'api-analytics-event');
