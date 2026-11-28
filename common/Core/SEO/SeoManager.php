<?php

namespace Core\SEO;

use Core\Core;
use Core\Lang;
use Core\Page\PageItem;
use Core\Page\Page;

defined('ROOT') or exit('Access denied!');

/**
 * Moteur SEO natif zero-config pour 299koEVO
 * Gère Canonical, Robots, OpenGraph, Twitter Cards et Schema.org JSON-LD
 *
 * @copyright (C) 2026, 299Ko EVO
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
class SeoManager
{
    /**
     * Point d'entrée principal hook endFrontHead
     */
    public static function renderHeadMetas($param = ''): string
    {
        $core = Core::getInstance();
        $siteUrl = rtrim((string)$core->getConfigVal('siteUrl'), '/');
        $siteName = (string)$core->getConfigVal('siteName');
        $siteDesc = trim(preg_replace('/\s+/', ' ', strip_tags((string)$core->getConfigVal('siteDesc'))));

        $existingMetas = implode("\n", $core->getMetas());

        // Détection de la page courante et du statut 404
        global $pageItem, $runPlugin;
        $is404 = (http_response_code() === 404) || ($core->getTitleTag() === Lang::get('core-404-title'));
        $isHomepage = false;

        // 1. Détermination de l'URL Canonique
        $canonicalUrl = '';
        if ($is404) {
            $canonicalUrl = $siteUrl . '/404';
        } elseif ($pageItem instanceof PageItem) {
            if ($pageItem->getIsHomepage() || $pageItem->getPosition() == 1 || $pageItem->getSlug() === '') {
                $isHomepage = true;
                $canonicalUrl = $siteUrl . '/';
            } else {
                $pageService = new Page();
                $canonicalUrl = $pageService->makeUrl($pageItem);
            }
        } else {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
            $path = parse_url($requestUri, PHP_URL_PATH) ?: '/';
            if ($path === '/' || $path === '' || $path === '/index.php') {
                $isHomepage = true;
                $canonicalUrl = $siteUrl . '/';
            } else {
                $canonicalUrl = $siteUrl . '/' . ltrim($path, '/');
            }
        }

        // Support multilingue si TranslationManager est actif
        if ($pageItem instanceof PageItem && class_exists('\Core\Translation\TranslationManager')) {
            $tm = \Core\Translation\TranslationManager::getInstance();
            $activeLoc = method_exists($core, 'getLocale') ? (string)$core->getLocale() : ($pageItem->getLocale() ?: $tm->getDefaultLocale());
            $pageService = new Page();
            $currentRelPath = str_replace($siteUrl, '', $pageService->makeUrl($pageItem));
            $localizedUrl = $siteUrl . $tm->buildLocalizedUrl($currentRelPath ?: '/', $pageItem->getLocale() ?: $activeLoc);
            if (!empty($localizedUrl)) {
                $canonicalUrl = $localizedUrl;
            }
        }

        // 2. Détermination du Titre et de la Description
        $rawTitle = $core->getTitleTag();
        if (($rawTitle === null || trim((string)$rawTitle) === '') && $runPlugin && method_exists($runPlugin, 'getTitleTag')) {
            $rawTitle = $runPlugin->getTitleTag();
        }
        if (($rawTitle === null || trim((string)$rawTitle) === '') && $pageItem instanceof PageItem) {
            $rawTitle = $pageItem->getMetaTitle() ?: $pageItem->getName();
        }
        if ($rawTitle === null || trim((string)$rawTitle) === '') {
            $rawTitle = $is404 ? Lang::get('core-404-title') : $siteName;
        }
        $rawTitle = trim((string)$rawTitle);

        // Si page d'accueil avec titre spécifique ou format standard
        $fullTitle = $rawTitle;
        if ($isHomepage && $pageItem instanceof PageItem && !empty($pageItem->getMetaTitle())) {
            $fullTitle = trim($pageItem->getMetaTitle());
        } elseif (stripos($fullTitle, $siteName) === false) {
            $fullTitle = $rawTitle . ' - ' . $siteName;
        }
        $fullTitle = trim($fullTitle);

        $rawDesc = $core->getMetaDescription();
        if (($rawDesc === null || trim((string)$rawDesc) === '') && $runPlugin && method_exists($runPlugin, 'getMetaDescriptionTag')) {
            $rawDesc = $runPlugin->getMetaDescriptionTag();
        }
        if (($rawDesc === null || trim((string)$rawDesc) === '') && $pageItem instanceof PageItem) {
            $rawDesc = $pageItem->getMetaDescription();
        }
        if ($rawDesc === null || trim((string)$rawDesc) === '') {
            $rawDesc = $siteDesc;
        }
        $metaDesc = trim(preg_replace('/\s+/', ' ', strip_tags((string)$rawDesc)));
        if (mb_strlen($metaDesc) > 165) {
            $metaDesc = mb_substr($metaDesc, 0, 162) . '...';
        }

        // 3. Extraction d'Image intelligente (Fallback Hierarchy)
        $ogImage = self::extractOgImage($core, $pageItem, $siteUrl);
        $imageDims = self::getImageDimensions($ogImage, $siteUrl);

        // Langue & Locale
        $langCode = Lang::getLocale() ?: 'fr';
        $ogLocale = ($langCode === 'fr') ? 'fr_FR' : (($langCode === 'en') ? 'en_US' : $langCode . '_' . strtoupper($langCode));
        $inLanguage = method_exists('\Core\Lang', 'getBcp47Locale') ? Lang::getBcp47Locale() : (($langCode === 'fr') ? 'fr-FR' : (($langCode === 'en') ? 'en-US' : $langCode));

        // Détection noindex
        $noIndex = $is404 || ($pageItem instanceof PageItem && method_exists($pageItem, 'getNoIndex') && $pageItem->getNoIndex());

        $output = [];

        // --- ROBOTS ---
        if (!self::metaExists($existingMetas, 'name="robots"') && !self::metaExists($existingMetas, "name='robots'")) {
            if ($noIndex) {
                $output[] = '<meta name="robots" content="noindex, nofollow" />';
            } else {
                $output[] = '<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />';
            }
        }

        // --- CANONICAL ---
        if (!self::metaExists($existingMetas, 'rel="canonical"') && !self::metaExists($existingMetas, "rel='canonical'")) {
            $output[] = '<link rel="canonical" href="' . htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') . '" />';
        }

        // --- OPEN GRAPH ---
        if (!self::metaExists($existingMetas, 'property="og:locale"') && !self::metaExists($existingMetas, "property='og:locale'")) {
            $output[] = '<meta property="og:locale" content="' . htmlspecialchars($ogLocale, ENT_QUOTES, 'UTF-8') . '" />';
        }
        if (!self::metaExists($existingMetas, 'property="og:type"') && !self::metaExists($existingMetas, "property='og:type'")) {
            $ogType = ($runPlugin && $core->getPluginToCall() === 'blog' && !$isHomepage) ? 'article' : 'website';
            $output[] = '<meta property="og:type" content="' . $ogType . '" />';
        }
        if (!self::metaExists($existingMetas, 'property="og:title"') && !self::metaExists($existingMetas, "property='og:title'")) {
            $output[] = '<meta property="og:title" content="' . htmlspecialchars($fullTitle, ENT_QUOTES, 'UTF-8') . '" />';
        }
        if (!self::metaExists($existingMetas, 'property="og:description"') && !self::metaExists($existingMetas, "property='og:description'")) {
            $output[] = '<meta property="og:description" content="' . htmlspecialchars($metaDesc, ENT_QUOTES, 'UTF-8') . '" />';
        }
        if (!self::metaExists($existingMetas, 'property="og:url"') && !self::metaExists($existingMetas, "property='og:url'")) {
            $output[] = '<meta property="og:url" content="' . htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') . '" />';
        }
        if (!self::metaExists($existingMetas, 'property="og:site_name"') && !self::metaExists($existingMetas, "property='og:site_name'")) {
            $output[] = '<meta property="og:site_name" content="' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . '" />';
        }
        if (!empty($ogImage) && !self::metaExists($existingMetas, 'property="og:image"') && !self::metaExists($existingMetas, "property='og:image'")) {
            $output[] = '<meta property="og:image" content="' . htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') . '" />';
            if (!empty($imageDims['width']) && !empty($imageDims['height'])) {
                $output[] = '<meta property="og:image:width" content="' . (int)$imageDims['width'] . '" />';
                $output[] = '<meta property="og:image:height" content="' . (int)$imageDims['height'] . '" />';
            }
        }

        // --- TWITTER CARDS ---
        if (!self::metaExists($existingMetas, 'name="twitter:card"') && !self::metaExists($existingMetas, "name='twitter:card'")) {
            $output[] = '<meta name="twitter:card" content="summary_large_image" />';
            $output[] = '<meta name="twitter:title" content="' . htmlspecialchars($fullTitle, ENT_QUOTES, 'UTF-8') . '" />';
            $output[] = '<meta name="twitter:description" content="' . htmlspecialchars($metaDesc, ENT_QUOTES, 'UTF-8') . '" />';
            if (!empty($ogImage)) {
                $output[] = '<meta name="twitter:image" content="' . htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') . '" />';
            }
        }

        // --- SCHEMA.ORG JSON-LD GRAPH ---
        if (!self::metaExists($existingMetas, 'application/ld+json')) {
            $jsonLd = self::buildSchemaJsonLd($core, $siteUrl, $siteName, $siteDesc, $canonicalUrl, $fullTitle, $metaDesc, $ogImage, $inLanguage, $isHomepage, $pageItem);
            if (!empty($jsonLd)) {
                $output[] = '<script type="application/ld+json">' . "\n" . json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n" . '</script>';
            }
        }

        $buffer = (string)$param;
        if (!empty($output)) {
            if ($buffer !== '') {
                $buffer .= "\n\t";
            } else {
                $buffer .= "\t";
            }
            $buffer .= implode("\n\t", $output) . "\n";
        }
        return $buffer;
    }

    /**
     * Extraction intelligente de l'image représentative (OG Image)
     */
    public static function extractOgImage(Core $core, $pageItem, string $siteUrl): string
    {
        // 1. Hook pour injection prioritaire (ex: plugins média, galerie)
        $ogImageData = $core->callHook('page:og-image', ['pageItem' => $pageItem, 'url' => null]);
        if (!empty($ogImageData['url'])) {
            return self::makeAbsoluteUrl($ogImageData['url'], $siteUrl);
        }

        // 2. Image à la une explicite de la page (champ img)
        if ($pageItem instanceof PageItem && method_exists($pageItem, 'getImg') && !empty($pageItem->getImg())) {
            return self::makeAbsoluteUrl((string) $pageItem->getImg(), $siteUrl);
        }

        // 3. Recherche intelligente d'une photo éditoriale dans l'ordre d'apparition du document (Hero CSS ou <img>, hors icônes/logos SVG)
        if ($pageItem instanceof PageItem && method_exists($pageItem, 'getContent')) {
            $content = html_entity_decode((string) $pageItem->getContent(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (preg_match_all('/(?:<img[^>]+src=["\']([^"\']+)["\']|background(?:-image)?\s*:\s*url\([\'"]?([^\'"\)]+)[\'"]?\))/i', $content, $matches, PREG_SET_ORDER)) {
                $fallbackImg = '';
                foreach ($matches as $m) {
                    $imgSrc = trim((string) (!empty($m[1]) ? $m[1] : ($m[2] ?? '')));
                    if ($imgSrc === '' || str_starts_with($imgSrc, 'data:')) {
                        continue;
                    }
                    if ($fallbackImg === '') {
                        $fallbackImg = $imgSrc;
                    }
                    if (!preg_match('/(logo|icon|favicon|\.svg(\?|$))/i', $imgSrc)) {
                        return self::makeAbsoluteUrl($imgSrc, $siteUrl);
                    }
                }
                if ($fallbackImg !== '') {
                    return self::makeAbsoluteUrl($fallbackImg, $siteUrl);
                }
            }
        }

        // 4. Image OpenGraph globale éventuelle configurée
        $defaultOg = (string) $core->getConfigVal('seo_og_image');
        if (!empty($defaultOg)) {
            return self::makeAbsoluteUrl($defaultOg, $siteUrl);
        }

        // 5. Logo du site configuré (dernier recours)
        $siteLogo = (string)$core->getConfigVal('siteLogo');
        if (!empty($siteLogo)) {
            return self::makeAbsoluteUrl($siteLogo, $siteUrl);
        }

        // 6. Recherche générique dans le dossier du thème actif
        $theme = (string)$core->getConfigVal('theme');
        if (defined('THEMES') && !empty($theme)) {
            $themeDir = THEMES . $theme . '/';
            $candidates = [
                'preview.png',
                'header.webp',
                'header.jpg',
                'assets/images/logoheader.png',
                'assets/images/logo.png',
                'logo.png',
                'icon.png',
            ];
            foreach ($candidates as $cand) {
                if (file_exists($themeDir . $cand)) {
                    return $siteUrl . '/theme/' . $theme . '/' . $cand;
                }
            }
        }

        return '';
    }

    /**
     * Construction du graphe JSON-LD Schema.org unifié
     */
    public static function buildSchemaJsonLd(
        Core $core,
        string $siteUrl,
        string $siteName,
        string $siteDesc,
        string $canonicalUrl,
        string $pageTitle,
        string $pageDesc,
        string $ogImage,
        string $inLanguage,
        bool $isHomepage,
        $pageItem
    ): array {
        $graph = [];

        // Récupération des profils réseaux sociaux
        $socialLinks = self::getSocialProfiles($core);

        // Logo
        $logoUrl = '';
        $siteLogo = (string)$core->getConfigVal('siteLogo');
        if (!empty($siteLogo)) {
            $logoUrl = self::makeAbsoluteUrl($siteLogo, $siteUrl);
        } elseif (defined('THEMES')) {
            $theme = (string)$core->getConfigVal('theme');
            foreach (['assets/images/logoheader.png', 'logo.png', 'assets/images/logo.png'] as $cand) {
                if (file_exists(THEMES . $theme . '/' . $cand)) {
                    $logoUrl = $siteUrl . '/theme/' . $theme . '/' . $cand;
                    break;
                }
            }
        }

        // 1. Organization / ProfessionalService / LocalBusiness
        $schemaType = (string)$core->getConfigVal('seo_schema_type') ?: 'ProfessionalService';
        $organization = [
            '@type' => $schemaType,
            '@id' => $siteUrl . '/#organization',
            'name' => $siteName,
            'url' => $siteUrl . '/',
        ];
        if (!empty($socialLinks)) {
            $organization['sameAs'] = $socialLinks;
        }

        // Contact téléphonique et email
        $sitePhone = (string)$core->getConfigVal('sitePhone');
        if (!empty($sitePhone)) {
            $organization['telephone'] = $sitePhone;
        }
        $siteEmail = (string)$core->getConfigVal('siteEmail');
        if (!empty($siteEmail)) {
            $organization['email'] = $siteEmail;
        }

        // Localisation et zone géographique
        $city = (string)$core->getConfigVal('siteCity');
        $postalCode = (string)$core->getConfigVal('sitePostalCode');
        $country = (string)$core->getConfigVal('siteCountry') ?: 'FR';
        if (!empty($city) || !empty($postalCode)) {
            $organization['address'] = [
                '@type' => 'PostalAddress',
                'addressLocality' => $city ?: 'Bourges',
                'postalCode' => $postalCode ?: '18000',
                'addressCountry' => $country,
            ];
        }
        $areaServed = (string)$core->getConfigVal('siteAreaServed');
        if (!empty($areaServed)) {
            $organization['areaServed'] = $areaServed;
        }

        if (!empty($logoUrl)) {
            $organization['logo'] = [
                '@type' => 'ImageObject',
                '@id' => $siteUrl . '/#logo',
                'inLanguage' => $inLanguage,
                'url' => $logoUrl,
                'contentUrl' => $logoUrl,
                'caption' => $siteName,
            ];
            $organization['image'] = ['@id' => $siteUrl . '/#logo'];
        }
        $graph[] = $organization;

        // 2. WebSite
        $webSite = [
            '@type' => 'WebSite',
            '@id' => $siteUrl . '/#website',
            'url' => $siteUrl . '/',
            'name' => $siteName,
            'description' => $siteDesc,
            'publisher' => ['@id' => $siteUrl . '/#organization'],
            'inLanguage' => $inLanguage,
        ];
        $graph[] = $webSite;

        // 3. ImageObject primaire si présente
        if (!empty($ogImage)) {
            $imageObject = [
                '@type' => 'ImageObject',
                '@id' => $canonicalUrl . '#primaryimage',
                'inLanguage' => $inLanguage,
                'url' => $ogImage,
                'contentUrl' => $ogImage,
            ];
            $dims = self::getImageDimensions($ogImage, $siteUrl);
            if (!empty($dims['width']) && !empty($dims['height'])) {
                $imageObject['width'] = (int)$dims['width'];
                $imageObject['height'] = (int)$dims['height'];
            }
            $graph[] = $imageObject;
        }

        // 4. WebPage
        $webPage = [
            '@type' => 'WebPage',
            '@id' => $canonicalUrl . '#webpage',
            'url' => $canonicalUrl,
            'name' => $pageTitle,
            'isPartOf' => ['@id' => $siteUrl . '/#website'],
            'about' => ['@id' => $siteUrl . '/#organization'],
            'description' => $pageDesc,
            'breadcrumb' => ['@id' => $canonicalUrl . '#breadcrumb'],
            'inLanguage' => $inLanguage,
        ];
        if (!empty($ogImage)) {
            $webPage['primaryImageOfPage'] = ['@id' => $canonicalUrl . '#primaryimage'];
        }
        $graph[] = $webPage;

        // 5. BreadcrumbList
        $breadcrumbElements = [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Accueil',
                'item' => $siteUrl . '/',
            ]
        ];
        if (!$isHomepage) {
            $itemName = ($pageItem instanceof PageItem) ? $pageItem->getName() : $pageTitle;
            $breadcrumbElements[] = [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => $itemName,
                'item' => $canonicalUrl,
            ];
        }
        $graph[] = [
            '@type' => 'BreadcrumbList',
            '@id' => $canonicalUrl . '#breadcrumb',
            'itemListElement' => $breadcrumbElements,
        ];

        return [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];
    }

    /**
     * Récupère les profils réseaux sociaux configurés
     */
    public static function getSocialProfiles(Core $core): array
    {
        $links = [];
        $hookData = $core->callHook('seo:social-links', ['links' => []]);
        if (!empty($hookData['links']) && is_array($hookData['links'])) {
            $links = array_merge($links, $hookData['links']);
        }
        if (defined('DATA')) {
            $seoConfigFile = DATA . 'plugin/seo/config.json';
            if (file_exists($seoConfigFile)) {
                $seoConfig = json_decode((string)file_get_contents($seoConfigFile), true);
                if (is_array($seoConfig)) {
                    foreach (['facebook', 'instagram', 'linkedin', 'twitter', 'youtube', 'github', 'mastodon', 'tiktok', 'pinterest'] as $key) {
                        if (!empty($seoConfig[$key]) && filter_var($seoConfig[$key], FILTER_VALIDATE_URL)) {
                            $links[] = $seoConfig[$key];
                        }
                    }
                }
            }
        }
        return array_values(array_unique(array_filter($links)));
    }

    /**
     * Calcule la taille d'une image locale
     */
    public static function getImageDimensions(string $imageUrl, string $siteUrl): array
    {
        $dims = ['width' => null, 'height' => null];
        if (empty($imageUrl)) {
            return $dims;
        }

        $localPath = '';
        if (str_starts_with($imageUrl, $siteUrl)) {
            $rel = substr($imageUrl, strlen($siteUrl));
            if (defined('ROOT')) {
                $localPath = ROOT . ltrim($rel, '/');
            }
        } elseif (!str_starts_with($imageUrl, 'http://') && !str_starts_with($imageUrl, 'https://')) {
            if (defined('ROOT')) {
                $localPath = ROOT . ltrim($imageUrl, '/');
            }
        }

        if (!empty($localPath) && file_exists($localPath)) {
            $size = @getimagesize($localPath);
            if (is_array($size)) {
                $dims['width'] = $size[0];
                $dims['height'] = $size[1];
            }
        }
        return $dims;
    }

    /**
     * Convertit une URL relative en URL absolue
     */
    public static function makeAbsoluteUrl(string $url, string $siteUrl): string
    {
        $clean = trim($url);
        if (empty($clean)) {
            return '';
        }
        if (str_starts_with($clean, 'http://') || str_starts_with($clean, 'https://')) {
            return $clean;
        }
        return $siteUrl . '/' . ltrim($clean, '/');
    }

    /**
     * Vérifie si une balise existe déjà dans les métas
     */
    public static function metaExists(string $haystack, string $needle): bool
    {
        return stripos($haystack, $needle) !== false;
    }
}
