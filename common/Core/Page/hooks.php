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
                . '<li><a href="https://299ko.flexcb.fr">'. Lang::get('page.links-site') .'</a></li>'
                . '<li><a href="https://299ko.flexcb.fr/docs">'. Lang::get('page.links-doc') .'</a></li></ul>');
        $page->save($pageItem);
    }
}

## Hooks

function pageEndFrontHead() {
    global $runPlugin, $pageItem;
    $core = Core::getInstance();
    $pluginToCall = $core->getPluginToCall();
    if ($pluginToCall === 'page') {
        if ($pageItem instanceof PageItem && class_exists('\Core\Translation\TranslationManager')) {
            $tm = \Core\Translation\TranslationManager::getInstance();
            $pageService = new Page();
            $siteUrl = rtrim((string) $core->getConfigVal('siteUrl'), '/');
            $hreflangHtml = $tm->renderHreflangTags('page', (string) $pageItem->getId(), static function (string $loc, string $targetId) use ($pageService, $tm, $siteUrl): string {
                $targetPage = $pageService->create($targetId);
                if (!$targetPage instanceof PageItem) {
                    return '';
                }
                $relPath = str_replace($siteUrl, '', $pageService->makeUrl($targetPage));
                $locPath = $tm->buildLocalizedUrl($relPath ?: '/', $loc);
                return $siteUrl . $locPath;
            });
            if ($hreflangHtml !== '') {
                echo "\n" . $hreflangHtml;
            }
        }
    }
}

/**
 * Hook pour la recherche globale : cherche dans les pages statiques
 */
function pageSearchResultsHook(array $data): array
{
    $query = $data['query'];
    $results = [];
    try {
        $pageManager = new Page();
        $items = $pageManager->getItems();

        foreach ($items as $item) {
            // Ne pas indexer les liens externes ou les plugins (déjà gérés par leurs propres hooks)
            if ($item->targetIs() !== 'page') continue;
            if ($item->getIsHidden()) continue;

            if (stripos($item->getName(), $query) !== false || stripos($item->getContent(), $query) !== false) {
                $results[] = [
                    'title' => $item->getName(),
                    'excerpt' => substr(strip_tags($item->getContent()), 0, 150) . '...',
                    'url' => (new Page())->makeUrl($item),
                    'icon' => 'fa-solid fa-file-lines',
                    'source' => 'page',
                    'source_label' => 'Page',
                    'date' => '',
                    'date_formatted' => '',
                    'score' => (stripos($item->getName(), $query) !== false ? 10 : 0) + (stripos($item->getContent(), $query) !== false ? 5 : 0)
                ];
            }
        }
        $data['results'] = array_merge($data['results'], $results);
    } catch (\Exception $e) {
        // Ignorer
    }
    return $data;
}

