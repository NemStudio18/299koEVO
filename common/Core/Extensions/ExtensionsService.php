<?php

namespace Core\Extensions;

use Core\Extensions\MarketPlaceManager;
use Core\Plugin\PluginsManager;
use Core\Storage\Cache;
use Utils\Util;
use Core\Lang;
use Core\Router\Router;
use Core\Core;

defined('ROOT') or exit('Access denied!');

class ExtensionsService
{
    private string $dir;
    private string $marketplaceConfigFile;
    private string $legacyPluginsFile;
    private bool $routesRegistered = false;
    private array $adminCss = [
        'templates/marketplace/admin.css',
    ];
    private array $adminJs = [
        'templates/marketplace/admin.js',
    ];
    private array $adminModules = [
        [
            'name' => 'pluginsmanager',
            'label' => 'pluginsmanager.name',
            'icon' => 'fa-solid fa-plug',
        ],
        [
            'name' => 'marketplace',
            'label' => 'marketplace.name',
            'icon' => 'fa-solid fa-store',
        ],
    ];

    private ?MarketPlaceManager $marketplaceManager = null;

    public function __construct()
    {
        $this->dir = DATA_CORE_EXTENSIONS;
        $this->marketplaceConfigFile = $this->dir . 'marketplace.json';
        $this->legacyPluginsFile = $this->dir . 'legacy_plugins.json';

        $this->bootstrap();
    }

    public function marketplace(): MarketPlaceManager
    {
        if ($this->marketplaceManager === null) {
            $this->marketplaceManager = new MarketPlaceManager($this);
        }

        return $this->marketplaceManager;
    }

    public function getMarketplaceConfig(): array
    {
        return Util::readJsonFile($this->marketplaceConfigFile, true) ?? [];
    }

    public function saveMarketplaceConfig(array $config): void
    {
        Util::writeJsonFile($this->marketplaceConfigFile, $config);
    }

    public function getMarketplaceConfigFile(): string
    {
        return $this->marketplaceConfigFile;
    }

    public function getLegacyPluginsFile(): string
    {
        return $this->legacyPluginsFile;
    }

    public function getPendingLegacyPlugins(): array
    {
        if (!file_exists($this->legacyPluginsFile)) {
            return [];
        }
        $data = Util::readJsonFile($this->legacyPluginsFile, true);
        if (!is_array($data)) {
            return [];
        }
        $plugins = $data['plugins'] ?? [];
        if (!is_array($plugins)) {
            return [];
        }
        return array_values(array_unique(array_filter($plugins)));
    }

    public function savePendingLegacyPlugins(array $plugins): void
    {
        $plugins = array_values(array_unique(array_filter($plugins)));
        if (empty($plugins)) {
            if (file_exists($this->legacyPluginsFile)) {
                @unlink($this->legacyPluginsFile);
            }
            return;
        }
        Util::writeJsonFile($this->legacyPluginsFile, ['plugins' => $plugins]);
    }

    public function getAdminCssUrls(): array
    {
        $urls = [];
        foreach ($this->adminCss as $path) {
            $abs = ADMIN_PATH . ltrim($path, '/');
            $v = file_exists($abs) ? ('?v=' . filemtime($abs)) : '';
            $urls[] = Util::urlBuild(ltrim($path, '/'), true) . $v;
        }
        return $urls;
    }

    public function getAdminJsUrls(): array
    {
        $urls = [];
        foreach ($this->adminJs as $path) {
            $abs = ADMIN_PATH . ltrim($path, '/');
            $v = file_exists($abs) ? ('?v=' . filemtime($abs)) : '';
            $urls[] = Util::urlBuild(ltrim($path, '/'), true) . $v;
        }
        return $urls;
    }

