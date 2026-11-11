<?php

defined('ROOT') or exit('Access denied!');

$router = \Core\Router\Router::getInstance();

// Routes publiques
$router->map('GET', '/notifications[/?]', 'Notifications\Controllers\NotificationsPublicController#list', 'notifications-list');
$router->map('POST', '/notifications/mark-read/[a:id][/?]', 'Notifications\Controllers\NotificationsPublicController#markRead', 'notifications-mark-read');
$router->map('POST', '/notifications/mark-all-read[/?]', 'Notifications\Controllers\NotificationsPublicController#markAllAsRead', 'notifications-mark-all-read');
