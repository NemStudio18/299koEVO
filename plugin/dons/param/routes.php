<?php

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('No direct script access allowed');

$router = \Core\Router\Router::getInstance();

// Public
$router->map('GET', '/dons[/?]', 'Dons\Controllers\DonController#home', 'dons-home');
$router->map('POST', '/dons/paypal/create', 'Dons\Controllers\DonController#createPayPalPayment', 'dons-paypal-create');
$router->map('POST', '/dons/paypal/capture', 'Dons\Controllers\DonController#capturePayPalPayment', 'dons-paypal-capture');
$router->map('POST', '/dons/stripe/create', 'Dons\Controllers\DonController#createStripePayment', 'dons-stripe-create');
$router->map('GET', '/dons/stripe/success', 'Dons\Controllers\DonController#stripeSuccess', 'dons-stripe-success');
$router->map('GET', '/dons/stripe/cancel', 'Dons\Controllers\DonController#stripeCancel', 'dons-stripe-cancel');

// Admin
$router->map('GET', '/admin/dons[/?]', 'Dons\Controllers\DonAdminController#list', 'dons-admin-list');
$router->map('GET', '/admin/dons/edit', 'Dons\Controllers\DonAdminController#edit', 'dons-admin-edit');
$router->map('POST', '/admin/dons/save', 'Dons\Controllers\DonAdminController#save', 'dons-admin-save');
$router->map('GET', '/admin/dons/stats', 'Dons\Controllers\DonAdminController#stats', 'dons-admin-stats');
$router->map('GET', '/admin/dons/chart-data', 'Dons\Controllers\DonAdminController#chartData', 'dons-admin-chart-data');
$router->map('GET', '/admin/dons/params', 'Dons\Controllers\DonAdminController#params', 'dons-admin-params');
$router->map('POST', '/admin/dons/save-params', 'Dons\Controllers\DonAdminController#saveParams', 'dons-admin-save-params');