    /**
     * Calcule et met en cache (5 min) la synthèse des mises à jour disponibles pour les plugins installés & actifs,
     * ainsi que pour les thèmes.
     */
    public function getAvailableUpdates(bool $forceRefresh = false): array
    {
        $cache = new Cache();
        $cacheKey = 'marketplace-updates-summary';
        if (!$forceRefresh) {
            $cached = $cache->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $summary = [
            'plugins_count' => 0,
            'themes_count' => 0,
            'total' => 0,
            'plugins' => [],
            'themes' => [],
        ];

        try {
            $marketplace = $this->marketplace();
            $plugins = $marketplace->getPlugins();
            $themes = $marketplace->getThemes();
            $pluginsManager = PluginsManager::getInstance();

            if (is_array($plugins)) {
                foreach ($plugins as $p) {
                    $r = new MarketPlaceRessource(MarketPlaceRessource::TYPE_PLUGIN, $p);
                    if ($r->updateNeeded()) {
                        $pluginSlug = strtolower((string)$p->slug);
                        $installed = $pluginsManager->getPlugin($pluginSlug);
                        // Ne notifier que pour les plugins installés ET actifs
                        if ($installed && $installed->getConfigVal('activate')) {
                            $summary['plugins'][$pluginSlug] = true;
                            $summary['plugins_count']++;
                        }
                    }
                }
            }

            if (is_array($themes)) {
                foreach ($themes as $t) {
                    $r = new MarketPlaceRessource(MarketPlaceRessource::TYPE_THEME, $t);
                    if ($r->updateNeeded()) {
                        $themeSlug = strtolower((string)$t->slug);
                        $summary['themes'][$themeSlug] = true;
                        $summary['themes_count']++;
                    }
                }
            }

            $summary['total'] = $summary['plugins_count'] + $summary['themes_count'];
            $cache->set($cacheKey, $summary, 300);
        } catch (\Throwable $e) {}

        return $summary;
    }

    public function getAdminNavigationEntries(): array
    {
        $entries = [];
        $updates = $this->getAvailableUpdates();

        foreach ($this->adminModules as $module) {
            $entry = [
                'name' => $module['name'],
                'icon' => $module['icon'],
                'label' => Lang::get($module['label']),
            ];
            if ($module['name'] === 'marketplace' && $updates['plugins_count'] > 0) {
                $entry['badge'] = 'MAJ plugins ' . $updates['plugins_count'];
            } elseif ($module['name'] === 'marketplace' && $updates['total'] > 0) {
                $entry['badge'] = 'MAJ ' . $updates['total'];
            }
            $entries[] = $entry;
        }
        return $entries;
    }

    public function isCoreAdminModule(string $name): bool
    {
        foreach ($this->adminModules as $module) {
            if ($module['name'] === $name) {
                return true;
            }
        }
        return false;
    }

    public function registerRoutes(Router $router): void
    {
        if ($this->routesRegistered) {
            return;
        }
        $this->routesRegistered = true;

        $router->map('GET', '/admin/pluginsmanager[/?]', 'Core\Extensions\Controllers\PluginsManagerController#list', 'pluginsmanager-list');
        $router->map('POST', '/admin/pluginsmanager/save', 'Core\Extensions\Controllers\PluginsManagerController#save', 'pluginsmanager-save');
        $router->map('GET', '/admin/pluginsmanager/[*:plugin]/[*:token]', 'Core\Extensions\Controllers\PluginsManagerController#maintenance', 'pluginsmanager-maintenance');

        $router->map('GET', '/admin/marketplace[/?]', 'Core\Extensions\Controllers\AdminMarketplaceController#index', 'admin-marketplace');
        $router->map('GET', '/admin/marketplace/plugins[/?]', 'Core\Extensions\Controllers\PluginsMarketController#index', 'marketplace-plugins');
        $router->map('GET', '/admin/marketplace/themes[/?]', 'Core\Extensions\Controllers\ThemesMarketController#index', 'marketplace-themes');
        $router->map('GET', '/admin/marketplace/refresh/[a:token][/?]', 'Core\Extensions\Controllers\AdminMarketplaceController#refreshCache', 'marketplace-refresh-cache');
        $router->map('GET', '/admin/marketplace/install/[a:type]/[a:slug]/[a:token][/?]', 'Core\Extensions\Controllers\AdminMarketplaceController#installRelease', 'marketplace-install-release');
        $router->map('GET', '/admin/marketplace/uninstall/[a:type]/[a:slug]/[a:token][/?]', 'Core\Extensions\Controllers\AdminMarketplaceController#uninstallRessource', 'marketplace-uninstall-ressource');
        $router->map('POST', '/admin/marketplace/migrate-legacy', 'Core\Extensions\Controllers\AdminMarketplaceController#migrateLegacyPlugins', 'marketplace-migrate-legacy');
    }

    public function registerAdminHooks(): void
    {
        $core = Core::getInstance();
        $core->addHook('adminContent', function ($content) {
            return $content;
        });
        $core->addHook('adminHead', function () {
            foreach ($this->getAdminCssUrls() as $url) {
                echo '<link href="' . $url . '" rel="stylesheet" type="text/css" />';
            }
            foreach ($this->getAdminJsUrls() as $url) {
                echo '<script type="text/javascript" src="' . $url . '"></script>';
            }
        });
    }

    private function bootstrap(): void
    {
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0755, true);
        }

        if (!file_exists($this->marketplaceConfigFile)) {
            $legacy = DATA_PLUGIN . 'marketplace' . DS . 'marketplace.json';
            if (file_exists($legacy)) {
                $payload = Util::readJsonFile($legacy, true);
                Util::writeJsonFile($this->marketplaceConfigFile, $payload);
            } else {
                Util::writeJsonFile($this->marketplaceConfigFile, [
                    'siteID' => uniqid('299ko-', true)
                ]);
            }
        }
    }
}


