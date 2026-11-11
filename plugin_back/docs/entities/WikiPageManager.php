<?php

namespace Docs\Entities;

use Core\Core;
use Core\Plugin\PluginsManager;
use Core\Router\Router;
use Utils\Util;

/**
 * @copyright (C) 2022, 299Ko, based on code (2010-2021) 99ko https://github.com/99kocms/
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Jonathan Coulet <j.coulet@gmail.com>
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * @author Maxime Blanc <maximeblanc@flexcb.fr>
 * @author Frédéric Kaplon <frederic.kaplon@me.com>
 * @author Florent Fortat <florent.fortat@maxgun.fr>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class WikiPageManager
{
    private array $items = [];
    private array $history = [];
    private int $nbItemsToPublic;

    public function __construct()
    {
        $this->loadItems();
        $this->loadHistory();
    }

    private function loadItems()
    {
        $categoriesManager = new WikiCategoriesManager();
        $newsList = WikiPage::query()->orderBy('position', 'ASC')->orderBy('date', 'DESC')->get();
        
        $this->items = [];
        $publishedCount = 0;
        
        foreach ($newsList as $page) {
            /** @var WikiPage $page */
            $categories = [];
            foreach ($categoriesManager->getCategories() as $cat) {
                if (in_array($page->getId(), $cat->items)) {
                    $categories['categories'][$cat->id] = [
                        'label' => $cat->label,
                        'url' => Router::getInstance()->generate('docs-category', ['name' => Util::strToUrl($cat->label), 'id' => $cat->id]),
                        'id' => $cat->id
                    ];
                }
            }
            $page->categories = $categories;
            $this->items[] = $page;
            if ($page->getDraft() === "0") {
                $publishedCount++;
            }
        }
        $this->nbItemsToPublic = $publishedCount;
    }

    /**
     * @return WikiPage[]
     */
    public function getItems() {
        return $this->items;
    }

    /**
     * @param mixed $id
     * @return WikiPage|null
     */
    public function create($id) {
        return WikiPage::findPK($id);
    }

    public function saveWikiPage(WikiPage $obj, $changeDescription = '')
    {
        $id = $obj->getId();
        $isNew = ($id < 1);
        
        if ($isNew) {
            $obj->setVersion(1);
            $obj->setLastModified(date('Y-m-d H:i:s'));
            $saved = $obj->save();
            if ($saved) {
                $this->saveToHistory($obj, $changeDescription ?: 'Création de la page');
            }
        } else {
            $oldPage = $this->create($id);
            if ($oldPage) {
                $this->saveToHistory($oldPage, $changeDescription);
            }
            $obj->incrementVersion();
            $saved = $obj->save();
        }
        
        if ($saved) {
            $this->loadItems();
            $this->recordActivity($obj, $isNew ? 'add' : 'edit');
        }
        
        return $saved;
    }

    public function delWikiPage(WikiPage $obj): bool
    {
        if ($obj->delete()) {
            $this->recordActivity($obj, 'delete');
            $this->loadItems();
            return true;
        }
        return false;
    }

    public function count() {
        return WikiPage::query()->count();
    }

    public function getNbItemsToPublic() {
        return $this->nbItemsToPublic;
    }

    public function rss() {
        $core = Core::getInstance();
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<rss version="2.0">';
        $xml .= '<channel>';
        $xml .= ' <title>' . $core->getConfigVal('siteName') . ' - ' . PluginsManager::getPluginConfVal('docs', 'label') . '</title>';
        $xml .= ' <link>' . $core->getConfigVal('siteUrl') . '/</link>';
        $xml .= ' <description>' . $core->getConfigVal('siteDescription') . '</description>';
        $xml .= ' <language>' . $core->getConfigVal('siteLang') . '</language>';
        foreach ($this->getItems() as $k => $v)
            if ($v->getDraft() === "0") {
                $xml .= '<item>';
                $xml .= '<title><![CDATA[' . $v->getName() . ']]></title>';
                $xml .= '<link>' . $core->getConfigVal('siteUrl') . Router::getInstance()->generate('docs-read', ['name' => $v->getSlug(), 'id' => $v->getId()]) . '</link>';
                $xml .= '<pubDate>' . (date("D, d M Y H:i:s O", strtotime($v->getDate()))) . '</pubDate>';
                $xml .= '<description><![CDATA[' . $v->getContent() . ']]></description>';
                $xml .= '</item>';
            }
        $xml .= '</channel>';
        $xml .= '</rss>';
        header('Cache-Control: must-revalidate, pre-check=0, post-check=0, max-age=0');
        header('Content-Type: application/rss+xml; charset=utf-8');
        echo $xml;
        die();
    }

    public function savePagesOrder(array $order) {
        if (empty($order)) return;
        
        foreach ($order as $position => $pageId) {
            /** @var WikiPage $page */
            $page = $this->create($pageId);
            if ($page) {
                $page->setPosition($position + 1);
                $page->save();
            }
        }
        $this->loadItems();
    }

    private function loadHistory() {
        $this->history = WikiPageHistory::query()->orderBy('modifiedAt', 'DESC')->get();
    }

    private function saveToHistory($page, $changeDescription = '') {
        $historyEntry = new WikiPageHistory([
            'pageId' => $page->getId(),
            'version' => $page->getVersion(),
            'name' => $page->getName(),
            'content' => $page->getContent(),
            'intro' => $page->getIntro(),
            'seoDesc' => $page->getSEODesc(),
            'modifiedBy' => $page->getModifiedBy(),
            'modifiedAt' => $page->getLastModified(),
            'changeDescription' => $changeDescription
        ]);
        
        if ($historyEntry->save()) {
            $this->loadHistory();
        }
    }

    public function getPageHistory($pageId) {
        return WikiPageHistory::query()->where('pageId', '=', $pageId)->orderBy('version', 'DESC')->get();
    }

    public function getPageVersion($pageId, $version) {
        $entry = WikiPageHistory::query()
            ->where('pageId', '=', $pageId)
            ->where('version', '=', $version)
            ->first();
            
        if ($entry) {
            return new WikiPage([
                'id' => $entry->getPageId(),
                'name' => $entry->getName(),
                'content' => html_entity_decode($entry->getContent(), ENT_QUOTES, 'UTF-8'),
                'intro' => html_entity_decode($entry->getIntro(), ENT_QUOTES, 'UTF-8'),
                'seoDesc' => $entry->getSEODesc(),
                'date' => $entry->getModifiedAt(),
                'draft' => '0',
                'img' => '',
                'version' => $entry->getVersion(),
                'lastModified' => $entry->getModifiedAt(),
                'modifiedBy' => $entry->getModifiedBy(),
                'slug' => Util::strToUrl($entry->getName())
            ]);
        }
        return false;
    }

    private function recordActivity($page, $action) {
        $activityManager = new WikiActivityManager();
        
        $categoryName = '';
        $categoriesManager = new WikiCategoriesManager();
        foreach ($categoriesManager->getCategories() as $cat) {
            if (in_array($page->getId(), $cat->items)) {
                $categoryName = $cat->label;
                break;
            }
        }
        
        $activityManager->addActivity($action, $page->getName(), $categoryName, $page->getId());
    }
}