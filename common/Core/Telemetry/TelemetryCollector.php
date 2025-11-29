<?php

namespace Core\Telemetry;

use Core\Core;
use Core\Plugin\PluginsManager;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 *
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

/**
 * Collecte les données de télémétrie selon le niveau choisi
 */
class TelemetryCollector
{
    private Core $core;
    private PluginsManager $pluginsManager;

    public function __construct()
    {
        $this->core = Core::getInstance();
        $this->pluginsManager = PluginsManager::getInstance();
    }

    /**
     * Collecte les données minimales (niveau 0 - installation uniquement)
     * 
     * @return array
     */
    public function collectMinimal(): array
    {
        return [
            'installation_id' => $this->getInstallationId(),
            'cms_version' => VERSION,
            'php_version' => PHP_VERSION,
            'timestamp' => date('c'),
        ];
    }

    /**
     * Collecte les données basiques (niveau 1)
     * 
     * @return array
     */
    public function collectBasic(): array
    {
        $plugins = $this->pluginsManager->getPlugins();
        $pluginsList = [];
        $activePluginsCount = 0;

        foreach ($plugins as $plugin) {
            $pluginsList[] = $plugin->getName();
            if ($plugin->getConfigVal('activate')) {
                $activePluginsCount++;
            }
        }

        $themes = [];
        $themesDir = THEMES;
        if (is_dir($themesDir)) {
            foreach (glob($themesDir . '*', GLOB_ONLYDIR) as $themeDir) {
                $themes[] = basename($themeDir);
            }
        }

        return array_merge($this->collectMinimal(), [
            'plugins_count' => count($pluginsList),
            'plugins_list' => $pluginsList,
            'active_plugins_count' => $activePluginsCount,
            'themes_count' => count($themes),
        ]);
    }

    /**
     * Collecte les données étendues (niveau 2)
     * 
     * @return array
     */
    public function collectExtended(): array
    {
        $data = $this->collectBasic();

        // Compter les pages
        $pagesCount = 0;
        $pagesDir = DATA_CORE_PAGE;
        if (is_dir($pagesDir)) {
            $pagesFiles = glob($pagesDir . '*.json');
            $pagesCount = $pagesFiles ? count($pagesFiles) : 0;
        }

        // Compter les utilisateurs
        $usersCount = 0;
        $usersFile = DATA_CORE_AUTH . 'users.json';
        if (file_exists($usersFile)) {
            $users = json_decode(file_get_contents($usersFile), true);
            $usersCount = is_array($users) ? count($users) : 0;
        }

        return array_merge($data, [
            'site_url' => $this->core->getConfigVal('siteUrl'),
            'site_name' => $this->core->getConfigVal('siteName'),
            'default_language' => $this->core->getConfigVal('siteLang'),
            'default_theme' => $this->core->getConfigVal('theme'),
            'default_plugin' => $this->core->getConfigVal('defaultPlugin'),
            'cache_enabled' => (bool) $this->core->getConfigVal('cache_enabled'),
            'users_count' => $usersCount,
            'pages_count' => $pagesCount,
            'last_update' => $this->getLastUpdateTimestamp(),
        ]);
    }

    /**
     * Récupère l'installation_id depuis la config
     * 
     * @return string
     */
    private function getInstallationId(): string
    {
        $config = $this->core->getConfig();
        return $config['telemetry_installation_id'] ?? '';
    }

    /**
     * Récupère le timestamp de la dernière mise à jour du CMS
     * 
     * @return string|null
     */
    private function getLastUpdateTimestamp(): ?string
    {
        $config = $this->core->getConfig();
        if (isset($config['versionCMS'])) {
            // Si la version a changé, c'est qu'il y a eu une mise à jour
            // On pourrait stocker une date de mise à jour dans le futur
            return date('c');
        }
        return null;
    }
}

