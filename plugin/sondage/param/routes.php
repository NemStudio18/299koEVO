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

$router->map('GET', '/sondage[/?]', 'SondageController#home', 'sondage-home');
$router->map('GET', '/sondage/[*:id]', 'SondageController#read', 'sondage-read');
$router->map('POST', '/sondage/vote/[*:id]', 'SondageController#vote', 'sondage-vote');

$router->map('GET', '/admin/sondage[/?]', 'SondageAdminController#list', 'sondage-admin-list');
$router->map('GET', '/admin/sondage/edit[/?]', 'SondageAdminController#edit', 'sondage-admin-edit');
$router->map('GET', '/admin/sondage/edit/[*:id]', 'SondageAdminController#edit', 'sondage-admin-edit-id');
$router->map('POST', '/admin/sondage/save', 'SondageAdminController#save', 'sondage-admin-save');
$router->map('GET', '/admin/sondage/delete/[*:id]/[a:token]', 'SondageAdminController#delete', 'sondage-admin-delete');
