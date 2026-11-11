<?php

/**
 * @copyright (C) 2023, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('Access denied!');

// DEBUG
// file_put_contents(ROOT . 'data/logs/routes_debug.log', date('Y-m-d H:i:s') . " - Page routes loaded\n", FILE_APPEND);

$router = \Core\Router\Router::getInstance();

$router->map('GET|POST', '/page[/?]', 'Core\Page\Controllers\PageController#home', 'page-home');
$router->map('GET', '/mentions-legales[/?]', 'Core\Page\Controllers\PageController#mentionsLegales', 'mentions-legales');
$router->map('GET', '/page/mentions-legales[/?]', 'Core\Page\Controllers\PageController#mentionsLegales', 'page-mentions-legales');
$router->map('GET', '/pro[/?]', 'Core\Page\Controllers\PageController#espacePro', 'espace-pro');
$router->map('GET', '/comment-revendiquer[/?]', 'Core\Page\Controllers\PageController#espacePro', 'comment-revendiquer');
$router->map('GET', '/page/comment-revendiquer[/?]', 'Core\Page\Controllers\PageController#espacePro', 'page-comment-revendiquer');
$router->map('POST', '/api/pro/chat[/?]', 'Core\Page\Controllers\PageController#sendProChatAjax', 'pro-chat-ajax');
$router->map('GET|POST', '/page/[*:name]-[a:id][/?]', 'Core\Page\Controllers\PageController#read', 'page-read');

$router->map('GET', '/admin/page[/?]', 'Core\Page\Controllers\PageAdminController#list', 'page-admin-home');
$router->map('GET', '/admin/page/new', 'Core\Page\Controllers\PageAdminController#new', 'page-admin-new');
$router->map('POST', '/admin/page/save', 'Core\Page\Controllers\PageAdminController#save', 'page-admin-save');
$router->map('GET', '/admin/page/edit/[a:id]', 'Core\Page\Controllers\PageAdminController#edit', 'page-admin-edit');
$router->map('GET', '/admin/page/new-parent', 'Core\Page\Controllers\PageAdminController#newParent', 'page-admin-new-parent');
$router->map('GET', '/admin/page/new-link', 'Core\Page\Controllers\PageAdminController#newLink', 'page-admin-new-link');
$router->map('GET', '/admin/page/maintenance/[a:id]/[a:token]', 'Core\Page\Controllers\PageAdminController#maintenance', 'page-admin-maintenance');
$router->map('GET', '/admin/page/delete/[a:id]/[a:token]', 'Core\Page\Controllers\PageAdminController#delete', 'page-admin-delete');
$router->map('GET|POST', '/admin/page/page-up/[a:id]/[a:token]', 'Core\Page\Controllers\PageAdminController#pageUp', 'page-admin-page-up');
$router->map('GET|POST', '/admin/page/page-down/[a:id]/[a:token]', 'Core\Page\Controllers\PageAdminController#pageDown', 'page-admin-page-down');
$router->map('POST', '/admin/page/save-order', 'Core\Page\Controllers\PageAdminController#saveOrder', 'page-admin-save-order');
$router->map('GET|POST', '/admin/page/update-visibility-flags', 'Core\Page\Controllers\PageAdminController#updateVisibilityFlags', 'page-admin-update-visibility-flags');
$router->map('POST', '/admin/page/toggle-global-icons', 'Core\Page\Controllers\PageAdminController#toggleGlobalIcons', 'page-admin-toggle-global-icons');
$router->map('GET', '/admin/page/list-plugin-routes-ajax/[*:plugin]', 'Core\Page\Controllers\PageAdminController#listPluginRoutesAjax', 'page-admin-list-plugin-routes-ajax');
$router->map('GET', '/admin/page/list-providers-ajax', 'Core\Page\Controllers\PageAdminController#listProvidersAjax', 'page-admin-list-providers-ajax');
$router->map('POST', '/admin/page/explain-policy-ajax', 'Core\Page\Controllers\PageAdminController#explainPolicyAjax', 'page-admin-explain-policy-ajax');
