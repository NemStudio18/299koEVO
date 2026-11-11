<?php

defined('ROOT') or exit('No direct script access allowed');

$router = \Core\Router\Router::getInstance();

// =========================================================
// ROUTES ADMIN
// =========================================================

// Dashboard (+ alias auto-généré par le menu admin en minuscules)
$router->map('GET', '/admin/PoiManager[/?]',    'PoiManager\\Controllers\\PoiManagerAdminController#dashboard', 'admin-poimanager');
$router->map('GET', '/admin/poimanager[/?]',    'PoiManager\\Controllers\\PoiManagerAdminController#dashboard', 'admin-poimanager-alias');
$router->map('GET', '/admin/PoiManager/history','PoiManager\\Controllers\\PoiManagerAdminController#history',   'admin-poimanager-history');

// Liste des POIs
$router->map('GET', '/admin/PoiManager/list[/?]', 'PoiManager\\Controllers\\PoiManagerAdminController#listPois', 'admin-poimanager-list');

// Création directe
$router->map('GET',  '/admin/PoiManager/create',     'PoiManager\\Controllers\\PoiManagerAdminController#create',     'admin-poimanager-create');
$router->map('POST', '/admin/PoiManager/create',     'PoiManager\\Controllers\\PoiManagerAdminController#createSave', 'admin-poimanager-create-save');

// Édition directe
$router->map('GET',  '/admin/PoiManager/edit/[*:id]',  'PoiManager\\Controllers\\PoiManagerAdminController#edit', 'admin-poimanager-edit');
$router->map('POST', '/admin/PoiManager/edit/save',    'PoiManager\\Controllers\\PoiManagerAdminController#save', 'admin-poimanager-save');

// Suppression directe
$router->map('POST', '/admin/PoiManager/delete/[*:id]', 'PoiManager\\Controllers\\PoiManagerAdminController#delete', 'admin-poimanager-delete');

// Proposals (nouveaux POIs proposés par des membres)
$router->map('GET',  '/admin/PoiManager/proposals',       'PoiManager\\Controllers\\PoiManagerAdminController#proposals',      'admin-poimanager-proposals');
$router->map('POST', '/admin/PoiManager/decideProposal',  'PoiManager\\Controllers\\PoiManagerAdminController#decideProposal', 'admin-poimanager-decide-proposal');

// Suggestions (corrections proposées par des membres)
$router->map('GET',  '/admin/PoiManager/suggestions',        'PoiManager\\Controllers\\PoiManagerAdminController#suggestions',     'admin-poimanager-suggestions');
$router->map('POST', '/admin/PoiManager/decideSuggestion',   'PoiManager\\Controllers\\PoiManagerAdminController#decideSuggestion','admin-poimanager-decide-suggestion');

// Photos
$router->map('POST', '/admin/PoiManager/uploadPhoto',         'PoiManager\\Controllers\\PoiManagerAdminController#uploadPhoto',  'admin-poimanager-upload-photo');
$router->map('POST', '/admin/PoiManager/deletePhoto/[*:id]',  'PoiManager\\Controllers\\PoiManagerAdminController#deletePhoto',  'admin-poimanager-delete-photo');
$router->map('POST', '/admin/PoiManager/updatePhotoCaption',  'PoiManager\\Controllers\\PoiManagerAdminController#updatePhotoCaption', 'admin-poimanager-update-photo-caption');
$router->map('GET',  '/admin/PoiManager/photosValidation',    'PoiManager\\Controllers\\PoiManagerAdminController#photosValidation', 'admin-poimanager-photos-validation');
$router->map('POST', '/admin/PoiManager/decidePhoto',         'PoiManager\\Controllers\\PoiManagerAdminController#decidePhoto', 'admin-poimanager-decide-photo');
$router->map('POST', '/admin/PoiManager/setPhotoAsCover',   'PoiManager\\Controllers\\PoiManagerAdminController#setPhotoAsCover', 'admin-poimanager-photo-set-cover');

// Configuration
$router->map('GET',  '/admin/PoiManager/config',     'PoiManager\\Controllers\\PoiManagerAdminController#config',     'admin-poimanager-config');
$router->map('POST', '/admin/PoiManager/configSave', 'PoiManager\\Controllers\\PoiManagerAdminController#configSave', 'admin-poimanager-config-save');

// =========================================================
// ROUTES PUBLIQUES
// =========================================================

// Dashboard propriétaire : mes fiches
$router->map('GET', '/PoiManager[/?]',         'PoiManager\\Controllers\\PoiManagerPublicController#myPois', 'poimanager-home');
$router->map('GET', '/PoiManager/myPois[/?]',  'PoiManager\\Controllers\\PoiManagerPublicController#myPois', 'poimanager-my-pois');

// Édition propriétaire (accès direct, sans validation)
$router->map('GET',  '/PoiManager/edit/[*:id]', 'PoiManager\\Controllers\\PoiManagerPublicController#ownerEdit', 'poimanager-owner-edit');
$router->map('POST', '/PoiManager/save',         'PoiManager\\Controllers\\PoiManagerPublicController#ownerSave', 'poimanager-owner-save');

// Upload photo propriétaire
$router->map('POST', '/PoiManager/uploadPhoto', 'PoiManager\\Controllers\\PoiManagerPublicController#ownerUploadPhoto', 'poimanager-upload-photo');
$router->map('POST', '/PoiManager/setPhotoAsCover', 'PoiManager\\Controllers\\PoiManagerPublicController#ownerSetPhotoAsCover', 'poimanager-owner-photo-set-cover');

// Proposal : proposer un nouveau POI (membre)
$router->map('GET',  '/PoiManager/propose[/?]', 'PoiManager\\Controllers\\PoiManagerPublicController#proposeForm', 'poimanager-propose');
$router->map('POST', '/PoiManager/propose',     'PoiManager\\Controllers\\PoiManagerPublicController#proposeSave', 'poimanager-propose-save');

// Suggestion : corriger un POI existant (membre)
$router->map('GET',  '/PoiManager/suggest/[*:id]', 'PoiManager\\Controllers\\PoiManagerPublicController#suggestForm', 'poimanager-suggest');
$router->map('POST', '/PoiManager/suggest/save',   'PoiManager\\Controllers\\PoiManagerPublicController#suggestSave', 'poimanager-suggest-save');
$router->map('POST', '/PoiManager/suggest/photo',  'PoiManager\\Controllers\\PoiManagerPublicController#suggestPhotoUpload', 'poimanager-suggest-photo-upload');
