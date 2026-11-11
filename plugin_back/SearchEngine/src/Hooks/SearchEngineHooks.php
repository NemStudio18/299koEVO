<?php

namespace SearchEngine\Hooks;

use Core\Core;
use Core\Logger;

defined('ROOT') or exit('Access denied!');

class SearchEngineHooks
{
    public static function registerHooks(): void
    {
        // Hooks are now declared in param/hooks.json
    }

    /**
     * Hook pour générer le slug d'un POI
     */
    public static function generateUrlName(array $data): string
    {
        $name = $data['name'] ?? '';
        $id = $data['id'] ?? 0;
        
        $service = new \SearchEngine\Services\Search\SearchService();
        return $service->generateUrlName($name, $id);
    }

    /**
     * Hook pour compter les POIs
     */
    public static function countTotal(array $aggloCities): int
    {
        $service = new \SearchEngine\Services\Search\SearchService();
        return $service->countTotalPois($aggloCities);
    }

    public static function install(): void
    {
        $core = Core::getInstance();
        $core->getLogger()->log(Logger::LEVEL_INFO, "SearchEngine: Installation started");

        $dataDir = DATA_PLUGIN . 'SearchEngine';
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        $configFile = $dataDir . '/config.json';
        if (!file_exists($configFile)) {
            $sourceConfig = dirname(__DIR__, 2) . '/param/config.json';
            if (file_exists($sourceConfig)) {
                copy($sourceConfig, $configFile);
            }
        }

        $core->getLogger()->log(Logger::LEVEL_INFO, "SearchEngine: Installation completed");
    }
}
