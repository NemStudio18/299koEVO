<?php

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('Access denied!');

$router = \Core\Router\Router::getInstance();

$router->map('GET', '/users/login[/?]', 'Core\Auth\Controllers\UsersLoginController#login', 'login');
$router->map('POST', '/users/login-send[/?]', 'Core\Auth\Controllers\UsersLoginController#loginSend', 'login-send');
$router->map('GET', '/users/logout[/?]', 'Core\Auth\Controllers\UsersLoginController#logout', 'logout');
$router->map('GET', '/users/lost-password[/?]', 'Core\Auth\Controllers\UsersLoginController#lostPassword', 'lost-password');
$router->map('POST', '/users/lost-password-send[/?]', 'Core\Auth\Controllers\UsersLoginController#lostPasswordSend', 'lost-password-send');
$router->map('GET', '/users/lost-password/confirm/[a:token][/?]', 'Core\Auth\Controllers\UsersLoginController#lostPasswordConfirm', 'lost-password-confirm');

$router->map('GET', '/admin/users[/?]', 'Core\Auth\Controllers\UsersAdminController#home', 'users-admin-home');
$router->map('GET', '/admin/users/add', 'Core\Auth\Controllers\UsersAdminManagementController#addUser', 'users-add');
$router->map('POST', '/admin/users/add/send', 'Core\Auth\Controllers\UsersAdminManagementController#addUserSend', 'users-add-send');
$router->map('GET', '/admin/users/edit/[i:id]', 'Core\Auth\Controllers\UsersAdminManagementController#edit', 'users-edit');
$router->map('POST', '/admin/users/edit/send', 'Core\Auth\Controllers\UsersAdminManagementController#editUserSend', 'users-edit-send');
$router->map('GET', '/admin/users/delete/[i:id]/[a:token]', 'Core\Auth\Controllers\UsersAdminManagementController#delete', 'users-delete');

