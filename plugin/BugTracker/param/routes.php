<?php

defined('ROOT') or exit('No direct script access allowed');

$router = \Core\Router\Router::getInstance();

// Public : formulaire de rapport de bug (PSR-4 compatible)
$router->map('GET', '/BugTracker[/?]', 'BugTracker\\Controllers\\BugTrackerPublicController#home', 'BugTracker-home');
$router->map('GET', '/BugTracker/report', 'BugTracker\\Controllers\\BugTrackerPublicController#report', 'BugTracker-report');
$router->map('POST', '/BugTracker/submit', 'BugTracker\\Controllers\\BugTrackerPublicController#submit', 'BugTracker-submit');
$router->map('GET', '/BugTracker/list', 'BugTracker\\Controllers\\BugTrackerPublicController#list', 'BugTracker-list');
$router->map('GET', '/BugTracker/view/[*:id]', 'BugTracker\\Controllers\\BugTrackerPublicController#view', 'BugTracker-view');

// Admin : gestion des bugs
$router->map('GET', '/admin/bugtracker[/?]', 'BugTracker\\Controllers\\BugTrackerAdminController#dashboard', 'admin-BugTracker');
$router->map('GET', '/admin/bugtracker/view/[*:id]', 'BugTracker\\Controllers\\BugTrackerAdminController#view', 'admin-BugTracker-view');
$router->map('POST', '/admin/bugtracker/update-status', 'BugTracker\\Controllers\\BugTrackerAdminController#updateStatus', 'admin-BugTracker-update-status');
$router->map('POST', '/admin/bugtracker/update-priority', 'BugTracker\\Controllers\\BugTrackerAdminController#updatePriority', 'admin-BugTracker-update-priority');
$router->map('POST', '/admin/bugtracker/add-comment', 'BugTracker\\Controllers\\BugTrackerAdminController#addComment', 'admin-BugTracker-add-comment');
$router->map('POST', '/admin/bugtracker/delete', 'BugTracker\\Controllers\\BugTrackerAdminController#delete', 'admin-BugTracker-delete');




