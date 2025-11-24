<?php

namespace Core\Settings;

use Core\Settings\UpdaterManager;
use Core\Settings\ConfigManagerBackupsManager;
use Core\Settings\ConfigManagerBackup;
use Core\Lang;
use Core\Router\Router;
use Core\Auth\UsersManager;
use Utils\Show;
use Utils\Util;
use Core\Core;

/**
 * Provides core-level configuration utilities (updates, backups, warnings).
 */
defined('ROOT') or exit('Access denied!');

class SettingsService
{
    private string $settingsDir;
    private string $legacyDir;
    private string $cacheFile;
    private bool $routesRegistered = false;
    private bool $hooksRegistered = false;
    private array $adminCss = [
        'templates/configmanager/admin.css',
    ];
    private array $adminJs = [
        'templates/configmanager/admin.js',
    ];
    private array $adminModules = [
        [
            'name' => 'configmanager',
            'label' => 'configmanager.name',
            'icon' => 'fa-solid fa-gear',
        ],
    ];

    public function __construct()
    {
        $this->settingsDir = DATA_CORE_SETTINGS;
        $this->legacyDir = DATA_PLUGIN . 'configmanager' . DS;
        $this->cacheFile = $this->settingsDir . 'cache.json';

        $this->bootstrapStorage();
        $this->registerHooks();
    }

    /**
     * Display warning to delete install.php when present.
     */
    public function displayInstallWarning(): void
    {
        if (file_exists(ROOT . 'install.php')) {
            echo "<div class='msg warning'>
                <p>" . Lang::get("configmanager-delete-install-msg") . "</p>
                <div style='text-align:center'><a class='button' href='" .
                Router::getInstance()->generate('configmanager-delete-install', ['token' => UsersManager::getCurrentUser()->token]) .
                "'>" . Lang::get("configmanager-delete-install") . "</a></div>
                <a href='javascript:' class='msg-button-close'><i class='fa-solid fa-xmark'></i></a></div>";
        }
    }

    /**
     * Removes install.php if possible.
     */
    public function deleteInstallFile(): bool
    {
        if (!file_exists(ROOT . 'install.php')) {
            return true;
        }

        return @unlink(ROOT . 'install.php');
    }

    /**
     * Checks for new versions using cache (daily).
     */
    public function checkNewVersion(): void
    {
        $cachedInfos = Util::readJsonFile($this->cacheFile);
        if ($cachedInfos !== false) {
            $lastVersion = $cachedInfos['lastVersion'];
            if ($lastVersion === VERSION) {
                $lastCheckUpdate = (int) $cachedInfos['lastCheckUpdate'];
                if ($lastCheckUpdate + 86400 < time()) {
                    $nextVersion = $this->getNewVersion();
                } else {
                    $nextVersion = false;
                }
            } else {
                $nextVersion = ($lastVersion > VERSION) ? $lastVersion : false;
            }
        } else {
            $nextVersion = $this->getNewVersion();
        }

        if ($nextVersion) {
            $this->displayNewVersion($nextVersion);
        }
    }

    public function displayNewVersion(string $nextVersion): void
    {
        Show::msg("<p>" . Lang::get('configmanager-update-msg', $nextVersion) . "</p>
            <div style='text-align:center'><a class='button alert' href='" .
            Router::getInstance()->generate('configmanager-update', ['token' => UsersManager::getCurrentUser()->token]) .
            "'>" . Lang::get('configmanager-update') . "</a></div>");
    }

    public function getNewVersion()
    {
        $updaterManager = new UpdaterManager();
        $nextVersion = $updaterManager ? $updaterManager->getNextVersion() : false;
        $cachedInfos = Util::readJsonFile($this->cacheFile);
        if ($cachedInfos === false) {
            $cachedInfos = [];
        }
        $cachedInfos['lastVersion'] = $updaterManager->lastVersion;
        $cachedInfos['lastCheckUpdate'] = time();
        Util::writeJsonFile($this->cacheFile, $cachedInfos);
        if ($nextVersion) {
            logg('Nouvelle version trouvée : ' . $nextVersion, 'INFO');
        }
        return $nextVersion;
    }

    public function cacheFile(): string
    {
        return $this->cacheFile;
    }

    public function backupDirectory(): string
    {
        return $this->settingsDir;
    }

