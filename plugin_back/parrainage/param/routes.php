<?php
defined('ROOT') OR exit('Access denied!');

$router = \Core\Router\Router::getInstance();

// ADMIN
$router->map('GET', '/admin/parrainage[/?]', 'Parrainage\\Controllers\\ParrainageAdminController#dashboard', 'admin-parrainage');
$router->map('POST', '/admin/parrainage[/?]', 'Parrainage\\Controllers\\ParrainageAdminController#dashboard', 'admin-parrainage-post');

// PUBLIC
$router->map('GET', '/parrainage[/?]', 'Parrainage\\Controllers\\ParrainagePublicController#dashboard', 'parrainage-dashboard');
