<?php

defined('ROOT') or exit('No direct script access allowed');

$router = \Core\Router\Router::getInstance();

// Public : page d'accueil du plugin claim
$router->map('GET', '/claim[/?]', 'Claim\\Controllers\\ClaimPublicController#home', 'claim-home');

// Routes spécifiques en premier
$router->map('GET', '/claim/my-claims', 'Claim\\Controllers\\ClaimPublicController#myClaims', 'claim-my-claims');
$router->map('POST', '/claim/submit', 'Claim\\Controllers\\ClaimPublicController#submit', 'claim-submit');
$router->map('GET', '/claim/claim/[i:id]', 'Claim\\Controllers\\ClaimPublicController#start', 'claim-claim');

// Public : formulaire de revendication d'une fiche OSM (Catch-all en dernier)
$router->map('GET', '/claim/[*:id]', 'Claim\\Controllers\\ClaimPublicController#start', 'claim-start');

// Admin : tableau de bord (squelette)
$router->map('GET', '/admin/claim[/?]', 'Claim\\Controllers\\ClaimAdminController#dashboard', 'admin-claim');
$router->map('POST', '/admin/claim/decide', 'Claim\\Controllers\\ClaimAdminController#decide', 'admin-claim-decide');
$router->map('GET', '/admin/claim/proof/[*:id]', 'Claim\\Controllers\\ClaimAdminController#proof', 'admin-claim-proof');


