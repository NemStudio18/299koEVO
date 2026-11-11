<?php

namespace LocalFeed\Hooks;

defined('ROOT') or exit('Access denied!');

class LocalFeedHooks
{
    /**
     * Hook endMainNavigation — Ajoute le lien "Vie locale" dans le menu principal
     */
    public static function onEndMainNavigation(string $param = ''): string
    {
        $link = '<li class="nav-item nav-local-feed">'
              . '<a href="/mur" class="nav-link nav-link-feed" title="Fil d\'actualité et vie locale en direct">'
              . 'Vie locale'
              . '</a></li>';
        
        return $param . $link;
    }
}
