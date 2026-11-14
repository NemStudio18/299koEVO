<?php

namespace Core\Extensions;

use Core\Plugin\PluginsManager;
use Core\Theme;
use Utils\Util;
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

    public function getCategory(): string
    {
        if ($this->type === self::TYPE_THEME) {
            return 'theme';
        }
        $slug = strtolower($this->slug);

        $categories = [
            'content'  => ['blog', 'pelleditor', 'tinymce', 'mdeditor', 'docs', 'homebuilder', 'page'],
            'commerce' => ['paiement', 'ecommerce', 'dons', 'payment', 'boutique'],
            'events'   => ['agenda', 'prordv', 'reservation'],
            'social'   => ['forum', 'livechat', 'contact', 'messaging', 'guestbook', 'newsletter'],
            'media'    => ['galerie', 'meteo', 'rss', 'searchmaps', 'sondage', '299search', 'highlight'],
            'system'   => ['antispam', 'lightstats', 'publicstats', 'notifications', 'oauth', 'pwa', 'seo', 'csseditor', 'libmanager', 'bugtracker', 'gamification', 'localfeed', 'auth', 'demo', 'telemetryadmin'],
        ];

        foreach ($categories as $cat => $slugs) {
            if (in_array($slug, $slugs, true)) {
                return $cat;
            }
        }

        $desc = strtolower($this->name . ' ' . $this->description);
        if (str_contains($desc, 'paiement') || str_contains($desc, 'boutique') || str_contains($desc, 'don') || str_contains($desc, 'stripe') || str_contains($desc, 'paypal')) return 'commerce';
        if (str_contains($desc, 'éditeur') || str_contains($desc, 'article') || str_contains($desc, 'texte') || str_contains($desc, 'wysiwyg')) return 'content';
        if (str_contains($desc, 'rendez-vous') || str_contains($desc, 'agenda') || str_contains($desc, 'date') || str_contains($desc, 'calendrier')) return 'events';
        if (str_contains($desc, 'chat') || str_contains($desc, 'message') || str_contains($desc, 'forum') || str_contains($desc, 'discussion')) return 'social';
        if (str_contains($desc, 'carte') || str_contains($desc, 'photo') || str_contains($desc, 'image') || str_contains($desc, 'rss') || str_contains($desc, 'recherche')) return 'media';

        return 'system';
    }

    public function getCategoryLabel(): string
    {
        $cat = $this->getCategory();
        $labels = [
            'content'  => 'Contenu',
            'commerce' => 'Commerce & Dons',
            'events'   => 'Événements & RDV',
            'social'   => 'Social & Contact',
            'media'    => 'Médias & Outils',
            'system'   => 'Système & Sécurité',
            'theme'    => 'Thème',
        ];
        return $labels[$cat] ?? 'Autre';
    }

    public function getCategoryIcon(): string
    {
        $cat = $this->getCategory();
        $icons = [
            'content'  => 'fa-solid fa-feather-pointed',
            'commerce' => 'fa-solid fa-credit-card',
            'events'   => 'fa-solid fa-calendar-days',
            'social'   => 'fa-solid fa-comments',
            'media'    => 'fa-solid fa-photo-film',
            'system'   => 'fa-solid fa-shield-halved',
            'theme'    => 'fa-solid fa-palette',
        ];
        return $icons[$cat] ?? 'fa-solid fa-puzzle-piece';
    }
}


