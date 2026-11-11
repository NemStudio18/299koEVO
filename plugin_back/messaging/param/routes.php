<?php

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('No direct script access allowed');

$router = \Core\Router\Router::getInstance();

// Routes publiques
$router->map('GET', '/messaging[/?]', 'Messaging\\Controllers\\MessagingPublicController#inbox', 'messaging-inbox');
$router->map('GET', '/messaging/compose[/?]', 'Messaging\\Controllers\\MessagingPublicController#compose', 'messaging-compose');
$router->map('POST', '/messaging/send[/?]', 'Messaging\\Controllers\\MessagingPublicController#send', 'messaging-send');
$router->map('GET', '/messaging/read/[i:id]', 'Messaging\\Controllers\\MessagingPublicController#read', 'messaging-read');
$router->map('POST', '/messaging/delete/[i:id]', 'Messaging\\Controllers\\MessagingPublicController#delete', 'messaging-delete');
$router->map('POST', '/messaging/mark-read/[i:id]', 'Messaging\\Controllers\\MessagingPublicController#markRead', 'messaging-mark-read');



