<?php

defined('ROOT') or exit('No direct script access allowed');

$router = \Core\Router\Router::getInstance();

// =========================================================
// ROUTES API PUBLIQUES (WIDGET TCHAT)
// =========================================================
$router->map('POST', '/api/livechat/init[/?]',   'LiveChat\\Controllers\\LiveChatApiController#init',  'livechat-api-init');
$router->map('POST', '/api/livechat/send[/?]',   'LiveChat\\Controllers\\LiveChatApiController#send',  'livechat-api-send');
$router->map('GET',  '/api/livechat/poll[/?]',   'LiveChat\\Controllers\\LiveChatApiController#poll',  'livechat-api-poll');

// =========================================================
// ROUTES ADMINISTRATION (MAX)
// =========================================================
$router->map('GET',  '/admin/livechat[/?]',              'LiveChat\\Controllers\\LiveChatAdminController#index',        'admin-livechat');
$router->map('GET',  '/admin/LiveChat[/?]',              'LiveChat\\Controllers\\LiveChatAdminController#index',        'admin-livechat-camel');
$router->map('GET',  '/admin/plugins/livechat[/?]',      'LiveChat\\Controllers\\LiveChatAdminController#index',        'admin-livechat-plugin');
$router->map('GET',  '/admin/plugins/LiveChat[/?]',      'LiveChat\\Controllers\\LiveChatAdminController#index',        'admin-livechat-plugin-camel');
$router->map('GET',  '/admin/livechat/conversations[/?]', 'LiveChat\\Controllers\\LiveChatAdminController#listConversations', 'admin-livechat-conversations');
$router->map('POST', '/admin/livechat/reply[/?]',        'LiveChat\\Controllers\\LiveChatAdminController#adminReply',   'admin-livechat-reply');
$router->map('GET',  '/admin/livechat/poll[/?]',         'LiveChat\\Controllers\\LiveChatAdminController#adminPoll',    'admin-livechat-poll');
$router->map('POST', '/admin/livechat/toggle-status[/?]', 'LiveChat\\Controllers\\LiveChatAdminController#toggleStatus', 'admin-livechat-toggle');
$router->map('POST', '/admin/livechat/config[/?]',        'LiveChat\\Controllers\\LiveChatAdminController#saveConfig',   'admin-livechat-config');

