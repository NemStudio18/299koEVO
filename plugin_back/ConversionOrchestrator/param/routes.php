<?php
/** @var Core\Router\Router $router */
$router = \Core\Router\Router::getInstance();

// Tunnel: Pending Action (Already exists in logic, but let's make it formal)
$router->map('GET', '/ConversionOrchestrator/pending/[a:action]/[i:poi_id]', 'ConversionOrchestrator\ConversionOrchestratorController#setPendingAction', 'conversion-orchestrator-pending-action');

// Admin: Reset Ownership
$router->map('POST', '/ConversionOrchestrator/admin/reset-ownership', 'ConversionOrchestrator\ConversionOrchestratorController#resetOwnership', 'conversion-orchestrator-admin-reset-ownership');
