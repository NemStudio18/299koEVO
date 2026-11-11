<?php

use Seo\Entities\SocialConfigManager;

/**
 * @copyright (C) 2022, 299Ko, based on code (2010-2021) 99ko https://github.com/99kocms/
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Jonathan Coulet <j.coulet@gmail.com>
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * @author Frédéric Kaplon <frederic.kaplon@me.com>
 * @author Florent Fortat <florent.fortat@maxgun.fr>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

require_once __DIR__ . '/entities/SocialConfigManager.php';
require_once __DIR__ . '/entities/Redirect.php';

SocialConfigManager::getAll();

/**
 * Gère les redirections enregistrées avant le dispatching du routeur
 */
function seoHandleRedirections(): void
{
    // Ne pas rediriger en mode admin
    if (defined('ADMIN_MODE') && ADMIN_MODE) {
        return;
    }

    $uri = $_SERVER['REQUEST_URI'] ?? '';
    // Retirer le base path si présent
    $uri = preg_replace('/^' . preg_quote(BASE_PATH, '/') . '/', '', $uri);
    // Retirer les query params pour le match d'URL propre
    $urlPath = parse_url($uri, PHP_URL_PATH);
    
    if (!$urlPath) return;

    $redirect = \Seo\Entities\Redirect::findMatch($urlPath);
    if ($redirect) {
        $redirect->incrementHits();
        $code = $redirect->code ?: 301;
        header("Location: " . $redirect->new_url, true, $code);
        exit();
    }
}

// Hook beforeExecution is now declared in param/hooks.json

function seoInstall() {
    SocialConfigManager::getAll();
}

function seoEndFrontHead() {
    seoPrintAnalytics();
    seoPrintMetaTags();
}

function seoPrintAnalytics(): void
{
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('seo');
    $tracking = $plugin->getConfigVal('trackingId');
    if (!$tracking) {
        return;
    }

    echo "<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', '" . $tracking . "', 'auto');
  ga('send', 'pageview');

</script>";

    $verification = $plugin->getConfigVal('wt');
    if ($verification) {
        echo '<meta name="google-site-verification" content="' . htmlspecialchars($verification) . '" />';
    }
}

function seoPrintMetaTags(): void
{
    global $page, $runPlugin;
    if ($runPlugin) {
        return;
    }
    if (!$page || !method_exists($page, 'getName')) {
        return;
    }

    $core = \Core\Core::getInstance();
    $title = htmlspecialchars($page->getMetaTitle() ?: $page->getName());
    $desc = htmlspecialchars(substr(strip_tags($page->getMetaDescription() ?: ''), 0, 160));
    $permalink = method_exists($page, 'getPermalink') ? $page->getPermalink() : '';
    $url = htmlspecialchars(rtrim($core->getConfigVal('siteUrl'), '/') . $permalink);

    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('seo');
    $image = '';
    if (method_exists($page, 'getFeaturedImage')) {
        $image = $page->getFeaturedImage();
    } elseif (method_exists($page, 'getImg')) {
        $image = $page->getImg();
    }
    if (!$image) {
        $image = $plugin->getConfigVal('defaultOgImage');
    }
    if (!$image) {
        $image = rtrim($core->getConfigVal('themeUrl'), '/') . '/img/default.jpg';
    }
    $image = htmlspecialchars($image);

    echo <<<HTML
<!-- SEO meta -->
<meta property="og:title" content="$title">
<meta property="og:description" content="$desc">
<meta property="og:type" content="article">
<meta property="og:url" content="$url">
<meta property="og:image" content="$image">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="$title">
<meta name="twitter:description" content="$desc">
<meta name="twitter:image" content="$image">
<link rel="canonical" href="$url">
HTML;

    $languages = SocialConfigManager::getLanguages();
    if (!empty($languages)) {
        foreach ($languages as $locale) {
            $href = htmlspecialchars(rtrim($core->getConfigVal('siteUrl'), '/') . '/' . trim($locale, '/') . $permalink);
            echo '<link rel="alternate" hreflang="' . htmlspecialchars($locale) . '" href="' . $href . '">';
        }
    }
}

