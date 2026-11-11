<?php

defined('ROOT') or exit('Access denied!');

/**
 * Plugin SearchMaps - Intégration Leaflet
 */

// 1. Autoloader
spl_autoload_register(function ($class) {
    if (strpos($class, 'SearchMaps\\') === 0) {
        $relativeClass = substr($class, 11);

        if (strpos($relativeClass, 'Controllers\\') === 0) {
            $path = str_replace('\\', '/', substr($relativeClass, 12));
            $file = __DIR__ . '/controllers/' . $path . '.php';
        } else {
            $path = str_replace('\\', '/', $relativeClass);
            $file = __DIR__ . '/src/' . $path . '.php';
        }

        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// 2. Enregistrement des Hooks
if (class_exists('\SearchMaps\Hooks\SearchMapsHooks')) {
    \SearchMaps\Hooks\SearchMapsHooks::registerHooks();
}

// 3. Chargement des Routes
if (file_exists(__DIR__ . '/param/routes.php')) {
    require_once __DIR__ . '/param/routes.php';
}

function search_mapsInstall()
{
    if (class_exists('\SearchMaps\Hooks\SearchMapsHooks')) {
        \SearchMaps\Hooks\SearchMapsHooks::install();
    }
}

function SearchMapsInstall()
{
    search_mapsInstall();
}


function searchMapsEndFrontHead($param = ''): string
{
    if (class_exists('\SearchMaps\Hooks\SearchMapsHooks')) {
        return \SearchMaps\Hooks\SearchMapsHooks::onEndFrontHead($param);
    }
    return (string)$param;
}
