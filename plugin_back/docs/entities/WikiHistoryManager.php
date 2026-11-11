<?php

namespace Docs\Entities;

use Utils\Util;

defined('ROOT') or exit('Access denied!');

class WikiHistoryManager
{
    private $history = [];

    public function __construct() {
        $this->loadHistory();
    }

    private function loadHistory() {
        $this->history = WikiPageHistory::query()->orderBy('modifiedAt', 'DESC')->get();
    }

    public function addHistoryEntry($page, $changeDescription = '') {
        // Vérifier si cette version existe déjà dans l'historique
        $exists = WikiPageHistory::query()
            ->where('pageId', '=', $page->getId())
            ->where('version', '=', $page->getVersion())
            ->first();
            
        if ($exists) {
            return true; // Doublon évité
        }
        
        /** @var WikiPage $page */
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
        
        $saved = $historyEntry->save();
        if ($saved) {
            $this->loadHistory();
        }
        return $saved;
    }

    /**
     * @param int $pageId
     * @return WikiPageHistory[]
     */
    public function getPageHistory($pageId) {
        return WikiPageHistory::query()
            ->where('pageId', '=', $pageId)
            ->orderBy('version', 'DESC')
            ->get();
    }

    /**
     * @param int $pageId
     * @param int $version
     * @return WikiPage|false
     */
    public function getPageVersion($pageId, $version) {
        /** @var WikiPageHistory $entry */
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
}