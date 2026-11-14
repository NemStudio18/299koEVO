<?php

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

$router = \Core\Router\Router::getInstance();

// Routes publiques (alias /home et /homebuilder)
$router->map('GET', '/home[/?]', 'HomeBuilder\\Controllers\\HomeBuilderController#home', 'homebuilder-home');
$router->map('GET', '/homebuilder[/?]', 'HomeBuilder\\Controllers\\HomeBuilderController#home', 'homebuilder-homebuilder');

// Routes d'administration
$router->map('GET', '/admin/homebuilder[/?]', 'HomeBuilder\\Controllers\\HomeBuilderAdminController#index', 'admin-homebuilder');
$router->map('GET', '/admin/homebuilder/add[/?]', 'HomeBuilder\\Controllers\\HomeBuilderAdminController#add', 'admin-homebuilder-add');
$router->map('GET', '/admin/homebuilder/edit/[*:id]', 'HomeBuilder\\Controllers\\HomeBuilderAdminController#edit', 'admin-homebuilder-edit');
$router->map('POST', '/admin/homebuilder/delete/[*:id]', 'HomeBuilder\\Controllers\\HomeBuilderAdminController#delete', 'admin-homebuilder-delete');
$router->map('POST', '/admin/homebuilder/reorder', 'HomeBuilder\\Controllers\\HomeBuilderAdminController#reorder', 'admin-homebuilder-reorder');
$router->map('POST', '/admin/homebuilder/add/send[/?]', 'HomeBuilder\\Controllers\\HomeBuilderAdminController#addSend', 'admin-homebuilder-add-send');
$router->map('POST', '/admin/homebuilder/edit/send/[*:id]', 'HomeBuilder\\Controllers\\HomeBuilderAdminController#editSend', 'admin-homebuilder-edit-send');
$router->map('GET', '/admin/homebuilder/styles/[*:id]', 'HomeBuilder\\Controllers\\HomeBuilderAdminController#styles', 'admin-homebuilder-styles');
$router->map('POST', '/admin/homebuilder/styles/send/[*:id]', 'HomeBuilder\\Controllers\\HomeBuilderAdminController#stylesSend', 'admin-homebuilder-styles-send');