<?php

namespace Core\Extensions;

use Core\Storage\Cache;
use Core\Logger;
use Core\Core;
use Core\Extensions\MarketPlaceCurl;

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
            $this->cache->set('marketplace-themes', $themes, 3600);
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
            $this->cache->set('marketplace-plugins', $plugins, 3600);
        }
        return $plugins;
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

        $curl = $this->makeCurl('repository/api/install-' . $ressource->type);
        $curl->post();
        $curl->setDatas([
            'type' => $ressource->type,
            'slug' => $ressource->slug,
            'version' => $ressource->getNextVersion()
        ]);
        $resp = $curl->execute()->getResponse();
        if ($resp['code'] !== 200 || empty($resp['body'])) {
            $this->logger->error('Unable to install ressource ' . $ressource->slug . PHP_EOL . ($resp['body'] ?? ''));
            return false;
        }
        file_put_contents(CACHE . 'tmp.zip', $resp['body']);
        if (filesize(CACHE . 'tmp.zip') === 0) {
            $this->logger->error('Unable to install ressource ' . $ressource->slug . ' (empty zip file)');
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open(CACHE . 'tmp.zip') === true) {
            if ($ressource->type === MarketPlaceRessource::TYPE_PLUGIN) {
                $zip->extractTo(PLUGINS);
            } elseif ($ressource->type === MarketPlaceRessource::TYPE_THEME) {
                $zip->extractTo(THEMES);
            }
            $zip->close();
            unlink(CACHE . 'tmp.zip');
            $this->runAfterUpdatePlugin($ressource);
            $this->logger->info($ressource->slug . ' correctly installed');
            return true;
        }
        $this->logger->error('Unable to install ressource ' . $ressource->slug . ' (zip error)');
        return false;
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


