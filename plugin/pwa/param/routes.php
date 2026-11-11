<?php
defined('ROOT') OR exit('No direct script access allowed');

$router = \Core\Router\Router::getInstance();

// Public endpoints for Web Push
$router->map('GET', '/pwa/public-key', 'Pwa\Controllers\PwaController#getPublicKey', 'pwa-public-key');
$router->map('POST', '/pwa/subscribe', 'Pwa\Controllers\PwaController#subscribe', 'pwa-subscribe');
$router->map('POST', '/pwa/unsubscribe', 'Pwa\Controllers\PwaController#unsubscribe', 'pwa-unsubscribe');
$router->map('GET', '/pwa/sw.js', 'Pwa\Controllers\PwaController#serviceWorker', 'pwa-service-worker');
$router->map('GET', '/pwa/manifest', 'Pwa\Controllers\PwaController#manifest', 'pwa-manifest');

// Admin route
$router->map('GET|POST', '/admin/pwa[/?]', 'PwaAdminController#home', 'pwa-admin-home');
$router->map('POST', '/admin/pwa/send-notification', 'PwaAdminController#sendNotification', 'pwa-admin-send-notification');


