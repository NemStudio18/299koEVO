<?php

$router = \Core\Router\Router::getInstance();
$router->map('GET', '/demo/[?]', 'Demo\Controllers\DemoPublicController#activate', 'demo-index');
$router->map('GET', '/demo/activate[/?]', 'Demo\Controllers\DemoPublicController#activate', 'demo-activate');
$router->map('GET', '/demo/stop[/?]', 'Demo\Controllers\DemoPublicController#stop', 'demo-stop');
