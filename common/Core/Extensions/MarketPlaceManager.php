<?php

namespace Core\Extensions;

use Core\Storage\Cache;
use Core\Logger;
use Core\Core;
use Core\Extensions\MarketPlaceCurl;
use ZipArchive;

defined('ROOT') or exit('Access denied!');

class MarketPlaceManager
{
    protected Cache $cache;

    protected Logger $logger;

    private ExtensionsService $service;

    public function __construct(ExtensionsService $service)
    {
        $this->service = $service;
        $this->cache = new Cache();
        $this->logger = Core::getInstance()->getLogger();
    }

    protected function makeCurl(string $endpoint): MarketPlaceCurl
    {
        return new MarketPlaceCurl($endpoint, $this->service);
    }

    public function getThemes(): array
    {
        $token = (string) Core::getInstance()->getConfigVal('marketplaceLicenseKey');
        $cacheKey = 'marketplace-themes' . (!empty($token) ? '-' . md5($token) : '');
        $themes = $this->cache->get($cacheKey);
        if ($themes === false) {
            $curl = $this->makeCurl('repository/api/get-themes');
            $curl->post();
            $resp = $curl->execute()->getResponse();
            if ($resp['code'] !== 200 || empty($resp['body'])) {
                return [];
            }
            $themes = json_decode($resp['body']);
            // Cache réduit à 5 minutes (300 secondes) pour avoir des mises à jour plus rapides
            $this->cache->set($cacheKey, $themes, 300);
        }
        return $themes;
    }

    public function getPlugins(): array
    {
        $token = (string) Core::getInstance()->getConfigVal('marketplaceLicenseKey');
        $cacheKey = 'marketplace-plugins' . (!empty($token) ? '-' . md5($token) : '');
        $plugins = $this->cache->get($cacheKey);
        if ($plugins === false) {
            $curl = $this->makeCurl('repository/api/get-plugins');
            $curl->post();
            $resp = $curl->execute()->getResponse();
            if ($resp['code'] !== 200 || empty($resp['body'])) {
                return [];
            }
            $plugins = json_decode($resp['body']);
            // Cache réduit à 5 minutes (300 secondes) pour avoir des mises à jour plus rapides
            $this->cache->set($cacheKey, $plugins, 300);
        }
        return $plugins;
    }

    /**
     * Force le rafraîchissement du cache marketplace
     * 
     * @return void
     */
    public function clearCache(): void
    {
        $this->cache->delete('marketplace-plugins');
        $this->cache->delete('marketplace-themes');
        $this->cache->delete('marketplace-updates-summary');
    }

    public function getPlugin(string $slug)
    {
        $plugins = $this->getPlugins();
        foreach ($plugins as $plugin) {
            if ($plugin->slug === $slug) {
                return $plugin;
            }
        }
        return false;
    }

    public function getTheme(string $slug)
    {
        $themes = $this->getThemes();
        foreach ($themes as $theme) {
            if ($theme->slug === $slug) {
                return $theme;
            }
        }
        return false;
    }

    public function getRessourceAsArray(string $type, string $slug)
    {
        if ($type === MarketPlaceRessource::TYPE_PLUGIN) {
            return $this->getPlugin($slug);
        } elseif ($type === MarketPlaceRessource::TYPE_THEME) {
            return $this->getTheme($slug);
        }
        return false;
    }

