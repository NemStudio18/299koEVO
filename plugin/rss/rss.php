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
    }
}