function seoBeforeRunPlugin(): void
{
    global $runPlugin;
    $enabled = [];
    if ($runPlugin && $runPlugin->getName() === 'blog') {
        $enabled = SocialConfigManager::getEnabledNetworks();
    }
    \Template\Template::addGlobal('SeoShareConfig', $enabled);
}

function seoBlogAfterSave(int $postId, array $postData)
{
    foreach (['facebook', 'x', 'linkedin'] as $network) {
        try {
            SocialConfigManager::postToNetwork($network, $postData);
        } catch (\Throwable $e) {
            continue;
        }
    }
    return true;
}

function seoGenerateSitemap(): int
{
    $items = ['/', '/recherche'];
    if (class_exists('\newsManager')) {
        $news = (new \newsManager())->getItems();
        foreach ($news as $item) {
            if (!$item->getDraft()) {
                $items[] = $item->getUrl();
            }
        }
    }

    // Call hook to allow other plugins to add URLs
    $items = \Core\Core::getInstance()->callHook('seo:collect-sitemap-urls', $items);

    $base = rtrim((string)\Core\Core::getInstance()->getConfigVal('siteUrl'), '/');
    $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    $seen = [];
    foreach ($items as $entry) {
        $path = '';
        $lastmod = null;
        if (is_array($entry)) {
            $path = $entry['loc'] ?? '';
            $lastmod = !empty($entry['lastmod']) ? substr($entry['lastmod'], 0, 10) : null;
        } else {
            $path = (string)$entry;
        }
        $path = '/' . ltrim($path, '/');
        if (isset($seen[$path]) || $path === '') {
            continue;
        }
        $seen[$path] = true;

        $loc = htmlspecialchars($base . $path, ENT_QUOTES, 'UTF-8');
        $xml .= "<url><loc>{$loc}</loc>";
        if ($lastmod && preg_match('/^\d{4}-\d{2}-\d{2}$/', $lastmod)) {
            $xml .= "<lastmod>{$lastmod}</lastmod>";
        }
        $xml .= "</url>";
    }
    $xml .= '</urlset>';

    return file_put_contents(ROOT . 'sitemap.xml', $xml) ? 0 : 1;
}

function seoEndFrontBody() {
    echo '<div id="seo_social_float"><ul>';
    echo seoGetSocialIcons('<li>', '</li>');
    echo '</ul></div>';
}

function seoMainNavigation() {
    echo seoGetSocialIcons('<li class="seo_element">', '</li>');
}

function seoFooter() {
    echo '<div id="seo_social"><ul>';
    echo seoGetSocialIcons('<li>', '</li>');
    echo '</ul></div>';
}

function seoGetSocialIcons($before = '', $after = '') {
    $social = seoGetSocialVars();
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('seo');
    $str = "";

    foreach ($social as $k => $v) {
        $tConfig = $plugin->getConfigVal($v);
        if ($tConfig && $tConfig !== '') {
            $str .= $before . '<a target="_blank" title="'. \Core\Lang::get('seo.follow-on', $k) . '" href="' . $tConfig . '"><i class="fa-brands fa-' . $v . '"></i></a>' . $after;
        }
    }
    return $str;
}

function seoGetSocialVars() {
    return [
        'Facebook' => 'facebook',
        'Twitter' => 'twitter',
        'YouTube' => 'youtube',
        'Instagram' => 'instagram',
        'TikTok' => 'tiktok',
        'Pinterest' => 'pinterest',
        'Linkedin' => 'linkedin',
        'Viadeo' => 'viadeo',
        'GitHub' => 'github',
        'Gitlab' => 'gitlab',
        "Mastodon" => 'mastodon',
        "Twitch" => 'twitch',
        "Discord" => 'discord',
        "Codepen" => 'codepen',
        "Tumblr" => 'tumblr',
    ];
}

// Hooks are now declared in param/hooks.json
