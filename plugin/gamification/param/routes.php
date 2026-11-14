<?php

defined('ROOT') or exit('No direct script access allowed');

$router = \Core\Router\Router::getInstance();

// Admin
$router->map('GET', '/admin/gamification', 'Gamification\\Controllers\\GamificationAdminController#dashboard', 'admin-gamification');
$router->map('POST', '/admin/gamification/save', 'Gamification\\Controllers\\GamificationAdminController#save', 'admin-gamification-save');
$router->map('POST', '/admin/gamification/palier/save', 'Gamification\\Controllers\\GamificationAdminController#savePalier', 'admin-gamification-palier-save');
$router->map('GET', '/admin/gamification/palier/delete/[i:id]', 'Gamification\\Controllers\\GamificationAdminController#deletePalier', 'admin-gamification-palier-delete');
$router->map('GET', '/admin/gamification/recalculate', 'Gamification\\Controllers\\GamificationAdminController#recalculate', 'admin-gamification-recalculate');
$router->map('POST', '/admin/gamification/recalculate/run', 'Gamification\\Controllers\\GamificationAdminController#recalculateRun', 'admin-gamification-recalculate-run');

// Public - Profil utilisateur
$router->map('GET', '/users/profile/gamification', 'Gamification\\Controllers\\GamificationPublicController#profile', 'gamification-profile');

// Public - Leaderboard (si activé dans la config)
$router->map('GET', '/gamification/leaderboard', 'Gamification\\Controllers\\GamificationPublicController#leaderboard', 'gamification-leaderboard');

// Public - Profil utilisateur (public)
$router->map('GET', '/users/profile/public/[i:id]', 'Gamification\\Controllers\\GamificationPublicController#publicProfile', 'gamification-public-profile');


