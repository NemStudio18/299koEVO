<?php

defined('ROOT') or exit('Access denied!');

/**
 * Plugin Short URL
 * Gère les redirections courtes pour les POIs (ex: /go/123 -> /poi/slug)
 */

// 1. Autoloader PSR-4 style
spl_autoload_register(function ($class) {
    if (strpos($class, 'ShortUrl\\') === 0) {
        $relativeClass = substr($class, 9);
        $path = str_replace('\\', '/', $relativeClass);
        $file = __DIR__ . '/src/' . $path . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// 2. Enregistrement des Hooks
if (class_exists('\ShortUrl\Hooks\ShortUrlHooks')) {
    \ShortUrl\Hooks\ShortUrlHooks::registerHooks();
}

// 3. Chargement des Routes
if (file_exists(__DIR__ . '/param/routes.php')) {
    require_once __DIR__ . '/param/routes.php';
}

// 4. Fonctions d'installation
function short_urlInstall()
{
    // Pas d'installation complexe requise pour l'instant
    return true;
}

function ShortUrlInstall()
{
    return short_urlInstall();
}
