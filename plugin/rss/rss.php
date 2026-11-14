<?php

namespace Rss {

    defined('ROOT') or exit('Access denied!');

    class Rss
    {
        public static function onEndFrontHead($param = ''): string
        {
            $pluginManager = \Core\Plugin\PluginsManager::getInstance();
            $plugin = $pluginManager->getPlugin('rss');
            if (!$plugin || !$plugin->getConfigVal('activate')) {
                return (string)$param;
            }

            $core = \Core\Core::getInstance();
            $siteName = $core->getConfigVal('siteName') ?: '299koEVO';
            $rssUrl = \Utils\Util::urlBuild('rss.xml');

            $html = '<link rel="alternate" type="application/rss+xml" title="' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . ' - Flux RSS" href="' . $rssUrl . '">' . "\n";
            return (string)$param . $html;
        }

        public static function onPublicContent($content = ''): string
        {
            global $runPlugin;
            if (!$runPlugin) {
                return (string)$content;
            }
            $pluginManager = \Core\Plugin\PluginsManager::getInstance();
            $rssPlugin = $pluginManager->getPlugin('rss');
            if (!$rssPlugin || !$rssPlugin->getConfigVal('activate')) {
                return (string)$content;
            }

            $currentSlug = $runPlugin->getName();
            // Check if this plugin is enabled for RSS in settings
            if (!$rssPlugin->getConfigVal('enable_' . $currentSlug)) {
                return (string)$content;
            }

            $rssUrl = \Utils\Util::urlBuild('rss/' . $currentSlug);

            $badgeHtml = '<div class="rss-channel-badge-container">'
                . '<a href="' . htmlspecialchars($rssUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener" class="rss-channel-badge" title="S\'abonner au flux RSS de cette rubrique">'
                . '<i class="fa-solid fa-rss" aria-hidden="true"></i> <span>Flux RSS</span>'
                . '</a></div>';

            return $badgeHtml . (string)$content;
        }
    }
}

