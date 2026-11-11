<?php

defined('ROOT') or exit('No direct script access allowed');

$router = \Core\Router\Router::getInstance();

// Public : gestion de l'abonnement
$router->map('GET', '/subscription', 'Subscription\\Controllers\\SubscriptionPublicController#home', 'subscription-home');
$router->map('GET', '/subscription/manage', 'Subscription\\Controllers\\SubscriptionPublicController#manage', 'subscription-manage');
$router->map('POST', '/subscription/subscribe', 'Subscription\\Controllers\\SubscriptionPublicController#subscribe', 'subscription-subscribe');
$router->map('GET', '/subscription/payment-success', 'Subscription\\Controllers\\SubscriptionPublicController#paymentSuccess', 'subscription-payment-success');
$router->map('GET', '/subscription/sepa-mandate', 'Subscription\\Controllers\\SubscriptionPublicController#sepaMandate', 'subscription-sepa-mandate');
$router->map('POST', '/subscription/create-sepa-mandate', 'Subscription\\Controllers\\SubscriptionPublicController#createSepaMandate', 'subscription-create-sepa-mandate');
$router->map('POST', '/subscription/cancel', 'Subscription\\Controllers\\SubscriptionPublicController#cancel', 'subscription-cancel');
$router->map('POST', '/subscription/start-trial', 'Subscription\\Controllers\\SubscriptionPublicController#startTrial', 'subscription-start-trial');
$router->map('GET', '/subscription/payment-methods', 'Subscription\\Controllers\\SubscriptionPublicController#paymentMethods', 'subscription-payment-methods');
$router->map('POST', '/subscription/add-payment-method', 'Subscription\\Controllers\\SubscriptionPublicController#addPaymentMethod', 'subscription-add-payment-method');
$router->map('POST', '/subscription/remove-payment-method/[i:methodId]', 'Subscription\\Controllers\\SubscriptionPublicController#removePaymentMethod', 'subscription-remove-payment-method');
$router->map('POST', '/subscription/add-addon', 'Subscription\\Controllers\\SubscriptionPublicController#addAddon', 'subscription-add-addon');
$router->map('POST', '/subscription/remove-addon/[i:addonId]', 'Subscription\\Controllers\\SubscriptionPublicController#removeAddon', 'subscription-remove-addon');

// Webhooks Mollie
$router->map('POST', '/subscription/webhook', 'Subscription\\Controllers\\SubscriptionPublicController#webhook', 'subscription-webhook');

// Admin : configuration
$router->map('GET', '/admin/subscription[/?]', 'Subscription\\Controllers\\SubscriptionAdminController#dashboard', 'admin-subscription');
$router->map('POST', '/admin/subscription/save-config', 'Subscription\\Controllers\\SubscriptionAdminController#saveConfig', 'admin-subscription-save-config');
$router->map('GET', '/admin/subscription/subscribers', 'Subscription\\Controllers\\SubscriptionAdminController#subscribers', 'admin-subscription-subscribers');
$router->map('GET', '/admin/subscription/payments', 'Subscription\\Controllers\\SubscriptionAdminController#payments', 'admin-subscription-payments');
$router->map('GET', '/admin/subscription/addons', 'Subscription\\Controllers\\SubscriptionAdminController#addons', 'admin-subscription-addons');
$router->map('POST', '/admin/subscription/addon/save', 'Subscription\\Controllers\\SubscriptionAdminController#saveAddon', 'admin-subscription-save-addon');
$router->map('POST', '/admin/subscription/addon/delete/[i:addonId]', 'Subscription\\Controllers\\SubscriptionAdminController#deleteAddon', 'admin-subscription-delete-addon');// Admin : Promo Codes
$router->map('POST', '/admin/subscription/promocode/save', 'Subscription\\Controllers\\SubscriptionAdminController#savePromoCode', 'admin-subscription-save-promocode');
$router->map('GET', '/admin/subscription/promocode/toggle/[i:codeId]', 'Subscription\\Controllers\\SubscriptionAdminController#togglePromoCode', 'admin-subscription-toggle-promocode');
$router->map('GET', '/admin/subscription/promocode/delete/[i:codeId]', 'Subscription\\Controllers\\SubscriptionAdminController#deletePromoCode', 'admin-subscription-delete-promocode');


