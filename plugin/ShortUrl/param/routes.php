<?php

defined('ROOT') or exit('No direct script access allowed');

$router = \Core\Router\Router::getInstance();

// Route courte : /go/123 -> Redirection vers la fiche POI
$router->map('GET', '/go/[i:id][/?]', 'ShortUrl\\Controllers\\RedirectController#go', 'short-url-go');

// Route option B : /com-bourges-nom-id
$router->map('GET', '/com-[**:slug][/?]', 'ShortUrl\\Controllers\\RedirectController#com', 'short-url-com');
