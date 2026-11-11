<?php

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 *
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('Access denied!');

$router = \Core\Router\Router::getInstance();

$router->map('GET', '/admin/telemetryadmin[/?]', 'TelemetryAdminController#dashboard', 'telemetryadmin-dashboard');
$router->map('GET', '/admin/telemetryadmin/reports[/?]', 'TelemetryAdminController#reports', 'telemetryadmin-reports');
$router->map('GET', '/admin/telemetryadmin/reports/[a:reportId]', 'TelemetryAdminController#reportDetail', 'telemetryadmin-report-detail');
$router->map('POST', '/admin/telemetryadmin/reports/[a:reportId]/status', 'TelemetryAdminController#updateStatus', 'telemetryadmin-report-status');
$router->map('GET', '/admin/telemetryadmin/installations[/?]', 'TelemetryAdminController#installations', 'telemetryadmin-installations');
$router->map('GET', '/admin/telemetryadmin/installations/[*:installationId]/plugins', 'TelemetryAdminController#installationPlugins', 'telemetryadmin-installation-plugins');
$router->map('GET', '/admin/telemetryadmin/sync-logs[/?]', 'TelemetryAdminController#syncLogs', 'telemetryadmin-sync-logs');

