<?php

defined('ROOT') or exit('Access denied!');

/**
 * Plugin SearchEngine - Logique de recherche avancée
 */

// 1. Autoloader
spl_autoload_register(function ($class) {
    if (strpos($class, 'SearchEngine\\') === 0) {
        $relativeClass = substr($class, 13); // Remove "SearchEngine\"

        // Mapping spécifique pour services
        if (strpos($relativeClass, 'Services\\') === 0) {
            $path = str_replace('\\', '/', substr($relativeClass, 9));
            $file = __DIR__ . '/services/' . $path . '.php';
        }
        // Mapping spécifique pour controllers
        elseif (strpos($relativeClass, 'Controllers\\') === 0) {
            $path = str_replace('\\', '/', substr($relativeClass, 12));
            $file = __DIR__ . '/controllers/' . $path . '.php';
        } else {
            // Fallback pour src/
            $path = str_replace('\\', '/', $relativeClass);
            $file = __DIR__ . '/src/' . $path . '.php';
        }

        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// 2. Enregistrement des Hooks
if (class_exists('\SearchEngine\Hooks\SearchEngineHooks')) {
    \SearchEngine\Hooks\SearchEngineHooks::registerHooks();
}

// 3. Chargement des Routes
if (file_exists(__DIR__ . '/param/routes.php')) {
    require_once __DIR__ . '/param/routes.php';
}

// 4. Fonctions Globales (Legacy Support / Install)
function search_engineInstall()
{
    if (class_exists('\SearchEngine\Hooks\SearchEngineHooks')) {
        \SearchEngine\Hooks\SearchEngineHooks::install();
    }
}

function SearchEngineInstall()
{
    search_engineInstall();
}
