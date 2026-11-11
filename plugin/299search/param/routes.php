<?php
defined('ROOT') or exit('No direct script access allowed');

$router = \Core\Router\Router::getInstance();
$router->map('GET|HEAD', '/search[/?]', 'Search299\\Controllers\\SearchController#index', 'search299-index');
$router->map('GET|HEAD', '/recherche-site[/?]', 'Search299\\Controllers\\SearchController#index', 'search299-alias');
