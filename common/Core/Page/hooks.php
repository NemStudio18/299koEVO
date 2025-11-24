<?php

namespace Core\Page;

use Core\Page\Page;
use Core\Page\PageItem;
use Core\Lang;
use Core\Core;
use Core\Plugin\PluginsManager;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 *
 * Hooks pour le module Page du core
 */
defined('ROOT') OR exit('Access denied!');

## Fonction d'installation

function pageInstall() {
    $page = new Page();
    if (count($page->getItems()) < 1) {
        $pageItem = new PageItem();
        $pageItem->setName(Lang::get('page.home'));
        $pageItem->setPosition(1);
        $pageItem->setIsHomepage(1);
        $pageItem->setContent('<p>'. Lang::get('page.home-content').'</p>');
        $pageItem->setIsHidden(0);
        $page->save($pageItem);
        $page = new Page();
        $pageItem = new PageItem();
        $pageItem->setName(Lang::get('page.links'));
        $pageItem->setPosition(2);
        $pageItem->setContent('<ul><li><a href="https://github.com/299Ko">'. Lang::get('page.links-git') .'</a></li>'
                . '<li><a href="https://facebook.com/299kocms/">'. Lang::get('page.links-fb') .'</a></li>'
                . '<li><a href="https://twitter.com/299kocms">'. Lang::get('page.links-twt') .'</a></li>'
                . '<li><a href="https://299ko.ovh">'. Lang::get('page.links-site') .'</a></li>'
                . '<li><a href="https://docs.299ko.ovh">'. Lang::get('page.links-doc') .'</a></li></ul>');
        $page->save($pageItem);
    }
}

## Hooks

function pageEndFrontHead() {
    global $runPlugin;
    $core = Core::getInstance();
    // Check if current page is a page module (core or legacy plugin)
    $pluginToCall = $core->getPluginToCall();
    if ($pluginToCall === 'page') {
        global $pageItem;
        if (isset($pageItem) && $pageItem && $pageItem->getNoIndex()) {
            echo '<meta name="robots" content="noindex"><meta name="googlebot" content="noindex">';
        }
        $pluginsManager = PluginsManager::getInstance();
        if (isset($pageItem) && $pageItem && $pluginsManager->isActivePlugin('galerie') && \galerie::searchByfileName($pageItem->getImg()))
            echo '<meta property="og:image" content="' . $core->getConfigVal('siteUrl') . '/' . str_replace('./', '', UPLOAD) . 'galerie/' . $pageItem->getImg() . '" />';
    }
}

