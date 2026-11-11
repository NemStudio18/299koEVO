<?php

defined('ROOT') or exit('Access denied!');

$router = \Core\Router\Router::getInstance();

$router->map('GET',  '/admin/menus[/?]',            'Core\Navigation\Controllers\MenuAdminController#index',      'menu-admin-home');
$router->map('POST', '/admin/menus/add[/?]',        'Core\Navigation\Controllers\MenuAdminController#addItem',    'menu-admin-add');
$router->map('POST', '/admin/menus/update[/?]',     'Core\Navigation\Controllers\MenuAdminController#updateItem', 'menu-admin-update');
$router->map('POST', '/admin/menus/delete[/?]',     'Core\Navigation\Controllers\MenuAdminController#deleteItem', 'menu-admin-delete');
$router->map('POST', '/admin/menus/save-order[/?]', 'Core\Navigation\Controllers\MenuAdminController#saveOrder',  'menu-admin-save-order');
