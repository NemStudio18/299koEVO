<?php

namespace Core\Extensions;

use Core\Extensions\MarketPlaceManager;
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
            $urls[] = Util::urlBuild($path, true);
        }
        return $urls;
    }

    public function getAdminJsUrls(): array
    {
        return [];
    }

    public function getAdminNavigationEntries(): array
    {
        $entries = [];
        foreach ($this->adminModules as $module) {
            $entries[] = [
                'name' => $module['name'],
                'icon' => $module['icon'],
                'label' => Lang::get($module['label']),
            ];
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
        $router->map('GET', '/admin/pluginsmanager/[a:plugin]/[a:token]', 'Core\Extensions\Controllers\PluginsManagerController#maintenance', 'pluginsmanager-maintenance');

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


