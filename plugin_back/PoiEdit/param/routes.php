<?php

defined('ROOT') or exit('No direct script access allowed');

$router = \Core\Router\Router::getInstance();

// Public : page d'accueil du plugin (recherche par défaut si pas de paramètre)
$router->map('GET', '/poi-edit[/?]', 'PoiEdit\\Controllers\\PoiEditPublicController#home', 'poi-edit-index');
// Alias générés par le CMS à partir du nom du plugin (avec et sans casse)
$router->map('GET', '/PoiEdit[/?]', 'PoiEdit\\Controllers\\PoiEditPublicController#home', 'poi-edit-index-alias');
$router->map('GET', '/poiedit[/?]', 'PoiEdit\\Controllers\\PoiEditPublicController#home', 'poi-edit-index-alias2');

// Public : ajouter un POI (workflow complet)
$router->map('GET', '/poi-edit/contribute[/?]', 'PoiEdit\\Controllers\\PoiEditPublicController#contribute', 'poi-edit-contribute');
$router->map('GET', '/poi-edit/start[/?]', 'PoiEdit\\Controllers\\PoiEditPublicController#start', 'poi-edit-start');
$router->map('GET', '/poi-edit/my-contributions[/?]', 'PoiEdit\\Controllers\\PoiEditPublicController#myContributions', 'poi-edit-my-contributions');

// Public : édition d'un POI existant (via suggestions ou accès direct si permis)
$router->map('GET', '/poi-edit/[*:type]/[*:id]', 'PoiEdit\\Controllers\\PoiEditPublicController#edit', 'poi-edit-edit');
$router->map('GET', '/poi-edit/edit/[*:type]/[*:id]', 'PoiEdit\\Controllers\\PoiEditPublicController#edit', 'poi-edit-edit-direct');
$router->map('POST', '/poi-edit/save', 'PoiEdit\\Controllers\\PoiEditPublicController#save', 'poi-edit-save');

// Public : propositions de création
$router->map('POST', '/poi-edit/propose', 'PoiEdit\\Controllers\\PoiEditPublicController#propose', 'poi-edit-propose');

// Admin : index
$router->map('GET', '/admin/poi-edit[/?]', 'PoiEdit\\Controllers\\PoiEditAdminController#index', 'admin-poi-edit');
// Alias: le menu admin génère /admin/poiedit (nom du plugin en minuscules sans tiret)
$router->map('GET', '/admin/poiedit[/?]', 'PoiEdit\\Controllers\\PoiEditAdminController#index', 'admin-poi-edit-alias');
$router->map('GET', '/admin/PoiEdit[/?]', 'PoiEdit\\Controllers\\PoiEditAdminController#index', 'admin-poi-edit-alias2');

// Admin : création directe d'un POI (sans proposition ni gamification)
$router->map('GET', '/admin/poi-edit/create[/?]', 'PoiEdit\\Controllers\\PoiEditAdminController#create', 'admin-poi-edit-create');
$router->map('POST', '/admin/poi-edit/create/save', 'PoiEdit\\Controllers\\PoiEditAdminController#createSave', 'admin-poi-edit-create-save');

// Admin : suggestions & propositions
$router->map('GET', '/admin/poi-edit/suggestions', 'PoiEdit\\Controllers\\PoiEditAdminController#suggestions', 'admin-poi-edit-suggestions');
$router->map('GET', '/admin/poi-edit/proposals', 'PoiEdit\\Controllers\\PoiEditAdminController#proposals', 'admin-poi-edit-proposals');
$router->map('POST', '/admin/poi-edit/decide', 'PoiEdit\\Controllers\\PoiEditAdminController#decide', 'admin-poi-edit-decide');
$router->map('POST', '/admin/poi-edit/decide-proposal', 'PoiEdit\\Controllers\\PoiEditAdminController#decideProposal', 'admin-poi-edit-decide-proposal');

// Admin : édition directe (admin)
$router->map('GET', '/admin/poi-edit/edit/[*:type]/[*:id]', 'PoiEdit\\Controllers\\PoiEditAdminController#edit', 'admin-poi-edit-edit');
$router->map('POST', '/admin/poi-edit/edit/save', 'PoiEdit\\Controllers\\PoiEditAdminController#save', 'admin-poi-edit-save');
$router->map('POST', '/admin/poi-edit/upload-photo', 'PoiEdit\\Controllers\\PoiEditAdminController#uploadPhoto', 'admin-poi-edit-upload-photo');
$router->map('POST', '/admin/poi-edit/delete-photo/[*:photoId]', 'PoiEdit\\Controllers\\PoiEditAdminController#deletePhoto', 'admin-poi-edit-delete-photo');

