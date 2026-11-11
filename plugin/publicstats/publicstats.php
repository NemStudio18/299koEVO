<?php
/**
 * Plugin Name: Statistiques Publiques
 * Description: Affiche une page publique avec les statistiques de la communauté (membres, quartiers, sondages, etc.).
 * Version: 1.0.0
 * Author: 299Ko
 */

defined('ROOT') or exit('Access denied!');

// Autoloader pour le namespace PublicStats
spl_autoload_register(function ($class) {
    if (strpos($class, 'PublicStats\\') === 0) {
        $path = str_replace('\\', '/', substr($class, 12));
        $file = __DIR__ . '/src/' . $path . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Enregistrer les hooks
\PublicStats\UserStatsProvider::registerHooks();

class PublicStats
{
    public function install()
    {
        return true;
    }
}

use Core\Core;

/**
 * Hook: Charge les styles CSS de la page statistiques
 */
function publicstatsAddStyles()
{
    echo '<link rel="stylesheet" href="' . \Utils\Util::urlBuild('plugin/publicstats/template/public.css') . '" type="text/css"/>';
}

function publicstatsIndexUrl($params = ''): string
{
    try {
        return \Core\Router\Router::getInstance()->generate('publicstats-index');
    } catch (\Exception $e) {
        return '#';
    }
}
