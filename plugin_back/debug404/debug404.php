<?php

defined('ROOT') or exit('Access denied!');

/**
 * Plugin Debug 404 - Logger simple pour debug
 */

function debug404Install(): void
{
    $logDir = DATA_PLUGIN . 'debug404' . DS;
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . 'logs.json';
    if (!file_exists($logFile)) {
        @file_put_contents($logFile, json_encode([], JSON_PRETTY_PRINT));
    }
}

function debug404OnBefore404(): void
{
    $logDir = DATA_PLUGIN . 'debug404' . DS;
    $logFile = $logDir . 'logs.json';
    
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $referer = $_SERVER['HTTP_REFERER'] ?? 'Direct';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $time = date('Y-m-d H:i:s');
    
    // Essayer de deviner le plugin incriminé
    $parts = explode('/', trim($uri, '/'));
    if ($parts[0] === 'index.php') {
        array_shift($parts);
    }
    $guessPlugin = $parts[0] ?? 'Core/Unknown';
    
    // Si c'est un plugin connu mais que ça fait 404
    $pluginsManager = \Core\Plugin\PluginsManager::getInstance();
    $plugin = $pluginsManager->getPlugin($guessPlugin);
    if ($plugin) {
        $guessPlugin = $guessPlugin . ' (Plugin exists, route missing?)';
    }
    
    $logs = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];
    if (!is_array($logs)) {
        $logs = [];
    }
    
    $logs[] = [
        'time' => $time,
        'uri' => $uri,
        'referer' => $referer,
        'plugin' => $guessPlugin,
        'ip' => $ip,
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET'
    ];
    
    // Garder seulement les 100 derniers
    if (count($logs) > 100) {
        array_shift($logs);
    }
    
    @file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));
}

function debug404AdminMenu(array $menu): array
{
    $menu[] = [
        'label' => 'Debug 404',
        'url' => \Core\Router\Router::getInstance()->generate('admin-debug404-list'),
        'icon' => 'fa-solid fa-bug',
        'position' => 999
    ];
    return $menu;
}
