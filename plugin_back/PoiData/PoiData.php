<?php

defined('ROOT') or exit('Access denied!');

/**
 * Plugin PoiData - Gestion centrale des données POI
 */

// 1. Autoloader
spl_autoload_register(function ($class) {
    if (strpos($class, 'PoiData\\') === 0) {
        $relativeClass = substr($class, 8); // Remove "PoiData\"

        // Mapping spécifique pour Services
        if (strpos($relativeClass, 'Services\\') === 0) {
            $path = str_replace('\\', '/', substr($relativeClass, 9));
            $file = __DIR__ . '/services/' . $path . '.php';
        }
        // Mapping spécifique pour Controllers (sans namespace dans les controllers)
        elseif (strpos($relativeClass, 'Controllers\\') === 0) {
            $path = str_replace('\\', '/', substr($relativeClass, 12));
            $file = __DIR__ . '/controllers/' . $path . '.php';
        } else {
            // Fallback pour src/ (ex: PoiData\Hooks\PoiDataHooks -> src/Hooks/PoiDataHooks.php)
            $path = str_replace('\\', '/', $relativeClass);
            $file = __DIR__ . '/src/' . $path . '.php';
        }

        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// 2. Enregistrement des Hooks
if (class_exists('\PoiData\Hooks\PoiDataHooks')) {
    \PoiData\Hooks\PoiDataHooks::registerHooks();
}

// 3. Chargement des Routes
if (file_exists(__DIR__ . '/param/routes.php')) {
    require_once __DIR__ . '/param/routes.php';
}

// 4. Fonctions Globales (Legacy Support)
function poi_dataInstall()
{
    if (class_exists('\PoiData\Hooks\PoiDataHooks')) {
        \PoiData\Hooks\PoiDataHooks::install();
    }
}

function PoiDataInstall()
{
    poi_dataInstall();
}

function poi_dataUninstall()
{
    if (class_exists('\PoiData\Hooks\PoiDataHooks')) {
        \PoiData\Hooks\PoiDataHooks::uninstall();
    }
}

function PoiDataUninstall()
{
    poi_dataUninstall();
}