// Admin : configuration
$router->map('GET', '/admin/poi-edit/config', 'PoiEdit\\Controllers\\PoiEditAdminController#config', 'admin-poi-edit-config');
$router->map('POST', '/admin/poi-edit/config/save', 'PoiEdit\\Controllers\\PoiEditAdminController#configSave', 'admin-poi-edit-config-save');

// Admin : tags personnalisés
$router->map('GET', '/admin/PoiEdit/custom-tags', 'PoiEdit\\Controllers\\PoiEditAdminController#customTags', 'admin-poi-edit-custom-tags');
$router->map('POST', '/admin/PoiEdit/custom-tags/save', 'PoiEdit\\Controllers\\PoiEditAdminController#customTagsSave', 'admin-poi-edit-custom-tags-save');
$router->map('POST', '/admin/PoiEdit/custom-tags/delete', 'PoiEdit\\Controllers\\PoiEditAdminController#customTagsDelete', 'admin-poi-edit-custom-tags-delete');

// Admin : catégories éditoriales
$router->map('GET', '/admin/PoiEdit/categories', 'PoiEdit\\Controllers\\PoiEditAdminController#categories', 'admin-poi-edit-categories');
$router->map('POST', '/admin/PoiEdit/categories/save', 'PoiEdit\\Controllers\\PoiEditAdminController#categoriesSave', 'admin-poi-edit-categories-save');
$router->map('POST', '/admin/PoiEdit/categories/delete', 'PoiEdit\\Controllers\\PoiEditAdminController#categoriesDelete', 'admin-poi-edit-categories-delete');

$router->map('POST', '/admin/PoiEdit/update-photo-caption', 'PoiEdit\\Controllers\\PoiEditAdminController#updatePhotoCaption', 'admin-poi-edit-update-photo-caption');

// Admin : gestion des types et catégories (ancien système - conservé pour compatibilité)
// On change les noms pour éviter les collisions si nécessaire, mais ici on garde les URIs originaux pour le moment si pas de clache de nom
$router->map('GET', '/admin/poi-edit/types/add', 'PoiEdit\\Controllers\\PoiEditAdminController#addType', 'admin-poi-edit-add-type');
$router->map('POST', '/admin/poi-edit/types/add', 'PoiEdit\\Controllers\\PoiEditAdminController#addType', 'admin-poi-edit-add-type-save');
$router->map('GET', '/admin/poi-edit/type-proposals', 'PoiEdit\\Controllers\\PoiEditAdminController#typeProposals', 'admin-poi-edit-type-proposals');
$router->map('POST', '/admin/poi-edit/type-proposal/decide', 'PoiEdit\\Controllers\\PoiEditAdminController#decideTypeProposal', 'admin-poi-edit-type-proposal-decide');

/* Admin : gestion des attributs (features) - DÉSACTIVÉ (utiliser custom-tags à la place)
$router->map('GET', '/admin/poi-edit/features', 'PoiEdit\\Controllers\\PoiEditAdminController#features', 'admin-poi-edit-features-legacy');
...
*/

// Admin : gestion des tags personnalisés (Legacy - names updated to avoid clash)
$router->map('GET', '/admin/poi-edit/custom-tags/add', 'PoiEdit\\Controllers\\PoiEditAdminController#addCustomTag', 'admin-poi-edit-add-custom-tag');
$router->map('POST', '/admin/poi-edit/custom-tags/save', 'PoiEdit\\Controllers\\PoiEditAdminController#saveCustomTag', 'admin-poi-edit-save-custom-tag');
$router->map('GET', '/admin/poi-edit/custom-tags/edit/[i:id]', 'PoiEdit\\Controllers\\PoiEditAdminController#editCustomTag', 'admin-poi-edit-edit-custom-tag');
$router->map('GET', '/admin/poi-edit/custom-tags/delete/[i:id]', 'PoiEdit\\Controllers\\PoiEditAdminController#deleteCustomTag', 'admin-poi-edit-delete-custom-tag');

