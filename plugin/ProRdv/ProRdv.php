<?php

namespace ProRdv {

    use Core\Plugin\Plugin;

    defined('ROOT') or exit('Access denied!');

    // Autoloader PSR-4 pour le namespace ProRdv
    spl_autoload_register(function ($class) {
        if (strpos($class, 'ProRdv\\') === 0) {
            $relative = substr($class, 7);
            if (strpos($relative, 'Controllers\\') === 0) {
                $path = str_replace('\\', '/', substr($relative, 12));
                $file = __DIR__ . '/controllers/' . $path . '.php';
            } elseif (strpos($relative, 'Services\\') === 0) {
                $path = str_replace('\\', '/', substr($relative, 9));
                $file = __DIR__ . '/src/Services/' . $path . '.php';
            } elseif (strpos($relative, 'Hooks\\') === 0) {
                $path = str_replace('\\', '/', substr($relative, 6));
                $file = __DIR__ . '/src/Hooks/' . $path . '.php';
            } else {
                $path = str_replace('\\', '/', $relative);
                $file = __DIR__ . '/src/' . $path . '.php';
            }
            if (file_exists($file)) {
                require_once $file;
            }
        }
    });

    class ProRdv extends Plugin
    {
        /**
         * Installation du plugin : création automatique des tables SQL
         */
        public static function install(): bool
        {
            return \ProRdv\Services\RdvService::getInstance()->installTables();
        }
    }

}

namespace {
    /**
     * Fonction d'installation appelée par PluginsManager::installPlugin
     */
    function ProRdvInstall(): bool
    {
        return \ProRdv\ProRdv::install();
    }

    /**
     * Hook navigation:catalog pour déclarer la page publique /rdv dans le gestionnaire de menus
     */
    function ProRdvNavigationCatalog(array $catalog): array
    {
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('ProRdv');
        if (!$plugin || !$plugin->getConfigVal('activate')) {
            return $catalog;
        }

        $catalog[] = [
            'plugin'      => 'ProRdv',
            'key'         => 'ProRdv:rdv-public-home',
            'route'       => 'rdv-public-home',
            'label'       => 'Rendez-vous',
            'icon'        => 'fa-solid fa-calendar-check',
            'description' => 'Prise de rendez-vous en ligne',
        ];
        return $catalog;
    }
}