    public function installRessource(MarketPlaceRessource $ressource): bool
    {
        if ($ressource->isInstalled) {
            $this->logger->info('updating ressource ' . $ressource->slug . ' to version ' . $ressource->getNextVersion());
        } else {
            $this->logger->info('installing ressource ' . $ressource->slug);
        }

        // Vérifier que l'extension ZipArchive est disponible
        if (!class_exists('ZipArchive') || !extension_loaded('zip')) {
            $this->logger->error('ZipArchive extension is not available. Please install php-zip extension.');
            return false;
        }

        // Vérifier que le dossier CACHE est accessible en écriture
        if (!is_writable(CACHE)) {
            $this->logger->error('Cache directory is not writable: ' . CACHE);
            return false;
        }

        $curl = $this->makeCurl('repository/api/install-' . $ressource->type);
        $curl->post();
        $curl->sendAsJson = true;
        // Ajouter un timeout plus long pour les téléchargements de ZIP
        $curl->addOption(CURLOPT_TIMEOUT, 300); // 5 minutes
        $curl->addOption(CURLOPT_CONNECTTIMEOUT, 30); // 30 secondes pour la connexion
        $curl->setDatas([
            'type' => $ressource->type,
            'slug' => $ressource->slug,
            'version' => $ressource->getNextVersion()
        ]);
        
        $this->logger->info('Downloading ZIP from API for ' . $ressource->slug . ' (version: ' . $ressource->getNextVersion() . ')');
        $curl->execute();
        
        // Vérifier les erreurs cURL avant de récupérer la réponse
        $curlError = $curl->getError();
        if ($curlError) {
            $this->logger->error('cURL error during download: ' . $curlError);
            return false;
        }
        
        $resp = $curl->getResponse();
        
        $this->logger->info('API response code: ' . ($resp['code'] ?? 'unknown'));
        $this->logger->info('API response body size: ' . (isset($resp['body']) ? strlen($resp['body']) : 0) . ' bytes');
        
        if ($resp['code'] !== 200) {
            $errorMsg = 'Unable to install ressource ' . $ressource->slug . ' - HTTP code: ' . ($resp['code'] ?? 'unknown');
            if (!empty($resp['body'])) {
                // Si la réponse est du JSON (erreur), essayer de le parser
                $errorBody = $resp['body'];
                if (substr($errorBody, 0, 1) === '{') {
                    $errorJson = json_decode($errorBody, true);
                    if (isset($errorJson['error'])) {
                        $errorMsg .= ' - Error: ' . $errorJson['error'];
                    } else {
                        $errorMsg .= PHP_EOL . $errorBody;
                    }
                } else {
                    $errorMsg .= PHP_EOL . substr($errorBody, 0, 500);
                }
            }
            $this->logger->error($errorMsg);
            return false;
        }
        
        if (empty($resp['body'])) {
            $this->logger->error('Unable to install ressource ' . $ressource->slug . ' - Empty response body');
            return false;
        }
        
        // Vérifier que la réponse est bien un ZIP (commence par PK)
        if (substr($resp['body'], 0, 2) !== 'PK') {
            $this->logger->error('Unable to install ressource ' . $ressource->slug . ' - Response is not a ZIP file. First bytes: ' . bin2hex(substr($resp['body'], 0, 10)));
            // Si c'est du JSON, essayer de le parser pour voir l'erreur
            if (substr($resp['body'], 0, 1) === '{') {
                $errorJson = json_decode($resp['body'], true);
                if (isset($errorJson['error'])) {
                    $this->logger->error('API error: ' . $errorJson['error']);
                }
            }
            return false;
        }
        
        $safeSlug = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$ressource->slug);
        if ($safeSlug === '' || $safeSlug !== (string)$ressource->slug) {
            $this->logger->error('Unable to install ressource - invalid slug: ' . $ressource->slug);
            return false;
        }

        $zipPath = CACHE . 'tmp_' . $safeSlug . '_' . bin2hex(random_bytes(8)) . '.zip';
        $written = file_put_contents($zipPath, $resp['body']);
        if ($written === false) {
            $this->logger->error('Unable to write ZIP file to: ' . $zipPath);
            return false;
        }
        
        $this->logger->info('ZIP file written: ' . $zipPath . ' (' . $written . ' bytes)');
        
