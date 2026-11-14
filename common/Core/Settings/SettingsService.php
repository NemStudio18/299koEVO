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
    private static bool $routesRegistered = false;
    private static bool $hooksRegistered = false;
    private static bool $installBadgeDisplayed = false;
    private static bool $updateBadgeDisplayed = false;
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
        [
            'name' => 'storage',
            'label' => 'storage.name',
            'icon' => 'fa-solid fa-database',
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
     * Check whether install artifacts are still present on disk.
     */
    public function hasInstallArtifacts(): bool
    {
        $installFile = ROOT . 'install.php';
        $installCss = ROOT . 'install' . DS . 'install.css';
        return file_exists($installFile) || file_exists($installCss);
    }

    /**
     * Render a compact persistent topbar badge when install artifacts remain.
     */
    public function displayInstallBadge(): void
    {
        if (self::$installBadgeDisplayed || !$this->hasInstallArtifacts()) {
            return;
        }
        $user = UsersManager::getCurrentUser();
        if (!$user) {
            return;
        }
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        if (!$evaluator->isAdmin(new \Core\Navigation\ConditionContext($user))) {
            return;
        }
        self::$installBadgeDisplayed = true;

        $deleteUrl = Router::getInstance()->generate('configmanager-delete-install', ['token' => $user->token]);
        $label = Lang::get('configmanager-install-badge') ?: 'install.php non supprimé';
        $title = Lang::get('configmanager-install-badge-title') ?: "Le fichier install.php n'est pas supprimé — cliquez pour le supprimer";

        echo '<a class="adm-install-badge" href="' . htmlspecialchars($deleteUrl, ENT_QUOTES, 'UTF-8') . '" title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">'
            . '<i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>'
            . '<span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>'
            . '</a>';
    }

    /**
     * Render a compact persistent topbar badge when a CMS update/patch is available.
     */
    public function displayUpdateBadge(): void
    {
        if (self::$updateBadgeDisplayed) {
            return;
        }
        $user = UsersManager::getCurrentUser();
        if (!$user) {
            return;
        }
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        if (!$evaluator->isAdmin(new \Core\Navigation\ConditionContext($user))) {
            return;
        }

        $cachedInfos = Util::readJsonFile($this->cacheFile);
        $nextVersion = $cachedInfos['nextVersion'] ?? false;
        if (!$nextVersion) {
            return;
        }
        self::$updateBadgeDisplayed = true;

        $isPatch = !empty($cachedInfos['isPatch']) || (bool) preg_match('/^[a-f0-9]{7,40}$/i', (string) $nextVersion);
        $updateUrl = Router::getInstance()->generate('configmanager-update', ['token' => $user->token]);

        if ($isPatch) {
            $shortCommit = substr((string) $nextVersion, 0, 7);
            $label = 'Correctif ' . $shortCommit;
            $title = 'Un correctif CMS est disponible (' . $shortCommit . '). Cliquez pour l\'appliquer.';
            echo '<a class="adm-install-badge adm-update-badge is-patch" href="' . htmlspecialchars($updateUrl, ENT_QUOTES, 'UTF-8') . '" title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">'
                . '<i class="fa-solid fa-shield-halved" aria-hidden="true"></i>'
                . '<span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>'
                . '</a>';
        } else {
            $label = 'Màj v' . $nextVersion;
            $title = 'Une nouvelle version ' . $nextVersion . ' est disponible. Cliquez pour mettre à jour.';
            echo '<a class="adm-install-badge adm-update-badge is-update" href="' . htmlspecialchars($updateUrl, ENT_QUOTES, 'UTF-8') . '" title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">'
                . '<i class="fa-solid fa-cloud-arrow-down" aria-hidden="true"></i>'
                . '<span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>'
                . '</a>';
        }
    }

    /**
     * Queue the install cleanup flash message once immediately after installation.
     */
    public function displayPostInstallWarningOnce(): void
    {
        if (empty($_SESSION['installOk'])) {
            return;
        }
        unset($_SESSION['installOk']);

        if (!$this->hasInstallArtifacts()) {
            return;
        }

        $user = UsersManager::getCurrentUser();
        $deleteUrl = Router::getInstance()->generate(
            'configmanager-delete-install',
            ['token' => $user ? $user->token : '']
        );

        Show::msg(
            '<span>' . Lang::get('configmanager-delete-install-msg') . '</span> '
            . '<a class="button msg-action-btn" href="' . htmlspecialchars($deleteUrl, ENT_QUOTES, 'UTF-8') . '">'
            . Lang::get('configmanager-delete-install') . '</a>',
            'warning'
        );
    }

    /**
     * Removes install.php if possible.
     */
    public function deleteInstallFile(): bool
    {
        $installFile = ROOT . 'install.php';
        $installCss = ROOT . 'install' . DS . 'install.css';
        $success = true;

        if (file_exists($installFile) && !@unlink($installFile)) {
            $success = false;
        }

        if (file_exists($installCss) && !@unlink($installCss)) {
            $success = false;
        }

        $installDir = ROOT . 'install';
        if (is_dir($installDir)) {
            $remaining = array_diff(scandir($installDir), ['.', '..']);
            if (empty($remaining)) {
                @rmdir($installDir);
            }
        }

        return $success;
    }

    /**
     * Checks for new versions using cache (hourly).
     */
    public function checkNewVersion(): void
    {
        $cachedInfos = Util::readJsonFile($this->cacheFile);
        if (!is_array($cachedInfos)) {
            $cachedInfos = [];
        }
        $lastCheckUpdate = (int) ($cachedInfos['lastCheckUpdate'] ?? 0);
        $ttl = $this->isCurrentModule() ? 60 : 3600;

        if ($lastCheckUpdate + $ttl < time()) {
            $nextVersion = $this->getNewVersion();
            $cachedInfos = Util::readJsonFile($this->cacheFile);
        } else {
            $nextVersion = $cachedInfos['nextVersion'] ?? false;
        }

        if ($nextVersion) {
            $lastNotified = $cachedInfos['lastNotifiedUpdate'] ?? null;
            if ($lastNotified !== (string) $nextVersion) {
                $this->displayNewVersion((string) $nextVersion);
                $cachedInfos['lastNotifiedUpdate'] = (string) $nextVersion;
                Util::writeJsonFile($this->cacheFile, $cachedInfos);
            }
        }
    }

    public function displayNewVersion(string $nextVersion): void
    {
        $updater = new UpdaterManager();
        $isPatch = (bool) preg_match('/^[a-f0-9]{7,40}$/i', $nextVersion);
        $token = UsersManager::getCurrentUser() ? UsersManager::getCurrentUser()->token : '';
        $updateUrl = Router::getInstance()->generate('configmanager-update', ['token' => $token]);

        if ($isPatch) {
            $shortCommit = substr($nextVersion, 0, 7);
            $msg = "<p><strong>[CORRECTIF / PATCH CMS DISPONIBLE]</strong> Un correctif de mise à jour pour le CMS est disponible (correctif <code>" . htmlspecialchars($shortCommit, ENT_QUOTES, 'UTF-8') . "</code>).</p>"
                . "<div style='text-align:center'><a class='button alert' href='" . htmlspecialchars($updateUrl, ENT_QUOTES, 'UTF-8') . "'>"
                . "<i class='fa-solid fa-shield-halved'></i> Appliquer le correctif</a></div>";
        } else {
            $msg = "<p>" . Lang::get('configmanager-update-msg', $nextVersion) . "</p>"
                . "<div style='text-align:center'><a class='button alert' href='" . htmlspecialchars($updateUrl, ENT_QUOTES, 'UTF-8') . "'>"
                . Lang::get('configmanager-update') . "</a></div>";
        }

        Show::msg($msg);
    }

    public function getNewVersion()
    {
        $updaterManager = new UpdaterManager();
        $nextVersion = $updaterManager->isReady ? $updaterManager->getNextVersion() : false;
        $cachedInfos = Util::readJsonFile($this->cacheFile);
        if (!is_array($cachedInfos)) {
            $cachedInfos = [];
        }
        $cachedInfos['lastVersion'] = $updaterManager->lastVersion;
        $cachedInfos['latestCommit'] = $updaterManager->latestCommit;
        $cachedInfos['nextVersion'] = $nextVersion;
        $cachedInfos['isPatch'] = (bool) preg_match('/^[a-f0-9]{7,40}$/i', (string) $nextVersion);
        $cachedInfos['lastCheckUpdate'] = time();
        Util::writeJsonFile($this->cacheFile, $cachedInfos);
        if ($nextVersion) {
            \Core\Core::getInstance()->getLogger()->info('Mise à jour trouvée : ' . $nextVersion . ($cachedInfos['isPatch'] ? ' (correctif/patch)' : ''));
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
        if (self::$routesRegistered) {
            return;
        }
        self::$routesRegistered = true;

        $router->map('GET', '/admin/configmanager[/?]', 'Core\Settings\Controllers\ConfigManagerAdminController#home', 'configmanager-admin');
        $router->map('POST', '/admin/configmanager/save', 'Core\Settings\Controllers\ConfigManagerAdminController#save', 'configmanager-admin-save');
        $router->map('GET', '/admin/configmanager/cacheclear/[a:token]', 'Core\Settings\Controllers\ConfigManagerAdminController#clearCache', 'configmanager-admin-cache-clear');
        $router->map('GET', '/admin/configmanager/update/[a:token]', 'Core\Settings\Controllers\ConfigManagerUpdateController#process', 'configmanager-update');
        $router->map('GET', '/admin/configmanager/update-manual/[a:token]', 'Core\Settings\Controllers\ConfigManagerUpdateController#processManual', 'configmanager-manual-update');
        $router->map('GET', '/admin/configmanager/check-update/[a:token]', 'Core\Settings\Controllers\ConfigManagerUpdateController#checkNow', 'configmanager-check-update');
        $router->map('GET', '/admin/configmanager/delete-install/[a:token]', 'Core\Settings\Controllers\ConfigManagerAdminController#deleteInstall', 'configmanager-delete-install');
        $router->map('GET', '/admin/configmanager/backup', 'Core\Settings\Controllers\ConfigManagerBackupAdminController#home', 'configmanager-backup');
        $router->map('GET', '/admin/configmanager/create-backup/[a:token]', 'Core\Settings\Controllers\ConfigManagerBackupAdminController#create', 'configmanager-create-backup');
        $router->map('GET', '/admin/configmanager/dl-backup/[a:token]/[i:timestamp]', 'Core\Settings\Controllers\ConfigManagerBackupAdminController#download', 'configmanager-dl-backup');
        $router->map('POST', '/admin/configmanager/delete-backup', 'Core\Settings\Controllers\ConfigManagerBackupAdminController#delete', 'configmanager-delete-backup');
        $router->map('GET', '/admin/configmanager/report[/?]', 'Core\Settings\Controllers\ConfigManagerAdminController#report', 'configmanager-report');
        $router->map('POST', '/admin/configmanager/report/send', 'Core\Settings\Controllers\ConfigManagerAdminController#sendReport', 'configmanager-report-send');
        $router->map('GET', '/admin/configmanager/telemetry/force-sync/[a:token]', 'Core\Settings\Controllers\ConfigManagerAdminController#forceTelemetrySync', 'configmanager-telemetry-force-sync');

        // Storage Management
        $router->map('GET', '/admin/storage[/?]', 'Core\Storage\Controllers\StorageAdminController#home', 'storage-admin');
        $router->map('POST', '/admin/storage/save', 'Core\Storage\Controllers\StorageAdminController#save', 'storage-admin-save');
        $router->map('POST', '/admin/storage/save-db', 'Core\Storage\Controllers\StorageAdminController#saveDatabase', 'storage-admin-save-db');
        $router->map('POST', '/admin/storage/migrate', 'Core\Storage\Controllers\StorageAdminController#migrate', 'storage-admin-migrate');
    }

    public function registerHooks(): void
    {
        if (self::$hooksRegistered) {
            return;
        }
        self::$hooksRegistered = true;

        $core = Core::getInstance();
        $core->addHook('adminTopbarBadges', function () {
            $this->displayInstallBadge();
            $this->displayUpdateBadge();
        });
        $core->addHook('adminHead', function () {
            $this->displayPostInstallWarningOnce();
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
            $payload = Util::readJsonFile($this->legacyDir . 'cache.json', true);
            Util::writeJsonFile($this->cacheFile, $payload);
        } elseif (!file_exists($this->cacheFile)) {
            Util::writeJsonFile($this->cacheFile, []);
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


