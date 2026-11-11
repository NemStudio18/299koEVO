<?php

namespace ShortUrl\Hooks;

defined('ROOT') or exit('Access denied');

class ShortUrlHooks
{
    public static function install()
    {
        // Pas d'installation BDD requise
        return true;
    }

    public static function registerHooks()
    {
        // Hooks are now declared in param/hooks.json
    }

    /**
     * Hook before404 : Gestion des URLs courtes de type /nom-du-lieu ou /nom-du-lieu-id
     */
    public static function onBefore404(): void
    {
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('ShortUrl');
        if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
            return;
        }

        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        // Retirer la query string
        if (($pos = strpos($requestUri, '?')) !== false) {
            $requestUri = substr($requestUri, 0, $pos);
        }

        $requestUri = trim($requestUri, '/');
        if (empty($requestUri)) return;

        // Sécurité : ne pas intercepter les routes système connues ou les fichiers
        $systemPrefixes = ['SearchUi', 'admin', 'users', 'api', 'assets', 'plugin', 'data', 'img', 'js', 'css', 'blog'];
        $firstSegment = explode('/', $requestUri)[0];
        if (in_array($firstSegment, $systemPrefixes)) return;

        // Ne pas intercepter les extensions de fichiers
        if (preg_match('/\.(jpg|jpeg|png|gif|css|js|ico|pdf|xml|txt|webmanifest)$/i', $requestUri)) return;

        // On essaie de trouver le POI via le SearchService (qui utilise le PoiRepository)
        // Note: Search\Services\Search\SearchService est le service unifié utilisé par SearchUi
        if (class_exists('\SearchEngine\Services\Search\SearchService')) {
            $searchService = new \SearchEngine\Services\Search\SearchService();
            $poi = $searchService->fetchByName($requestUri);

            if ($poi && !empty($poi['id'])) {
                // On délègue au RedirectController
                $controller = new \ShortUrl\Controllers\RedirectController();
                $controller->go($poi['id']);
                exit;
            }
        }
    }
}
