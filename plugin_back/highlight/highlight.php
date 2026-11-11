<?php

namespace Highlight {

use Core\Plugin\PluginsManager;

defined('ROOT') or exit('No direct script access allowed');

class Hooks
{
    public static function install(): void
    {
    }

    public static function getThemeCSSUrl(string $theme): string
    {
        $t = array_key_exists($theme, self::getThemes()) ? $theme : 'default';
        return 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.6.0/styles/' . $t . '.min.css';
    }

    public static function getThemes(): array
    {
        return [
            'default' => 'Default',
            'a11y-dark' => 'A 11 Y Dark',
            'a11y-light' => 'A 11 Y Light',
            'arta' => 'Arta',
            'github' => 'GitHub',
            'github-dark' => 'GitHub Dark',
            'monokai-sublime' => 'Monokai Sublime',
            'vs' => 'VS',
            'vs2015' => 'VS 2015',
        ];
    }

    public static function endFrontHead(): void
    {
        $plugin = PluginsManager::getInstance()->getPlugin('highlight');
        if (!$plugin) {
            return;
        }
        echo '<link rel="stylesheet" href="' . self::getThemeCSSUrl($plugin->getConfigVal('theme')) . '" type="text/css"/>';
        echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.6.0/highlight.min.js"></script>';
    }

    public static function endFrontBody(): void
    {
        echo '<script>hljs.highlightAll();</script>';
    }
}

}

namespace {

    function highlightInstall(): void
    {
        \Highlight\Hooks::install();
    }

}