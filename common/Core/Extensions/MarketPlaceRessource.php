<?php

namespace Core\Extensions;

use Core\Plugin\PluginsManager;
use Core\Theme;
use stdClass;

defined('ROOT') or exit('Access denied!');

class MarketPlaceRessource
{
    const TYPE_PLUGIN = 'plugin';
    const TYPE_THEME = 'theme';
    public string $slug = '';

    public string $name = '';

    public string $description = '';

    public string $lastVersion = '';

    public string $version = '';

    public string $icon = '';

    public string $authorEmail = '';

    public string $authorWebsite = '';

    public string $type = '';

    public bool $isInstalled = false;

    public bool $isInstallable = false;

    protected $origRessource;

    protected $versionsUpdate;

    public function __construct(string $type, $origRessource)
    {
        $this->type = $type;
        $this->origRessource = $origRessource;
        $this->slug = $origRessource->slug;
        $this->name = $origRessource->name;
        $this->description = $origRessource->description ?? '';
        $this->lastVersion = $origRessource->version ?? '';
        $this->icon = $origRessource->icon ?? '';
        $this->authorEmail = $origRessource->authorEmail ?? '';
        $this->authorWebsite = $origRessource->authorWebsite ?? '';
        $this->versionsUpdate = $origRessource->versionsUpdate ?? new stdClass();

        if ($this->type === self::TYPE_PLUGIN) {
            $plu = PluginsManager::getInstance()->getPlugin($this->slug);
            if ($plu !== false) {
                $this->isInstalled = true;
                $this->version = $plu->getInfoVal('version') ?? '';
            }
        } else {
            $theme = new Theme($this->slug);
            if ($theme->isInstalled()) {
                $this->isInstalled = true;
                $this->version = $theme->version ?? '';
            }
        }
        $this->checkIsInstallable();
    }

    protected function checkIsInstallable(): void
    {
        $this->isInstallable = true;
        // Par défaut, utiliser '0.0.0' pour être compatible avec toutes les versions
        // sauf si une version minimale est explicitement requise
        $min299koVersion = $this->origRessource->required299koVersion ?? '0.0.0';
        if (!version_compare(VERSION, $min299koVersion, '>=')) {
            $this->isInstallable = false;
        }
        $minPHPVersion = $this->origRessource->requiredPHPVersion ?? '7.4.0';
        if (!version_compare(PHP_VERSION, $minPHPVersion, '>=')) {
            $this->isInstallable = false;
        }
    }

    public function getLocalHash(): ?string
    {
        $hashFile = DATA_CORE_EXTENSIONS . 'hashes.json';
        if (file_exists($hashFile)) {
            $hashes = Util::readJsonFile($hashFile, true);
            if (is_array($hashes) && isset($hashes[$this->type . ':' . $this->slug])) {
                return $hashes[$this->type . ':' . $this->slug];
            }
        }

        // Calcul récursif de l'empreinte du dossier (identique à l'algorithme du Hub)
        $dir = ($this->type === self::TYPE_PLUGIN) ? (PLUGINS . $this->slug) : (THEMES . $this->slug);
        if (!is_dir($dir)) {
            return null;
        }

        return self::computeDirectoryHash($dir);
    }

    public static function computeDirectoryHash(string $dir): string
    {
        $hashes = [];
        if (!is_dir($dir)) {
            return '';
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $subPath = substr($file->getPathname(), strlen($dir));
                if (str_contains($subPath, 'config.json') || str_contains($subPath, '.cache') || str_contains($subPath, '.git')) {
                    continue;
                }
                $hashes[] = $subPath . ':' . sha1_file($file->getPathname());
            }
        }
        sort($hashes);
        return sha1(implode('|', $hashes));
    }

    public function getRemoteHash(): ?string
    {
        return $this->origRessource->hash ?? $this->origRessource->sha ?? $this->origRessource->commit ?? null;
    }

    public function remoteHashMismatch(): bool
    {
        $remoteHash = $this->getRemoteHash();
        if (empty($remoteHash)) {
            return false;
        }
        $localHash = $this->getLocalHash();
        return (!empty($localHash) && $localHash !== $remoteHash);
    }

    public function isSecurityPatch(): bool
    {
        return ($this->isInstalled && $this->version === $this->lastVersion && $this->remoteHashMismatch());
    }

    public function updateNeeded(): bool
    {
        if (!$this->isInstalled) {
            return false;
        }

        // 1. Détection classique par version SemVer (ex: 1.0.0 vs 1.1.0)
        if (!empty($this->lastVersion) && version_compare($this->lastVersion, $this->version, '>')) {
            return true;
        }

        // 2. Détection automatique par hash (patch de sécurité / micro-fix sans incrément de version)
        if ($this->remoteHashMismatch()) {
            return true;
        }

        return false;
    }

    public function getPreviewUrl()
    {
        return $this->origRessource->preview_images[0] ?? false;
    }

    public function getOthersPreviewsUrl()
    {
        if (isset($this->origRessource->preview_images) && count($this->origRessource->preview_images) > 1) {
            return array_slice($this->origRessource->preview_images, 1, count($this->origRessource->preview_images) - 1);
        }
        return false;
    }

    public function getNextVersion(): string
    {
        return $this->versionsUpdate->{$this->version} ?? 'init';
    }
}


