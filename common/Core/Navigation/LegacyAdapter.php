<?php

namespace Core\Navigation;

use Core\Core;
use Core\Page\Page;
use Utils\Util;

/**
 * Adaptateur pour compatibilité avec l'ancien système
 * 
 * Permet la transition progressive vers le nouveau système
 */
defined('ROOT') or exit('Access denied!');

class LegacyAdapter
{
    /**
     * Convertit l'ancien format de navigation vers NavigationTree
     */
    public static function convertLegacyNavigation(array $legacyItems): array
    {
        $converted = [];

        foreach ($legacyItems as $item) {
            $converted[] = [
                'id' => 'legacy.' . ($item['id'] ?? uniqid()),
                'label' => $item['label'] ?? '',
                'target' => $item['target'] ?? '#',
                'targetAttr' => $item['targetAttribut'] ?? '_self',
                'cssClass' => $item['cssClass'] ?? '',
                'position' => $item['id'] ?? 100,
                'source' => 'legacy',
                'parentId' => $item['parent'] > 0 ? 'legacy.' . $item['parent'] : null,
                'visibility' => ['public' => true],
                'enabled' => true,
            ];
        }

        return $converted;
    }

    /**
     * Migre les pages existantes vers navigation.json
     */
    public static function migratePagesToNavigation(): bool
    {
        try {
            $page = new Page();
            $items = $page->getItems();
            $navigationFile = DATA_CORE . 'navigation.json';

            if (!file_exists($navigationFile)) {
                return false;
            }

            $data = \Utils\Util::readJsonFile($navigationFile, true);
            if (!is_array($data)) {
                $data = ['version' => '1.0', 'items' => []];
            }

            $existingIds = [];
            foreach ($data['items'] ?? [] as $item) {
                $existingIds[] = $item['id'] ?? '';
            }

            foreach ($items as $pageItem) {
                if ($pageItem->getIsHidden()) {
                    continue;
                }

                $id = 'core.page.' . $pageItem->getId();
                if (in_array($id, $existingIds)) {
                    continue; // Déjà migré
                }

                $url = ($pageItem->targetIs() == 'parent') 
                    ? $pageItem->getTarget() 
                    : $page->makeUrl($pageItem);

                $data['items'][] = [
                    'id' => $id,
                    'label' => $pageItem->getName(),
                    'target' => $url,
                    'targetAttr' => $pageItem->getTargetAttr() ?? '_self',
                    'cssClass' => $pageItem->getCssClass() ?? '',
                    'position' => (int)$pageItem->getPosition(),
                    'source' => 'core',
                    'parentId' => $pageItem->getParent() > 0 ? 'core.page.' . $pageItem->getParent() : null,
                    'visibility' => ['public' => true],
                    'enabled' => true,
                ];
            }

            return \Utils\Util::writeJsonFile($navigationFile, $data);
        } catch (\Exception $e) {
            Core::getInstance()->getLogger()->error("LegacyAdapter: Migration failed: " . $e->getMessage());
            return false;
        }
    }
}

