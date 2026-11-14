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

$router->map('GET', '/admin/csseditor[/?]', 'CssEditor\Controllers\CssEditorAdminController#home', 'csseditor-admin-home');
$router->map('POST', '/admin/csseditor/save', 'CssEditor\Controllers\CssEditorAdminController#save', 'csseditor-admin-save');
$router->map('POST', '/admin/csseditor/save-css', 'CssEditor\Controllers\CssEditorAdminController#saveCss', 'csseditor-admin-save-css');

$router->map('GET', '/csseditor/custom.css', 'CssEditor\Controllers\CssEditorPublicController#customCss', 'csseditor-public-custom-css');