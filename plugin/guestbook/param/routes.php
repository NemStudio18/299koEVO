<?php

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('No direct script access allowed');

$router = \Core\Router\Router::getInstance();

$router->map('GET', '/guestbook[/?]', 'GuestbookController#home', 'guestbook-home');
$router->map('POST', '/guestbook/add', 'GuestbookController#add', 'guestbook-add');
$router->map('GET', '/guestbook/like/[i:id]', 'GuestbookController#like', 'guestbook-like');

$router->map('GET', '/admin/guestbook[/?]', 'GuestbookAdminController#list', 'guestbook-admin-list');
$router->map('GET', '/admin/guestbook/params[/?]', 'GuestbookAdminController#params', 'guestbook-admin-params');
$router->map('POST', '/admin/guestbook/params/save', 'GuestbookAdminController#saveParams', 'guestbook-admin-save-params');
$router->map('GET', '/admin/guestbook/approve/[i:id]/[a:token]', 'GuestbookAdminController#approve', 'guestbook-admin-approve');
$router->map('GET', '/admin/guestbook/delete/[i:id]/[a:token]', 'GuestbookAdminController#delete', 'guestbook-admin-delete');
$router->map('POST', '/admin/guestbook/reply/[i:id]/[a:token]', 'GuestbookAdminController#reply', 'guestbook-admin-reply');
$router->map('GET', '/admin/guestbook/delete-reply/[i:id]/[a:token]', 'GuestbookAdminController#deleteReply', 'guestbook-admin-delete-reply');
