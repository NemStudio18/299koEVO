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
        $themes = $this->cache->get('marketplace-themes');
        if ($themes === false) {
            $curl = $this->makeCurl('repository/api/get-themes');
            $curl->post();
            $resp = $curl->execute()->getResponse();
            if ($resp['code'] !== 200 || empty($resp['body'])) {
                return [];
            }
            $themes = json_decode($resp['body']);
            // Cache réduit à 5 minutes (300 secondes) pour avoir des mises à jour plus rapides
            $this->cache->set('marketplace-themes', $themes, 300);
        }
        return $themes;
    }

    public function getPlugins(): array
    {
        $plugins = $this->cache->get('marketplace-plugins');
        if ($plugins === false) {
            $curl = $this->makeCurl('repository/api/get-plugins');
            $curl->post();
            $resp = $curl->execute()->getResponse();
            if ($resp['code'] !== 200 || empty($resp['body'])) {
                return [];
            }
            $plugins = json_decode($resp['body']);
            // Cache réduit à 5 minutes (300 secondes) pour avoir des mises à jour plus rapides
            $this->cache->set('marketplace-plugins', $plugins, 300);
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
        
        $zipPath = CACHE . 'tmp.zip';
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
        if ($zip->open(CACHE . 'tmp.zip') === true) {
            $targetDir = $ressource->type === MarketPlaceRessource::TYPE_PLUGIN ? PLUGINS : THEMES;
            $pluginDir = $targetDir . $ressource->slug . DS;
            
            // Vérifier si le ZIP contient déjà le dossier parent ou les fichiers directement
            $firstEntry = $zip->getNameIndex(0);
            $hasParentDir = $firstEntry !== false && strpos($firstEntry, $ressource->slug . '/') === 0;
            
            if ($hasParentDir) {
                // Le ZIP contient déjà le dossier parent, extraire directement
                $zip->extractTo($targetDir);
            } else {
                // Le ZIP contient les fichiers à la racine, créer le dossier et extraire dedans
                if (!is_dir($pluginDir)) {
                    mkdir($pluginDir, 0755, true);
                }
                $zip->extractTo($pluginDir);
            }
            
            $zip->close();

            // Mettre à jour l'horodatage des fichiers extraits pour forcer l'invalidation des caches
            $this->touchDirectoryRecursive($hasParentDir ? ($targetDir . $ressource->slug) : $pluginDir);

            // Enregistrer l'empreinte (hash) de la version installée pour le suivi automatique des correctifs
            $installedHash = $ressource->getRemoteHash() ?: (file_exists(CACHE . 'tmp.zip') ? sha1_file(CACHE . 'tmp.zip') : null);
            if ($installedHash) {
                $hashFile = DATA_CORE_EXTENSIONS . 'hashes.json';
                $hashes = file_exists($hashFile) ? (\Utils\Util::readJsonFile($hashFile, true) ?: []) : [];
                $hashes[$ressource->type . ':' . $ressource->slug] = $installedHash;
                \Utils\Util::writeJsonFile($hashFile, $hashes);
            }

            unlink(CACHE . 'tmp.zip');
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
        $this->logger->error('Unable to install ressource ' . $ressource->slug . ' (zip error)');
        return false;
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


