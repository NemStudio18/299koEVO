<?php

namespace Core\Settings\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Storage\CacheManager;
use Core\Lang;
use Utils\Show;
use Core\Theme;
use Core\Plugin\PluginsManager;
use Utils\Util;
use Core\Plugin\Plugin;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class ConfigManagerAdminController extends AdminController {
    
    public function home() {
        if (is_dir(ROOT . 'update')) {
            Show::msg(Lang::get('configmanager-update-dir-found') . '<br/><a class="button" href="' . $this->router->generate('configmanager-manual-update', ['token' => $this->user->token]) . '">' . Lang::get('configmanager-update') . '</a>', 'true');
        }

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('configmanager', 'config');

        $tpl->set('link', $this->router->generate('configmanager-admin-save'));
        $tpl->set('cacheClearLink', $this->router->generate('configmanager-admin-cache-clear', ['token' => $this->user->token]));

        // Get cache statistics
        $cacheManager = new CacheManager();
        $tpl->set('cacheStats', $cacheManager->getStats());
        $registrationGroups = array_map(static function ($group): array {
            return [
                'id' => $group->attributes['id'] ?? null,
                'slug' => $group->attributes['slug'] ?? '',
                'name' => $group->attributes['name'] ?? '',
            ];
        }, $this->core->auth()->getGroups());
        $tpl->set('registrationGroups', $registrationGroups);
        $tpl->set('registrationDefaultGroup', $this->core->getConfigVal('registrationDefaultGroup') ?? 'member');
        
        // Pass available locales and current locale to template
        $availablesLocales = Lang::getAvailablesLocales();
        $currentLocale = Lang::getLocale();
        $tpl->set('availablesLocales', $availablesLocales);
        $tpl->set('currentLocale', $currentLocale);
        
        // Generate language options HTML directly to avoid template parsing issues
        $langOptions = '';
        foreach ($availablesLocales as $code => $name) {
            $selected = ($currentLocale === $code) ? ' selected' : '';
            $langOptions .= '<option' . $selected . ' value="' . htmlspecialchars($code) . '">' . htmlspecialchars($name) . '</option>';
        }
        $tpl->set('langOptions', $langOptions);
        
        // Pass default plugin values to template for simpler conditions
        $defaultPlugin = $this->core->getConfigVal('defaultPlugin');
        $tpl->set('defaultPlugin', $defaultPlugin === null || $defaultPlugin === false || $defaultPlugin === '' ? 'page' : $defaultPlugin);
        $defaultAdminPlugin = $this->core->getConfigVal('defaultAdminPlugin');
        $tpl->set('defaultAdminPlugin', $defaultAdminPlugin === null || $defaultAdminPlugin === false || $defaultAdminPlugin === '' ? 'configmanager' : $defaultAdminPlugin);
        
        // Télémétrie
        $telemetryService = new \Core\Telemetry\TelemetryService();
        $tpl->set('telemetryLevel', (int) ($this->core->getConfigVal('telemetry_level') ?? 0));
        $tpl->set('installationId', $telemetryService->getInstallationId());
        
        // Vérifier si jamais synchronisé
        $hasSynced = $telemetryService->hasSynced();
        $lastSync = null;
        if ($hasSynced) {
            $lastSyncTimestamp = $telemetryService->getLastSync();
            if ($lastSyncTimestamp !== null) {
                $lastSync = date('Y-m-d H:i:s', $lastSyncTimestamp);
            }
        }
        $tpl->set('lastSync', $lastSync);
        $tpl->set('hasSynced', $hasSynced);
        $tpl->set('forceSyncUrl', $this->router->generate('configmanager-telemetry-force-sync', ['token' => $this->user->token]));

        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Force la synchronisation de la télémétrie
     */
    public function forceTelemetrySync(string $token)
    {
        if (!$this->user->isAuthorized() || $this->user->token !== $token) {
            Show::msg(Lang::get('core-not-authorized'), 'error');
            $this->core->redirect($this->router->generate('configmanager-admin'));
            return;
        }

        try {
            $telemetryService = new \Core\Telemetry\TelemetryService();
            $success = $telemetryService->forceSend();
            
            if ($success) {
                $this->logger->info('Telemetry: forced sync succeeded for ' . ($this->user->email ?? 'unknown'));
                Show::msg(Lang::get('configmanager-telemetry-sync-success'), 'success');
            } else {
                $this->logger->warning('Telemetry: forced sync failed for ' . ($this->user->email ?? 'unknown'));
                Show::msg(Lang::get('configmanager-telemetry-sync-error'), 'error');
            }
        } catch (\Exception $e) {
            $this->logger->error('Telemetry: forced sync exception for ' . ($this->user->email ?? 'unknown') . ' - ' . $e->getMessage());
            Show::msg(Lang::get('configmanager-telemetry-sync-error') . ': ' . $e->getMessage(), 'error');
        }

        $this->core->redirect($this->router->generate('configmanager-admin'));
    }

    public function save() {
        if (!$this->user->isAuthorized()) {
            return $this->home();
        }
        if (array_key_exists($_POST['siteLang'], Lang::$availablesLocales)) {
            $lang = $_POST['siteLang'];
        } else {
            $lang = Lang::getLocale();
        }
        $siteLogo = trim($_POST['siteLogo'] ?? '');
        $logoPlacement = $_POST['siteLogoPlacement'] ?? 'none';
        $allowedLogoPlacements = ['none', 'siteName', 'header'];
        if (!in_array($logoPlacement, $allowedLogoPlacements, true)) {
            $logoPlacement = 'none';
        }

        $config = [
            'siteName' => (trim($_POST['siteName']) != '') ? trim($_POST['siteName']) : 'Demo',
            'siteDesc' => (trim($_POST['siteDesc']) != '') ? trim($_POST['siteDesc']) : '',
            'siteLang' => $lang,
            'siteUrl' => (trim($_POST['siteUrl']) != '') ? rtrim(trim($_POST['siteUrl']), '/') : $this->core->getConfigVal('siteUrl'),
            'theme' => $_POST['theme'],
            'defaultPlugin' => $_POST['defaultPlugin'],
            'hideTitles' => (isset($_POST['hideTitles'])) ? true : false,
            'debug' => (isset($_POST['debug'])) ? true : false,
            'defaultAdminPlugin' => $_POST['defaultAdminPlugin'],
            'cache_enabled' => (isset($_POST['cache_enabled'])) ? true : false,
            'cache_duration' => (int)$_POST['cache_duration'],
            'cache_minify' => (isset($_POST['cache_minify'])) ? true : false,
            'cache_lazy_loading' => (isset($_POST['cache_lazy_loading'])) ? true : false,
            'siteLogo' => $siteLogo,
            'siteLogoPlacement' => $logoPlacement,
        ];
        $config['allowRegistrations'] = isset($_POST['allowRegistrations']);
        $defaultGroupSlug = $_POST['registrationDefaultGroup'] ?? 'member';
        if ($this->core->auth()->getGroupBySlug($defaultGroupSlug) === null) {
            $defaultGroupSlug = 'member';
        }
        $config['registrationDefaultGroup'] = $defaultGroupSlug;
        $validationMode = $_POST['registrationValidationMode'] ?? 'email';
        if (!in_array($validationMode, ['email', 'admin', 'none'], true)) {
            $validationMode = 'email';
        }
        $config['registrationValidationMode'] = $validationMode;
        
        // Télémétrie
        $telemetryLevel = (int) ($_POST['telemetry_level'] ?? 0);
        if ($telemetryLevel < 0 || $telemetryLevel > 2) {
            $telemetryLevel = 0;
        }
        $config['telemetry_level'] = $telemetryLevel;

        // Invalidate cache if needed (AVANT la sauvegarde pour avoir l'ancienne config)
        $this->invalidateCacheIfNeeded($config);
        
        $actor = $this->user->email ?? 'unknown';
        if (!$this->core->saveConfig($config, $config)) {
            $this->logger->error('Config: save failed for ' . $actor);
            Show::msg(Lang::get("core-changes-not-saved"), 'error');
        } else {
            $this->logger->info('Config: settings saved by ' . $actor);
            Show::msg(Lang::get("core-changes-saved"), 'success');
        }
        //$this->core->saveHtaccess($_POST['htaccess']);
        $this->core->redirect($this->router->generate('configmanager-admin'));
    }

    public function report() {
        if (!$this->user->isAuthorized()) {
            return $this->home();
        }

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('configmanager', 'report');
        $response->setTitle(Lang::get('configmanager-report-title'));

        // Récupérer la liste des plugins pour le select
        $pluginsManager = PluginsManager::getInstance();
        $plugins = [];
        foreach ($pluginsManager->getPlugins() as $plugin) {
            $plugins[] = [
                'slug' => $plugin->getName(),
                'name' => $plugin->getInfoVal('name') ?? $plugin->getName()
            ];
        }
        $tpl->set('plugins', $plugins);
        $tpl->set('sendUrl', $this->router->generate('configmanager-report-send'));

        $response->addTemplate($tpl);
        return $response;
    }

    public function sendReport() {
        if (!$this->user->isAuthorized()) {
            return $this->home();
        }

        $type = $_POST['type'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $plugin = trim($_POST['plugin'] ?? '');
        $screenshot = $_POST['screenshot'] ?? null;

        // Validation
        if (!in_array($type, ['bug', 'feature', 'question', 'other'], true)) {
            Show::msg(Lang::get('configmanager-report-invalid-type'), 'error');
            $this->core->redirect($this->router->generate('configmanager-report'));
            return;
        }

        if (empty($title) || strlen($title) < 3) {
            Show::msg(Lang::get('configmanager-report-title-required'), 'error');
            $this->core->redirect($this->router->generate('configmanager-report'));
            return;
        }

        if (empty($description) || strlen($description) < 10) {
            Show::msg(Lang::get('configmanager-report-description-required'), 'error');
            $this->core->redirect($this->router->generate('configmanager-report'));
            return;
        }

        // Validation email si fourni
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Show::msg(Lang::get('configmanager-report-invalid-email'), 'error');
            $this->core->redirect($this->router->generate('configmanager-report'));
            return;
        }

        // Envoyer le rapport
        $reportService = new \Core\Telemetry\ReportService();
        $result = $reportService->sendReport(
            $type,
            $title,
            $description,
            !empty($email) ? $email : null,
            !empty($plugin) ? $plugin : null,
            $screenshot
        );

        if ($result['success']) {
            Show::msg(Lang::get('configmanager-report-sent-success'), 'success');
            $this->logger->info('Config report sent by ' . ($this->user->email ?? 'unknown') . ' type ' . $type);
        } else {
            Show::msg(Lang::get('configmanager-report-sent-error') . ': ' . $result['message'], 'error');
            $this->logger->warning('Config report failed for ' . ($this->user->email ?? 'unknown') . ' - ' . ($result['message'] ?? 'unknown'));
        }

        $this->core->redirect($this->router->generate('configmanager-report'));
    }

    public function deleteInstall($token) {
        if (!$this->user->isAuthorized()) {
            return $this->home();
        }
        if ($this->core->settings()->deleteInstallFile()) {
            $this->logger->info('Config: install files removed by ' . ($this->user->email ?? 'unknown'));
            Show::msg(Lang::get('configmanager-deleted-install'), 'success');
        } else {
            $this->logger->warning('Config: install file removal failed for ' . ($this->user->email ?? 'unknown'));
            Show::msg(Lang::get('configmanager-error-deleting-install'), 'error');
        }
        return $this->home();
    }

    public function clearCache($token) {
        if (!$this->user->isAuthorized()) {
            return $this->home();
        }
        $cacheManager = new CacheManager();
        if ($cacheManager->clearCache()) {
            $this->logger->info('Config: cache cleared by ' . ($this->user->email ?? 'unknown'));
            Show::msg(Lang::get('configmanager-cache-clear-success'), 'success');
        } else {
            $this->logger->warning('Config: cache clear failed for ' . ($this->user->email ?? 'unknown'));
            Show::msg(Lang::get('configmanager-cache-clear-error'), 'error');
        }
        return $this->home();
    }



    /**
     * Invalidate cache when configuration changes
     * 
     * @param array $newConfig
     * @return void
     */
    protected function invalidateCacheIfNeeded(array $newConfig): void
    {
        $oldConfig = $this->core->getconfig();
        
        // If theme has changed
        if (isset($newConfig['theme']) && $newConfig['theme'] !== ($oldConfig['theme'] ?? '')) {
            // Invalidate old theme cache
            if (!empty($oldConfig['theme'])) {
                $oldTheme = new Theme($oldConfig['theme']);
                $oldTheme->invalidateCache();
            }
            // Invalidate new theme cache
            $newTheme = new Theme($newConfig['theme']);
            $newTheme->invalidateCache();
            // Force invalidate all page caches to ensure theme change is reflected
            $this->core->invalidateCacheByTag('page');
        }
        
        // If language has changed
        if (isset($newConfig['siteLang']) && $newConfig['siteLang'] !== ($oldConfig['siteLang'] ?? '')) {
            // Invalidate old language cache
            if (!empty($oldConfig['siteLang'])) {
                Lang::invalidateCache($oldConfig['siteLang']);
            }
            
            // Invalidate new language cache
            Lang::invalidateCache($newConfig['siteLang']);
            
            // Force invalidate all page caches to ensure language change is reflected
            $this->core->invalidateCacheByTag('page');
        }
        
        // If cache settings have changed - only invalidate specific caches
        $cacheSettings = ['cache_minify', 'cache_lazy_loading'];
        
        foreach ($cacheSettings as $setting) {
            if (isset($newConfig[$setting]) && $newConfig[$setting] !== ($oldConfig[$setting] ?? false)) {
                // Only invalidate caches that depend on these settings
                $this->core->invalidateCacheByTag('minify_enabled');
                $this->core->invalidateCacheByTag('lazy_enabled');
                break;
            }
        }
        
        // If cache is disabled, invalidate all cache
        if (isset($newConfig['cache_enabled']) && $newConfig['cache_enabled'] !== ($oldConfig['cache_enabled'] ?? true)) {
            if (!$newConfig['cache_enabled']) {
                // Cache disabled - invalidate all
                $this->core->invalidateAllCache();
            }
        }
        
        // If cache duration changed, invalidate all cache (as duration affects all cached content)
        if (isset($newConfig['cache_duration']) && $newConfig['cache_duration'] !== ($oldConfig['cache_duration'] ?? 3600)) {
            $this->core->invalidateAllCache();
        }
        
        // Invalidate plugin caches if their content might have changed
        $this->invalidatePluginCachesIfNeeded($oldConfig, $newConfig);
    }
    
    /**
     * Invalidate plugin caches if their content might have changed
     * 
     * @param array $oldConfig
     * @param array $newConfig
     * @return void
     */
    protected function invalidatePluginCachesIfNeeded(array $oldConfig, array $newConfig): void
    {
        // Get plugins manager to access all plugins
        $pluginsManager = PluginsManager::getInstance();
        $plugins = $pluginsManager->getPlugins();
        
        foreach ($plugins as $plugin) {
            $pluginName = $plugin->getName();
            $pluginConfig = $plugin->getConfig();
            
            // Check if plugin is activated
            if (!$plugin->getConfigVal('activate')) {
                continue;
            }
            
            // Check if plugin configuration has changed
            $pluginDataPath = DATA_PLUGIN . $pluginName . '/config.json';
            if (file_exists($pluginDataPath)) {
                $currentPluginConfig = Util::readJsonFile($pluginDataPath);
                if ($currentPluginConfig !== false) {
                    // Compare current config with what we have in memory
                    if ($this->hasPluginConfigChanged($pluginConfig, $currentPluginConfig)) {
                        // Plugin config changed, invalidate its cache
                        $plugin->invalidateCache();
                    }
                }
            }
            
            // Check for specific plugin content changes
            $this->checkPluginSpecificChanges($plugin, $oldConfig, $newConfig);
        }
    }
    
    /**
     * Check if plugin configuration has changed
     * 
     * @param array $memoryConfig
     * @param array $fileConfig
     * @return bool
     */
    protected function hasPluginConfigChanged(array $memoryConfig, array $fileConfig): bool
    {
        // Compare important config keys that would affect cache
        $importantKeys = ['activate', 'priority', 'label', 'itemsByPage', 'displayTOC', 'hideContent', 'comments'];
        
        foreach ($importantKeys as $key) {
            if (isset($memoryConfig[$key]) && isset($fileConfig[$key])) {
                if ($memoryConfig[$key] !== $fileConfig[$key]) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check for plugin-specific content changes
     * 
     * @param plugin $plugin
     * @param array $oldConfig
     * @param array $newConfig
     * @return void
     */
    protected function checkPluginSpecificChanges(Plugin $plugin, array $oldConfig, array $newConfig): void
    {
        $pluginName = $plugin->getName();
        
        // Blog plugin specific checks
        if ($pluginName === 'blog') {
            // Check if blog settings that affect content have changed
            if (isset($newConfig['blog_itemsByPage']) && $newConfig['blog_itemsByPage'] !== ($oldConfig['blog_itemsByPage'] ?? 5)) {
                $plugin->invalidateCache();
            }
            if (isset($newConfig['blog_displayTOC']) && $newConfig['blog_displayTLS'] !== ($oldConfig['blog_displayTOC'] ?? 'no')) {
                $plugin->invalidateCache();
            }
        }
        
        // Galerie plugin specific checks
        if ($pluginName === 'galerie') {
            if (isset($newConfig['galerie_order']) && $newConfig['galerie_order'] !== ($oldConfig['galerie_order'] ?? 'byDate')) {
                $plugin->invalidateCache();
            }
            if (isset($newConfig['galerie_showTitles']) && $newConfig['galerie_showTitles'] !== ($oldConfig['galerie_showTitles'] ?? '1')) {
                $plugin->invalidateCache();
            }
        }
        

        
        // Default plugin change - invalidate all plugin caches
        if (isset($newConfig['defaultPlugin']) && $newConfig['defaultPlugin'] !== ($oldConfig['defaultPlugin'] ?? '')) {
            $plugin->invalidateCache();
        }
    }
}