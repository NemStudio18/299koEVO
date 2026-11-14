<?php
$router = \Core\Router\Router::getInstance();

$router->map('GET',  '/admin/libmanager',
    'LibManager\\Controllers\\LibManagerController#index',
    'libmanager-dashboard');

$router->map('POST', '/admin/libmanager/check',
    'LibManager\\Controllers\\LibManagerController#check',
    'libmanager-check');

$router->map('POST', '/admin/libmanager/update',
    'LibManager\\Controllers\\LibManagerController#update',
    'libmanager-update');

$router->map('GET',  '/admin/libmanager/log',
    'LibManager\\Controllers\\LibManagerController#log',
    'libmanager-log');
