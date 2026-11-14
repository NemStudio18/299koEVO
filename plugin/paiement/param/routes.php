<?php
defined('ROOT') OR exit('Access denied!');

$router = \Core\Router\Router::getInstance();

$router->map('GET',  '/paiement[/?]',                               'CheckoutController#home',          'paiement-home');
$router->map('GET',  '/paiement/checkout[/?]',                      'CheckoutController#checkout',      'paiement-checkout');
$router->map('GET',  '/paiement/success[/?]',                       'CheckoutController#success',       'paiement-success');
$router->map('GET',  '/paiement/cancel[/?]',                        'CheckoutController#cancel',        'paiement-cancel');
$router->map('POST', '/paiement/webhook/stripe',                    'WebhookController#stripe',         'paiement-webhook-stripe');
$router->map('POST', '/paiement/webhook/paypal',                    'WebhookController#paypal',         'paiement-webhook-paypal');
$router->map('POST', '/paiement/webhook/mollie',                    'WebhookController#mollie',         'paiement-webhook-mollie');

$router->map('GET',  '/admin/paiement[/?]',                         'PaiementAdminController#home',         'paiement-admin-home');
$router->map('POST', '/admin/paiement/saveConfig',                  'PaiementAdminController#saveConfig',   'paiement-admin-saveconfig');
