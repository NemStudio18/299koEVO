<?php

defined('ROOT') or exit('Access denied!');

$router = \Core\Router\Router::getInstance();

// Routes publiques
$router->map('POST', '/poi-favorites/toggle[/?]', 'PoiFavorites\Controllers\FavoritesPublicController#toggle', 'poi-favorites-toggle');
$router->map('GET', '/poi-favorites/my-favorites[/?]', 'PoiFavorites\Controllers\FavoritesPublicController#myFavorites', 'poi-favorites-my-favorites');
$router->map('POST', '/poi-favorites/remove[/?]', 'PoiFavorites\Controllers\FavoritesPublicController#remove', 'poi-favorites-remove');
$router->map('GET', '/poi-favorites/notifications[/?]', 'PoiFavorites\Controllers\FavoritesPublicController#notifications', 'poi-favorites-notifications');
$router->map('POST', '/poi-favorites/notifications/mark-read[/?]', 'PoiFavorites\Controllers\FavoritesPublicController#markRead', 'poi-favorites-mark-read');

