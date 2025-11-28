<?php

namespace Core\Extensions;

use Utils\Util;

defined('ROOT') or exit('Access denied!');

class LegacyPluginsMigrator
{
    /**
     * Attempt to reinstall plugins via marketplace.
     *
     * @param array $slugs Plugin slugs to install
     * @param MarketPlaceManager $marketManager
     * @param bool $cleanupLegacyDirs
     * @return array{installed: array, failed: array<string,string>}
     */
    public static function install(array $slugs, MarketPlaceManager $marketManager, bool $cleanupLegacyDirs = false): array
    {
        $results = [
            'installed' => [],
            'failed' => [],
        ];

        $slugs = array_unique(array_filter($slugs));
        foreach ($slugs as $slug) {
            $pluginData = $marketManager->getPlugin($slug);
            if (!$pluginData) {
                $results['failed'][$slug] = 'not_found';
                continue;
            }

            $ressource = new MarketPlaceRessource(MarketPlaceRessource::TYPE_PLUGIN, $pluginData);
            if (!$ressource->isInstallable) {
                $results['failed'][$slug] = 'requirements';
                continue;
            }

            if (!$marketManager->installRessource($ressource)) {
                $results['failed'][$slug] = 'install_error';
                continue;
            }

            $results['installed'][] = $slug;
            if ($cleanupLegacyDirs) {
                $legacyDir = DATA_PLUGIN . $slug;
                if (is_dir($legacyDir)) {
                    Util::delTree($legacyDir);
                }
            }
        }

        return $results;
    }
}

