<?php

require_once __DIR__ . '/services/LibUpdater.php';
require_once __DIR__ . '/controllers/LibManagerController.php';

function libManagerInstall(): void {
    $logDir = DATA . 'libmanager/';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
}

function libManagerCoreInit(): void {
    \Core\Lang::loadLanguageFile(PLUGINS . 'LibManager' . DS . 'langs' . DS);
}
\Core\Core::getInstance()->addHook('core:init', 'libManagerCoreInit');
\Core\Core::getInstance()->addHook('core:navigation', 'libManagerNavigation');

function libManagerNavigation(): void {
    if (!defined('ADMIN_MODE') || !ADMIN_MODE) return;
    if (class_exists('\TenantManager\Services\TenantContext') && \TenantManager\Services\TenantContext::has()) return;

    $nav    = \Core\Navigation\NavigationService::getInstance();
    $router = \Core\Router\Router::getInstance();

    $nav->register('plugin.libmanager.main', [
        'label'      => \Core\Lang::get('libmanager.name'),
        'target'     => $router->generate('libmanager-dashboard'),
        'icon'       => 'fa-solid fa-cubes',
        'position'   => 90,
        'source'     => 'plugin:LibManager',
        'visibility' => ['admin' => true, 'logged' => true],
    ]);
}
