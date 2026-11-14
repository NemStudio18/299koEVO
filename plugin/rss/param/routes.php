<?php

defined('ROOT') or exit('No direct script access allowed');

$router = \Core\Router\Router::getInstance();

// Public RSS Feeds
$router->map('GET', '/rss.xml', 'Rss\\Controllers\\RssPublicController#feed', 'rss-feed-xml');
$router->map('GET', '/feed[/?]', 'Rss\\Controllers\\RssPublicController#feed', 'rss-feed-alias');
$router->map('GET', '/rss/[*:channel][/?]', 'Rss\\Controllers\\RssPublicController#channel', 'rss-feed-channel');

// Admin Configuration
$router->map('GET', '/admin/rss[/?]', 'Rss\\Controllers\\RssAdminController#home', 'admin-rss-home');
$router->map('POST', '/admin/rss/save[/?]', 'Rss\\Controllers\\RssAdminController#save', 'admin-rss-save');

