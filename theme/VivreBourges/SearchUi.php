<?php

defined('ROOT') or exit('Access denied!');

/**
 * Plugin SearchUI - Interface Utilisateur
 */

// 1. Autoloader
spl_autoload_register(function ($class) {
    if (strpos($class, 'SearchUI\\') === 0) {
        $relativeClass = substr($class, 9);

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
if (class_exists('\SearchUI\Hooks\SearchUIHooks')) {
    \SearchUI\Hooks\SearchUIHooks::registerHooks();
}

// 3. Chargement des Routes
if (file_exists(__DIR__ . '/param/routes.php')) {
    require_once __DIR__ . '/param/routes.php';
}

// 4. Fonctions Globales (Legacy)
function search_uiInstall()
{
    if (class_exists('\SearchUI\Hooks\SearchUIHooks')) {
        \SearchUI\Hooks\SearchUIHooks::install();
    }
}

function SearchUiInstall()
{
    search_uiInstall();
}
