<?php

defined('ROOT') or exit('Access denied!');

$router = \Core\Router\Router::getInstance();

// Route admin pour lister les 404
$router->map('GET', '/admin/debug404[/?]', 'Debug404\Controllers\Debug404AdminController#list', 'admin-debug404-list');
