<?php
defined("ROOT") or exit("No direct script access allowed");

$router = \Core\Router\Router::getInstance();

// --- Routes Publiques Visiteurs / Clients ---
$router->map("GET|HEAD", "/rdv[/?]", "ProRdv\\Controllers\\PublicRdvController#defaultIndex", "rdv-public-home");
$router->map("GET|HEAD", "/ProRdv[/?]", "ProRdv\\Controllers\\PublicRdvController#defaultIndex", "ProRdv");
$router->map("GET|HEAD", "/rdv/creneaux", "ProRdv\\Controllers\\PublicRdvController#slotsJson", "rdv-public-slots");
$router->map("POST", "/rdv/reserver", "ProRdv\\Controllers\\PublicRdvController#postReserve", "rdv-public-post-reserve");
$router->map("GET|HEAD", "/rdv/confirmer/[*:token]", "ProRdv\\Controllers\\PublicRdvController#confirm", "rdv-public-confirm");
$router->map("GET|HEAD", "/rdv/annuler/[*:token]", "ProRdv\\Controllers\\PublicRdvController#cancel", "rdv-public-cancel");
$router->map("GET|HEAD", "/rdv/poi/[i:id]", "ProRdv\\Controllers\\PublicRdvController#byPoi", "rdv-public-by-poi");
$router->map("GET|HEAD", "/rdv/[*:slug]", "ProRdv\\Controllers\\PublicRdvController#index", "rdv-public-slug");

// --- Routes Espace Pro / Commerçants ---
$router->map("GET|HEAD", "/pro/rdv[/?]", "ProRdv\\Controllers\\ProRdvController#index", "rdv-pro-index");
$router->map("GET|POST", "/pro/rdv/activate", "ProRdv\\Controllers\\ProRdvController#activate", "rdv-pro-activate");
$router->map("POST", "/pro/rdv/action", "ProRdv\\Controllers\\ProRdvController#action", "rdv-pro-action");
$router->map("GET|POST", "/pro/rdv/config", "ProRdv\\Controllers\\ProRdvController#config", "rdv-pro-config");

// --- Routes Administration Globale ---
$router->map("GET|POST", "/admin/prordv[/?]", "ProRdv\\Controllers\\AdminRdvController#index", "rdv-admin-index");
$router->map("GET|POST", "/admin/prordv/commerce/[i:id]", "ProRdv\\Controllers\\AdminRdvController#viewCommerce", "rdv-admin-commerce");
$router->map("POST", "/admin/prordv/toggle/[i:id]", "ProRdv\\Controllers\\AdminRdvController#toggleCommerce", "rdv-admin-toggle");
$router->map("POST", "/admin/prordv/delete/[i:id]", "ProRdv\\Controllers\\AdminRdvController#deleteCommerce", "rdv-admin-delete");
