<?php

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('No direct script access allowed');

$router = \Core\Router\Router::getInstance();

// Public
$router->map('GET', '/faq[/?]', 'FaqController#home', 'faq-home');
$router->map('POST', '/faq/vote/[i:id]', 'FaqController#vote', 'faq-vote');
$router->map('POST', '/faq/ask', 'FaqController#ask', 'faq-ask');

// Admin
$router->map('GET', '/admin/faq[/?]', 'FaqAdminController#list', 'faq-admin-list');
$router->map('GET', '/admin/faq/edit/[i:id]?', 'FaqAdminController#edit', 'faq-admin-edit');
$router->map('POST', '/admin/faq/save', 'FaqAdminController#save', 'faq-admin-save');
$router->map('POST', '/admin/faq/delete/[i:id]', 'FaqAdminController#delete', 'faq-admin-delete');



