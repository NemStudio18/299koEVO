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
$router->map('GET', '/newsletter[/?]', 'NewsletterController#home', 'newsletter-home');
$router->map('POST', '/newsletter/subscribe', 'NewsletterController#subscribe', 'newsletter-subscribe');
$router->map('GET|POST', '/newsletter/quick-subscribe', 'NewsletterController#quickSubscribe', 'newsletter-quick-subscribe');
$router->map('POST', '/newsletter/profile-unsubscribe', 'NewsletterController#profileUnsubscribe', 'newsletter-profile-unsubscribe');
$router->map('GET', '/newsletter/unsubscribe/[a:token]', 'NewsletterController#unsubscribe', 'newsletter-unsubscribe');
$router->map('POST', '/newsletter/unsubscribe', 'NewsletterController#unsubscribePost', 'newsletter-unsubscribe-post');


// Admin
$router->map('GET', '/admin/newsletter[/?]', 'NewsletterAdminController#list', 'newsletter-admin-list');
$router->map('POST', '/admin/newsletter/delete/[i:id]', 'NewsletterAdminController#delete', 'newsletter-admin-delete');
$router->map('POST', '/admin/newsletter/send', 'NewsletterAdminController#send', 'newsletter-admin-send');
$router->map('GET', '/admin/newsletter/params', 'NewsletterAdminController#params', 'newsletter-admin-params');
$router->map('POST', '/admin/newsletter/save-params', 'NewsletterAdminController#saveParams', 'newsletter-admin-save-params');


