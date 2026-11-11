<?php

defined('ROOT') or exit('Access denied!');

$router = \Core\Router\Router::getInstance();

// Routes publiques
// IMPORTANT: Les routes spécifiques doivent être déclarées AVANT les routes génériques
$router->map('GET', '/offers/add', 'Offers\Controllers\OffersPublicController#add', 'offers-add');
$router->map('POST', '/offers/add/send', 'Offers\Controllers\OffersPublicController#addSend', 'offers-add-send');
$router->map('GET', '/offers/view/[i:id][/?]', 'Offers\Controllers\OffersPublicController#view', 'offers-view');
$router->map('POST', '/offers/unlock/[i:id][/?]', 'Offers\Controllers\OffersPublicController#unlock', 'offers-unlock');
$router->map('GET', '/offers/unlock/[i:id][/?]', 'Offers\Controllers\OffersPublicController#unlock', 'offers-unlock-get');
$router->map('GET', '/offers/my-offers[/?]', 'Offers\Controllers\OffersPublicController#myOffers', 'offers-my-offers');
$router->map('GET', '/offers/edit/[i:id][/?]', 'Offers\Controllers\OffersPublicController#edit', 'offers-edit');
$router->map('POST', '/offers/edit/[i:id][/?]', 'Offers\Controllers\OffersPublicController#editSend', 'offers-edit-send');
$router->map('GET', '/offers[/?]', 'Offers\Controllers\OffersPublicController#index', 'offers-index');
$router->map('GET', '/offers/[i:poi_id][/?]', 'Offers\Controllers\OffersPublicController#poiOffers', 'offers-poi');

// Routes admin
$router->map('GET', '/admin/offers[/?]', 'Offers\Controllers\OffersAdminController#list', 'admin-offers-list');
$router->map('GET', '/admin/offers/add[/?]', 'Offers\Controllers\OffersAdminController#add', 'admin-offers-add');
$router->map('POST', '/admin/offers/add/send[/?]', 'Offers\Controllers\OffersAdminController#addSend', 'admin-offers-add-send');
$router->map('GET', '/admin/offers/edit/[i:id][/?]', 'Offers\Controllers\OffersAdminController#edit', 'admin-offers-edit');
$router->map('POST', '/admin/offers/edit/send[/?]', 'Offers\Controllers\OffersAdminController#editSend', 'admin-offers-edit-send');
$router->map('POST', '/admin/offers/delete/[i:id][/?]', 'Offers\Controllers\OffersAdminController#delete', 'admin-offers-delete');
$router->map('POST', '/admin/offers/approve/[i:id][/?]', 'Offers\Controllers\OffersAdminController#approve', 'admin-offers-approve');
$router->map('POST', '/admin/offers/reject/[i:id][/?]', 'Offers\Controllers\OffersAdminController#reject', 'admin-offers-reject');
$router->map('GET', '/admin/offers/stats[/?]', 'Offers\Controllers\OffersAdminController#stats', 'admin-offers-stats');