    public function registerRoutes(Router $router): void
    {
        if ($this->routesRegistered) {
            return;
        }
        $this->routesRegistered = true;

        $router->map('GET', '/admin/configmanager[/?]', 'Core\Settings\Controllers\ConfigManagerAdminController#home', 'configmanager-admin');
        $router->map('POST', '/admin/configmanager/save', 'Core\Settings\Controllers\ConfigManagerAdminController#save', 'configmanager-admin-save');
        $router->map('GET', '/admin/configmanager/cacheclear/[a:token]', 'Core\Settings\Controllers\ConfigManagerAdminController#clearCache', 'configmanager-admin-cache-clear');
        $router->map('GET', '/admin/configmanager/update/[a:token]', 'Core\Settings\Controllers\ConfigManagerUpdateController#process', 'configmanager-update');
        $router->map('GET', '/admin/configmanager/update-manual/[a:token]', 'Core\Settings\Controllers\ConfigManagerUpdateController#processManual', 'configmanager-manual-update');
        $router->map('GET', '/admin/configmanager/delete-install/[a:token]', 'Core\Settings\Controllers\ConfigManagerAdminController#deleteInstall', 'configmanager-delete-install');
        $router->map('GET', '/admin/configmanager/backup', 'Core\Settings\Controllers\ConfigManagerBackupAdminController#home', 'configmanager-backup');
        $router->map('GET', '/admin/configmanager/create-backup/[a:token]', 'Core\Settings\Controllers\ConfigManagerBackupAdminController#create', 'configmanager-create-backup');
        $router->map('GET', '/admin/configmanager/dl-backup/[a:token]/[i:timestamp]', 'Core\Settings\Controllers\ConfigManagerBackupAdminController#download', 'configmanager-dl-backup');
        $router->map('POST', '/admin/configmanager/delete-backup', 'Core\Settings\Controllers\ConfigManagerBackupAdminController#delete', 'configmanager-delete-backup');
    }

    public function registerHooks(): void
    {
        if ($this->hooksRegistered) {
            return;
        }
        $this->hooksRegistered = true;

        $core = Core::getInstance();
        $core->addHook('afterAdminTitle', function () {
            $this->displayInstallWarning();
        });
        $core->addHook('adminHead', function () {
            $this->checkNewVersion();
        });
        $core->addHook('adminToolsTemplates', function () {
            if ($this->isCurrentModule()) {
                echo '<a title="' . Lang::get('configmanager-backup') . '" id="configmanager-backup" href="' . Router::getInstance()->generate('configmanager-backup') . '"><i class="fa-solid fa-box-archive"></i></a>';
            }
        });
    }

    public function getAdminCssUrls(): array
    {
        if (!$this->isCurrentModule()) {
            return [];
        }
        return array_map(function ($path) {
            return Util::urlBuild('admin/' . ltrim($path, '/'));
        }, $this->adminCss);
    }

    public function getAdminJsUrls(): array
    {
        if (!$this->isCurrentModule()) {
            return [];
        }
        return array_map(function ($path) {
            return Util::urlBuild('admin/' . ltrim($path, '/'));
        }, $this->adminJs);
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

    private function bootstrapStorage(): void
    {
        if (!is_dir($this->settingsDir)) {
            @mkdir($this->settingsDir, 0755, true);
        }

        // migrate cache
        if (!file_exists($this->cacheFile) && file_exists($this->legacyDir . 'cache.json')) {
            $payload = util::readJsonFile($this->legacyDir . 'cache.json', true);
            util::writeJsonFile($this->cacheFile, $payload);
        } elseif (!file_exists($this->cacheFile)) {
            util::writeJsonFile($this->cacheFile, []);
        }

        // migrate backups
        if (is_dir($this->legacyDir)) {
            $files = scandir($this->legacyDir);
            foreach ($files as $file) {
                if (preg_match('/backup-.*\.zip/i', $file)) {
                    $source = $this->legacyDir . $file;
                    $dest = $this->settingsDir . $file;
                    if (!file_exists($dest)) {
                        @copy($source, $dest);
                    }
                }
            }
        }
    }

    protected function isCurrentModule(): bool
    {
        return Core::getInstance()->getPluginToCall() === 'configmanager';
    }
}


