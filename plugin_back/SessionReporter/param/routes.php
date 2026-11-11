<?php
/** @var \Core\Router\Router $router */
$router = \Core\Router\Router::getInstance();

$router->map('POST', '/sessionreporter/log-js-error', 'SessionReporter\Controllers\ReporterPublicController#logJsError', 'sessionreporter-log-js-error');
$router->map('POST', '/sessionreporter/submit-report', 'SessionReporter\Controllers\ReporterPublicController#submitReport', 'sessionreporter-submit-report');
$router->map('GET', '/admin/sessionreporter[/?]', 'SessionReporter\Controllers\ReporterAdminController#index', 'admin-sessionreporter');
$router->map('POST', '/admin/sessionreporter/generate-code', 'SessionReporter\Controllers\ReporterAdminController#generateCode', 'admin-sessionreporter-generate-code');
$router->map('GET', '/admin/sessionreporter/set-debug-token', 'SessionReporter\Controllers\ReporterAdminController#setDebugToken', 'admin-sessionreporter-set-debug-token');
