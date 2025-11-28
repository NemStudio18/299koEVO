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
$router->map('GET', '/users/register[/?]', 'Core\Auth\Controllers\UsersLoginController#register', 'register');
$router->map('POST', '/users/register/send[/?]', 'Core\Auth\Controllers\UsersLoginController#registerSend', 'register-send');
$router->map('GET', '/users/register/confirm/[a:token][/?]', 'Core\Auth\Controllers\UsersLoginController#confirmRegistration', 'register-confirm');
$router->map('GET', '/users/profile[/?]', 'Core\Auth\Controllers\UsersLoginController#profile', 'profile');
$router->map('POST', '/users/profile/save[/?]', 'Core\Auth\Controllers\UsersLoginController#profileSave', 'profile-save');

$router->map('GET', '/admin/users[/?]', 'Core\Auth\Controllers\UsersAdminController#home', 'users-admin-home');
$router->map('GET', '/admin/users/add', 'Core\Auth\Controllers\UsersAdminManagementController#addUser', 'users-add');
$router->map('POST', '/admin/users/add/send', 'Core\Auth\Controllers\UsersAdminManagementController#addUserSend', 'users-add-send');
$router->map('GET', '/admin/users/edit/[i:id]', 'Core\Auth\Controllers\UsersAdminManagementController#edit', 'users-edit');
$router->map('POST', '/admin/users/edit/send', 'Core\Auth\Controllers\UsersAdminManagementController#editUserSend', 'users-edit-send');
$router->map('GET', '/admin/users/delete/[i:id]/[a:token]', 'Core\Auth\Controllers\UsersAdminManagementController#delete', 'users-delete');

$router->map('GET', '/admin/users/groups[/?]', 'Core\Auth\Controllers\GroupsAdminController#home', 'groups-admin-home');
$router->map('GET', '/admin/users/groups/add', 'Core\Auth\Controllers\GroupsAdminController#add', 'groups-add');
$router->map('POST', '/admin/users/groups/add/send', 'Core\Auth\Controllers\GroupsAdminController#addSend', 'groups-add-send');
$router->map('GET', '/admin/users/groups/edit/[i:id]', 'Core\Auth\Controllers\GroupsAdminController#edit', 'groups-edit');
$router->map('POST', '/admin/users/groups/edit/send', 'Core\Auth\Controllers\GroupsAdminController#editSend', 'groups-edit-send');
$router->map('GET', '/admin/users/groups/delete/[i:id]/[a:token]', 'Core\Auth\Controllers\GroupsAdminController#delete', 'groups-delete');

