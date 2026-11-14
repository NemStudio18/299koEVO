<?php

namespace Rss\Controllers;

use Core\Controllers\PublicController as BasePublicController;
use Rss\Services\RssProviderManager;

defined('ROOT') or exit('Access denied!');

class RssPublicController extends BasePublicController
{
    public function feed()
    {
        $this->renderRssFeed(null);
    }

    public function channel($channel)
    {
        $this->renderRssFeed(trim(strtolower((string)$channel)));
    }

    private function renderRssFeed(?string $channelFilter)
    {
        $pluginManager = \Core\Plugin\PluginsManager::getInstance();
        $plugin = $pluginManager->getPlugin('rss');
        $config = ($plugin && method_exists($plugin, 'getConfig')) ? $plugin->getConfig() : [];

        $core = \Core\Core::getInstance();
        $siteUrl = rtrim($core->getConfigVal('siteUrl') ?: 'http://localhost', '/');
        $siteName = $core->getConfigVal('siteName') ?: '299koEVO';

        $feedTitle = $config['feed_title'] ?? 'Flux d\'actualités';
        $feedDesc = $config['feed_description'] ?? 'Dernières publications du site';
        $maxItems = max(5, min(100, (int)($config['max_items'] ?? 30)));

        $providers = RssProviderManager::getAvailableProviders();
        $items = [];

        if ($channelFilter !== null) {
            // Flux d'un canal / plugin précis
            if (isset($providers[$channelFilter])) {
                $feedTitle = $providers[$channelFilter]['name'];
                $feedDesc = $providers[$channelFilter]['description'];
                $items = RssProviderManager::getItemsForProvider($channelFilter, $siteUrl);
            }
        } else {
            // Flux global aggrégé : inclut tous les modules cochés dans la config
            foreach ($providers as $slug => $provider) {
                if (!empty($config['enable_' . $slug])) {
                    $modItems = RssProviderManager::getItemsForProvider($slug, $siteUrl);
                    $items = array_merge($items, $modItems);
                }
            }
        }

        // Tri chronologique décroissant (les plus récents en premier)
        usort($items, function($a, $b) {
            return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
        });

        // Limiter le nombre d'éléments
        $items = array_slice($items, 0, $maxItems);

        // Construction du XML
        $selfUrl = $siteUrl . ($channelFilter ? '/rss/' . $channelFilter : '/rss.xml');
        $lastBuildDate = !empty($items) ? gmdate(DATE_RSS, $items[0]['timestamp']) : gmdate(DATE_RSS);

        header('Content-Type: application/rss+xml; charset=utf-8');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        echo "  <channel>\n";
        echo "    <title>" . htmlspecialchars($siteName . ' - ' . $feedTitle, ENT_XML1, 'UTF-8') . "</title>\n";
        echo "    <link>" . htmlspecialchars($siteUrl, ENT_XML1, 'UTF-8') . "</link>\n";
        echo "    <description>" . htmlspecialchars($feedDesc, ENT_XML1, 'UTF-8') . "</description>\n";
        echo "    <language>fr-FR</language>\n";
        echo "    <lastBuildDate>" . $lastBuildDate . "</lastBuildDate>\n";
        echo "    <atom:link href=\"" . htmlspecialchars($selfUrl, ENT_XML1, 'UTF-8') . "\" rel=\"self\" type=\"application/rss+xml\" />\n";

        foreach ($items as $item) {
            $pubDate = gmdate(DATE_RSS, $item['timestamp']);
            echo "    <item>\n";
            echo "      <title>" . htmlspecialchars($item['title'], ENT_XML1, 'UTF-8') . "</title>\n";
            echo "      <link>" . htmlspecialchars($item['link'], ENT_XML1, 'UTF-8') . "</link>\n";
            echo "      <guid isPermaLink=\"true\">" . htmlspecialchars($item['link'], ENT_XML1, 'UTF-8') . "</guid>\n";
            echo "      <pubDate>" . $pubDate . "</pubDate>\n";
            if (!empty($item['category'])) {
                echo "      <category>" . htmlspecialchars($item['category'], ENT_XML1, 'UTF-8') . "</category>\n";
            }
            if (!empty($item['description'])) {
                echo "      <description><![CDATA[" . $item['description'] . "]]></description>\n";
            }
            echo "    </item>\n";
        }

        echo "  </channel>\n";
        echo "</rss>\n";
        exit;
    }
}
