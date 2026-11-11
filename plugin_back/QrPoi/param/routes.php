<?php

defined('ROOT') or exit('Access denied!');

$router = \Core\Router\Router::getInstance();

// Route publique pour rediriger depuis le QR code
$router->map('GET', '/qr/poi/[i:poi_id][/?]', 'QrPoi\Controllers\QrPoiPublicController#redirect', 'qr-poi-redirect');

// Routes frontend propriétaire
$router->map('GET', '/qr-poi/manage/[i:poi_id][/?]', 'QrPoi\Controllers\QrPoiPublicController#manage', 'qr-poi-manage');
$router->map('GET', '/qr-poi/customize/[i:poi_id][/?]', 'QrPoi\Controllers\QrPoiPublicController#customize', 'qr-poi-customize');
$router->map('POST', '/qr-poi/customize/[i:poi_id][/?]', 'QrPoi\Controllers\QrPoiPublicController#saveCustomization', 'qr-poi-save-customize');
$router->map('GET', '/qr-poi/preview/[i:poi_id][/?]', 'QrPoi\Controllers\QrPoiPublicController#preview', 'qr-poi-preview');
$router->map('POST', '/qr-poi/remove-logo/[i:poi_id][/?]', 'QrPoi\Controllers\QrPoiPublicController#removeLogo', 'qr-poi-remove-logo');
$router->map('POST', '/qr-poi/toggle-display/[i:poi_id][/?]', 'QrPoi\Controllers\QrPoiPublicController#toggleDisplay', 'qr-poi-toggle-display');

// Routes admin
$router->map('GET', '/admin/qrpoi', 'QrPoi\Controllers\QrPoiAdminController#index', 'admin-qrpoi-dashboard');
$router->map('GET', '/admin/qr-poi[/?]', 'QrPoi\Controllers\QrPoiAdminController#index', 'admin-qr-poi-list');
$router->map('GET', '/admin/qr-poi/generate/[i:poi_id][/?]', 'QrPoi\Controllers\QrPoiAdminController#generate', 'admin-qr-poi-generate');
$router->map('GET', '/admin/qr-poi/customize/[i:poi_id][/?]', 'QrPoi\Controllers\QrPoiAdminController#customize', 'admin-qr-poi-customize');
$router->map('POST', '/admin/qr-poi/customize/[i:poi_id][/?]', 'QrPoi\Controllers\QrPoiAdminController#saveCustomization', 'admin-qr-poi-save-customize');
$router->map('GET', '/admin/qr-poi/download/[i:poi_id][/?]', 'QrPoi\Controllers\QrPoiAdminController#download', 'admin-qr-poi-download');
$router->map('GET', '/admin/qr-poi/preview/[i:poi_id][/?]', 'QrPoi\Controllers\QrPoiAdminController#preview', 'admin-qr-poi-preview');
$router->map('POST', '/admin/qr-poi/toggle-display/[i:poi_id][/?]', 'QrPoi\Controllers\QrPoiAdminController#toggleDisplay', 'admin-qr-poi-toggle-display');



