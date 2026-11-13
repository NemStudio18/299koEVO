<?php

defined('ROOT') OR exit('Access denied!');

$router = \Core\Router\Router::getInstance();

// Routes publiques
$router->map('GET', '/annonces[/?]', 'Annonces\\Controllers\\AnnoncesController#home', 'annonces-home');
$router->map('GET', '/annonces/[a:id]-[*:slug][/?]', 'Annonces\\Controllers\\AnnoncesController#view', 'annonces-view');
$router->map('POST', '/annonces/[a:id]/reserver[/?]', 'Annonces\\Controllers\\AnnoncesController#reserve', 'annonces-reserve');

// Routes administration
$router->map('GET', '/admin/annonces[/?]', 'Annonces\\Controllers\\AnnoncesAdminController#home', 'admin-annonces-home');
$router->map('GET', '/admin/annonces/edit[/?]', 'Annonces\\Controllers\\AnnoncesAdminController#edit', 'admin-annonces-edit');
$router->map('GET', '/admin/annonces/edit/[a:id][/?]', 'Annonces\\Controllers\\AnnoncesAdminController#editId', 'admin-annonces-edit-id');
$router->map('POST', '/admin/annonces/save[/?]', 'Annonces\\Controllers\\AnnoncesAdminController#save', 'admin-annonces-save');
$router->map('GET', '/admin/annonces/delete/[a:id]/[a:token][/?]', 'Annonces\\Controllers\\AnnoncesAdminController#delete', 'admin-annonces-delete');
$router->map('POST', '/admin/annonces/reservation/[a:id]/status[/?]', 'Annonces\\Controllers\\AnnoncesAdminController#updateReservationStatus', 'admin-annonces-reservation-status');
$router->map('GET', '/admin/annonces/reservation/delete/[a:id]/[a:token][/?]', 'Annonces\\Controllers\\AnnoncesAdminController#deleteReservation', 'admin-annonces-reservation-delete');
$router->map('POST', '/admin/annonces/saveConf[/?]', 'Annonces\\Controllers\\AnnoncesAdminController#saveConf', 'admin-annonces-save-config');

