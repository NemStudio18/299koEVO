<?php

defined('ROOT') or exit('Access denied!');

$router = \Core\Router\Router::getInstance();

// Routes publiques
$router->map('GET', '/poi-reviews[/?]', 'PoiReviews\Controllers\ReviewsPublicController#index', 'poi-reviews-index');
$router->map('GET', '/poi-reviews/[:poi_id]/[?]', 'PoiReviews\Controllers\ReviewsPublicController#list', 'poi-reviews-list');
$router->map('POST', '/poi-reviews/add[/?]', 'PoiReviews\Controllers\ReviewsPublicController#add', 'poi-reviews-add');
$router->map('POST', '/poi-reviews/moderate/[i:id][/?]', 'PoiReviews\Controllers\ReviewsPublicController#moderate', 'poi-reviews-moderate');

// Routes admin
$router->map('GET', '/admin/poi-reviews[/?]', 'PoiReviews\Controllers\ReviewsAdminController#index', 'admin-poi-reviews-list');
$router->map('GET', '/admin/poireviews[/?]', 'PoiReviews\Controllers\ReviewsAdminController#index');
$router->map('POST', '/admin/poi-reviews/restore/[i:id][/?]', 'PoiReviews\Controllers\ReviewsAdminController#restore', 'admin-poi-reviews-restore');
$router->map('POST', '/admin/poi-reviews/delete/[i:id][/?]', 'PoiReviews\Controllers\ReviewsAdminController#delete', 'admin-poi-reviews-delete');

