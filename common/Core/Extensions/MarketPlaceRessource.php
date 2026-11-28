<?php

namespace Core\Extensions;

use Core\Core;
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

    public bool $isExclusive = false;

    public string $exclusiveLabel = '';

    public bool $isCommunity = false;

    public bool $isOfficial = true;

    public string $channel = 'official';

    public array $requiredPlugins = [];

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
        $this->requiredPlugins = (array)($origRessource->requiredPlugins ?? $origRessource->requires ?? []);
        if (empty($this->requiredPlugins) && strtolower($this->slug) === 'socialx') {
            $this->requiredPlugins = ['SocialFeed'];
        }
        $this->authorEmail = $origRessource->authorEmail ?? '';
        $this->authorWebsite = $origRessource->authorWebsite ?? '';
        $this->versionsUpdate = $origRessource->versionsUpdate ?? new stdClass();
        $this->isExclusive = !empty($origRessource->exclusive);
        $this->exclusiveLabel = (string)($origRessource->exclusive_label ?? ($this->isExclusive ? 'Exclusivité privée' : ''));
        $this->channel = (string)($origRessource->channel ?? ($this->isExclusive ? 'exclusive' : 'official'));
        $this->isCommunity = !empty($origRessource->is_community)
            || !empty($origRessource->community)
            || $this->channel === 'community'
            || (!empty($this->authorEmail)
                && !preg_match('/@(flexcb\.fr|299ko\.com|nemstudio)/i', $this->authorEmail)
                && strpos(strtolower($this->authorWebsite), '299ko.flexcb.fr') === false
                && !$this->isExclusive);
        $this->isOfficial = !$this->isExclusive && !$this->isCommunity;

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

    public function getRequiredPlugins(): array
    {
        return $this->requiredPlugins;
    }

    public function getMissingRequiredPlugins(?PluginsManager $pluginsManager = null): array
    {
        if ($this->type !== self::TYPE_THEME || empty($this->requiredPlugins)) {
            return [];
        }
        if (!$pluginsManager) {
            $pluginsManager = \Core\Plugin\PluginsManager::getInstance();
        }
        $missing = [];
        foreach ($this->requiredPlugins as $reqP) {
            $p = $pluginsManager->getPlugin($reqP);
            if (!$p) {
                $missing[] = $reqP;
            }
        }
        return $missing;
    }

    public function getMissingRequiredPluginsString(?PluginsManager $pluginsManager = null): string
    {
        return implode(', ', $this->getMissingRequiredPlugins($pluginsManager));
    }

    public function isCommunity(): bool
    {
        return $this->isCommunity;
    }

    public function isOfficial(): bool
    {
        return $this->isOfficial;
    }

    public function getChannel(): string
    {
        return $this->channel;
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
        if (!$this->isInstalled) {
            return false;
        }

        if (!empty($this->lastVersion) && !empty($this->version) && version_compare($this->version, $this->lastVersion, '>')) {
            return false;
        }

        $remoteHash = $this->getRemoteHash();
        if (empty($remoteHash)) {
            return false;
        }

        $hashFile = DATA_CORE_EXTENSIONS . 'hashes.json';
        $hashes = file_exists($hashFile) ? (Util::readJsonFile($hashFile, true) ?: []) : [];
        if (!is_array($hashes)) {
            $hashes = [];
        }

        $key = $this->type . ':' . $this->slug;
        $installedHash = $hashes[$key] ?? null;

        if (!empty($installedHash) && $installedHash === $remoteHash) {
            return false;
        }

        // Si le hash enregistré est absent ou différent, vérifier l'empreinte réelle du dossier local
        $dir = ($this->type === self::TYPE_PLUGIN) ? (PLUGINS . $this->slug) : (THEMES . $this->slug);
        if (!is_dir($dir)) {
            return false;
        }

        $localHash = self::computeDirectoryHash($dir);
        if ($localHash !== '' && $localHash === $remoteHash) {
            if (is_dir(DATA_CORE_EXTENSIONS) || @mkdir(DATA_CORE_EXTENSIONS, 0755, true)) {
                $hashes[$key] = $remoteHash;
                Util::writeJsonFile($hashFile, $hashes);
            }
            return false;
        }

        // En environnement de développement local (domaine .test / .local ou branche develop active),
        // le code local prime : on évite les faux avis de mise à jour pendant les sessions de dev
        if (self::isLocalDevEnvironment()) {
            if ($localHash !== '' && (is_dir(DATA_CORE_EXTENSIONS) || @mkdir(DATA_CORE_EXTENSIONS, 0755, true))) {
                $hashes[$key] = $localHash;
                Util::writeJsonFile($hashFile, $hashes);
            }
            return false;
        }

        return true;
    }

    /**
     * Détermine si l'instance tourne dans un environnement de développement local
     */
    public static function isLocalDevEnvironment(): bool
    {
        if (defined('DEV_MODE') && DEV_MODE) {
            return true;
        }
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (str_ends_with($host, '.test') || str_ends_with($host, '.local') || $host === 'localhost' || str_starts_with($host, '127.0.0.1')) {
            return true;
        }
        $gitHead = ROOT . '.git' . DIRECTORY_SEPARATOR . 'HEAD';
        if (is_file($gitHead)) {
            $headContent = trim((string) @file_get_contents($gitHead));
            if (str_contains($headContent, 'develop') || str_contains($headContent, 'feature/')) {
                return true;
            }
        }
        return false;
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
        if (!empty($this->origRessource->preview_images[0])) {
            return $this->origRessource->preview_images[0];
        }
        if ($this->type === self::TYPE_THEME) {
            // 1. Si présent en local : preview.png en priorité, sinon icon.png
            $localPreview = THEMES . $this->slug . '/preview.png';
            if (file_exists($localPreview)) {
                return \Utils\Util::urlBuild(THEMES . $this->slug . '/preview.png');
            }
            $localIcon = THEMES . $this->slug . '/icon.png';
            if (file_exists($localIcon)) {
                return \Utils\Util::urlBuild(THEMES . $this->slug . '/icon.png');
            }
            // 2. Sinon URL de l'API Marketplace officielle
            $marketUrl = rtrim((string)Core::getInstance()->getConfigVal('marketplaceUrl'), '/');
            if (empty($marketUrl)) {
                $marketUrl = 'https://299ko-api.flexcb.fr';
            }
            return $marketUrl . '/repository/api/asset?type=themes&slug=' . rawurlencode($this->slug) . '&file=preview.png';
        }
        return false;
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

    public function hasIconImage(): bool
    {
        return !empty($this->icon) && str_contains($this->icon, '://');
    }

    public function getIconUrl(): string
    {
        return $this->icon;
    }

    public function getIconClass(): string
    {
        if ($this->hasIconImage()) {
            return '';
        }
        return !empty($this->icon) ? $this->icon : $this->getCategoryIcon();
    }
}


