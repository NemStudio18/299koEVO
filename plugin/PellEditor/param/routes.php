<?php
/**
 * PellEditor Plugin - Routes registration
 */
$router = \Core\Router\Router::getInstance();
$router->map('GET', '/admin/pelleditor/gallery-api[/?]', 'PellEditor\Controllers\PellEditorController#gallery', 'pelleditor-gallery-api');
$router->map('POST', '/admin/pelleditor/export[/?]', 'PellEditor\Controllers\PellEditorController#export', 'pelleditor-export');

// Workspace-aware routes (optional if handled by TenantManager but safer to explicit map here if needed)
$router->map('POST', '/pelleditor/export[/?]', 'PellEditor\Controllers\PellEditorController#export', 'pelleditor-workspace-export');
$router->map('GET', '/pelleditor/gallery-api[/?]', 'PellEditor\Controllers\PellEditorController#gallery', 'pelleditor-workspace-gallery');
