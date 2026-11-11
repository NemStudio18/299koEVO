<?php
defined('ROOT') or exit('No direct script access allowed');

$router = \Core\Router\Router::getInstance();

// Public : Tableau de bord Business
$router->map('GET', '/BusinessDashboard', 'BusinessDashboard\\Controllers\\BusinessDashboardController#index', 'business-dashboard-index');
