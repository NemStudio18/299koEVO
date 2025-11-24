<?php

namespace Core\Extensions;

use Core\Plugin\PluginsManager;
use Core\Theme;

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
        $min299koVersion = $this->origRessource->required299koVersion ?? '1.0.0';
        if (!version_compare(VERSION, $min299koVersion, '>=')) {
            $this->isInstallable = false;
        }
        $minPHPVersion = $this->origRessource->requiredPHPVersion ?? '7.4.0';
        if (!version_compare(PHP_VERSION, $minPHPVersion, '>=')) {
            $this->isInstallable = false;
        }
    }

    public function updateNeeded(): bool
    {
        return ($this->isInstalled && $this->version != $this->lastVersion);
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