        if (filesize($zipPath) === 0) {
            $this->logger->error('Unable to install ressource ' . $ressource->slug . ' (empty zip file)');
            @unlink($zipPath);
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            if (!$this->isSafeZip($zip)) {
                $zip->close();
                @unlink($zipPath);
                $this->logger->error('Unsafe ZIP archive (Zip Slip or symlink) rejected for ' . $ressource->slug);
                return false;
            }

            $targetDir = $ressource->type === MarketPlaceRessource::TYPE_PLUGIN ? PLUGINS : THEMES;
            
            // Résolution insensible à la casse pour préserver le nom exact du dossier existant (ex: LiveChat vs livechat)
            $existingDirName = null;
            if (is_dir($targetDir)) {
                $entries = scandir($targetDir);
                if ($entries !== false) {
                    foreach ($entries as $entry) {
                        if ($entry !== '.' && $entry !== '..' && strcasecmp($entry, $safeSlug) === 0) {
                            $existingDirName = $entry;
                            break;
                        }
                    }
                }
            }
            $targetSlug = $existingDirName ?? $safeSlug;
            $pluginDir = $targetDir . $targetSlug . DS;

            // Snapshot automatique pré-mise à jour si la ressource est déjà installée
            if ($ressource->isInstalled && is_dir($pluginDir)) {
                $backupDir = DATA_CORE_SETTINGS;
                if (!is_dir($backupDir)) {
                    @mkdir($backupDir, 0755, true);
                }
                $snapName = $backupDir . 'backup-pre-update-' . $ressource->type . '-' . $safeSlug . '-v' . ($ressource->version ?: 'current') . '-' . date('Y-m-d-H-i-s') . '.zip';
                \Core\Storage\Zip::createZipFromFolder($pluginDir, $snapName);
                $this->logger->info("Pre-update snapshot created for {$ressource->slug} at {$snapName}");
            }

            // Si un dossier avec une casse différente résiduelle existe, le nettoyer
            if ($existingDirName !== null && $existingDirName !== $safeSlug && is_dir($targetDir . $safeSlug)) {
                $this->removeDirectoryRecursive($targetDir . $safeSlug);
            }

            // Vérifier si le ZIP contient déjà le dossier parent ou les fichiers directement
            $firstEntry = $zip->getNameIndex(0);
            $hasParentDir = $firstEntry !== false && (
                strpos($firstEntry, $safeSlug . '/') === 0 ||
                ($existingDirName !== null && strpos($firstEntry, $existingDirName . '/') === 0)
            );
            
            $extractSuccess = false;
            if ($hasParentDir) {
                // Si le ZIP a un sous-dossier, l'extraire dans un dossier temporaire pour normaliser le nom cible
                $tempExtract = CACHE_PATH . 'ext_' . uniqid() . DS;
                @mkdir($tempExtract, 0755, true);
                $extractSuccess = $zip->extractTo($tempExtract);
                $zip->close();
                
                // Détecter le sous-dossier extrait
                $subDirs = glob($tempExtract . '*', GLOB_ONLYDIR);
                $extractedSubDir = !empty($subDirs) ? $subDirs[0] : null;
                
                if ($extractedSubDir && is_dir($extractedSubDir)) {
                    if (!is_dir($pluginDir)) {
                        @mkdir($pluginDir, 0755, true);
                    }
                    $this->copyDirectoryRecursive($extractedSubDir, $pluginDir);
                }
                $this->removeDirectoryRecursive($tempExtract);
            } else {
                // Le ZIP contient les fichiers à la racine, créer le dossier et extraire dedans
                if (!is_dir($pluginDir)) {
                    mkdir($pluginDir, 0755, true);
                }
                $extractSuccess = $zip->extractTo($pluginDir);
                $zip->close();
            }

            if (!$extractSuccess) {
                @unlink($zipPath);
                $this->logger->error('Unable to extract ZIP archive for ' . $ressource->slug);
                return false;
            }

            $extractedDir = rtrim($pluginDir, DS);

            // Mettre à jour l'horodatage des fichiers extraits pour forcer l'invalidation des caches
            $this->touchDirectoryRecursive($extractedDir);

            // Enregistrer l'empreinte (hash) d'arborescence de la version installée pour le suivi automatique des correctifs
            $installedHash = $ressource->getRemoteHash() ?: MarketPlaceRessource::computeDirectoryHash($extractedDir);
            if ($installedHash) {
                $hashFile = DATA_CORE_EXTENSIONS . 'hashes.json';
                $hashes = file_exists($hashFile) ? (\Utils\Util::readJsonFile($hashFile, true) ?: []) : [];
                $hashes[$ressource->type . ':' . $safeSlug] = $installedHash;
                \Utils\Util::writeJsonFile($hashFile, $hashes);
            }

            @unlink($zipPath);
            $this->runAfterUpdatePlugin($ressource);

            // Invalider immédiatement le cache de la marketplace (plugins et thèmes)
            $this->clearCache();

            // Vider le cache des templates compilés, OPcache et forcer le cache-busting des assets
            if (class_exists('\Core\Storage\CacheManager')) {
                $cacheManager = new \Core\Storage\CacheManager();
                $cacheManager->clearCache();
            }

            $this->logger->info($ressource->slug . ' correctly installed');
            return true;
        }
        @unlink($zipPath);
        $this->logger->error('Unable to install ressource ' . $ressource->slug . ' (zip error)');
        return false;
    }

    /**
     * Valide qu'une archive ZIP ne contient pas de traversée de répertoire (Zip Slip) ni de liens symboliques.
     */
    private function isSafeZip(ZipArchive $zip): bool
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false || strpos($name, "\0") !== false) {
                return false;
            }
            $normalized = str_replace('\\', '/', $name);
            if (
                str_starts_with($normalized, '/') ||
                preg_match('/^[a-zA-Z]:/', $normalized) ||
                $normalized === '..' ||
                str_starts_with($normalized, '../') ||
                str_contains($normalized, '/../') ||
                str_ends_with($normalized, '/..')
            ) {
                return false;
            }
            $opsys = 0;
            $attr = 0;
            if ($zip->getExternalAttributesIndex($i, $opsys, $attr) && $opsys === ZipArchive::OPSYS_UNIX) {
                $mode = ($attr >> 16) & 0170000;
                if ($mode === 0120000) {
                    return false;
                }
            }
        }
        return true;
    }

    protected function touchDirectoryRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            if (is_file($dir)) {
                @touch($dir);
            }
            return;
        }
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $item) {
                @touch($item->getPathname());
            }
        } catch (\Throwable $e) {}
    }

    protected function removeDirectoryRecursive(string $dir): bool
    {
        if (!is_dir($dir)) {
            return true;
        }
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->removeDirectoryRecursive($path) : @unlink($path);
        }
        return @rmdir($dir);
    }

    protected function copyDirectoryRecursive(string $src, string $dst): void
    {
        if (!is_dir($dst)) {
            @mkdir($dst, 0755, true);
        }
        $files = array_diff(scandir($src) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $srcPath = $src . DIRECTORY_SEPARATOR . $file;
            $dstPath = $dst . DIRECTORY_SEPARATOR . $file;
            if (is_dir($srcPath)) {
                $this->copyDirectoryRecursive($srcPath, $dstPath);
            } else {
                @copy($srcPath, $dstPath);
            }
        }
    }

    protected function runAfterUpdatePlugin(MarketPlaceRessource $ressource)
    {
        if ($ressource->type !== MarketPlaceRessource::TYPE_PLUGIN) {
            return;
        }
        $path = PLUGINS . $ressource->slug . DS . '_afterUpdate.php';
        if (!$ressource->isInstalled) {
            if (file_exists($path)) {
                unlink($path);
            }
            return;
        }
        if (!file_exists($path)) {
            return;
        }
        $this->logger->info('execute _afterUpdate.php from ' . $ressource->slug);
        include_once($path);
        unlink($path);
    }
}


