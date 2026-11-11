<?php
defined('ROOT') OR exit('No direct script access allowed');

$router->map('GET', '/admin/audit', 'Audit\Controllers\AuditAdminController#index', 'admin-audit-index');
$router->map('GET', '/admin/audit/scan', 'Audit\Controllers\AuditAdminController#scan', 'admin-audit-scan');
