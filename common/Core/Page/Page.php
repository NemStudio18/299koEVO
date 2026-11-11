<?php

namespace Core\Page;

use Core\Page\PageItem;
use Core\Plugin\PluginsManager;
use Core\Core;
use Utils\Util;
use Core\Router\Router;
use Core\Lang;

/**
 * Gestion des pages et de la navigation intégrée au core.
 * Système de stockage v2.0 (Versionné, Atomique, Backups).
 */
defined('ROOT') or exit('Access denied!');

class Page
{
    public const SCHEMA_VERSION = '2.0';
    public const MAX_BACKUPS = 5;

    private $items = [];
    private string $pagesFile;
    private string $legacyPagesFile;

    public function __construct()
    {
        $this->items = $this->loadPages();
    }

    /**
     * Charge les pages via l'ActiveRecord (Supporte JSON/SQL transparentement).
     */
    private function loadPages()
    {
        return PageItem::queryBuilder()->orderBy('position', 'asc')->get();
    }

    public function save($obj)
    {
        if (!($obj instanceof PageItem)) {
            return false;
        }

        // Si c'est une nouvelle homepage, on reset les autres en mémoire avant de sauver
        if ($obj->getIsHomepage() > 0) {
            $this->initIshomepageVal($obj->getId());
            // On doit sauver les autres objets si on veut que le changement soit SQL-consistent
            // mais l'ActiveRecord le fera via sa logique si on l'appelle proprement.
            // Pour simplifier ici, on sauve au moins l'objet courant.
        }

        $success = $obj->save();
        if ($success) {
            $this->items = $this->loadPages();
        }
        return $success;
    }

    public function del($obj)
    {
        if ($obj->getIsHomepage() < 1 && $this->count() > 1) {
            $success = $obj->delete();
            if ($success) {
                $this->items = $this->loadPages();
            }
            return $success;
        }
        return false;
    }

    public function savePositions(array $positionMap): bool
    {
        $success = true;
        foreach ($this->items as $item) {
            if (isset($positionMap[$item->getId()])) {
                $data = $positionMap[$item->getId()];
                if (is_array($data)) {
                    if (isset($data['position'])) $item->setPosition($data['position']);
                    if (isset($data['parent'])) $item->setParent($data['parent']);
                } else {
                    $item->setPosition($data);
                }
                if (!$item->save()) {
                    $success = false;
                }
            }
        }
        $this->items = $this->loadPages();
        return $success;
    }

    public function cleanupDuplicatePluginItems($logger = null): void
    {
        if ($logger === null) {
            $logger = Core::getInstance()->getLogger();
        }

        $items = $this->loadPages();
        $modified = false;
        $itemsByTarget = [];

        foreach ($items as $item) {
            $target = $item->getTarget();
            // Ignorer les pages standards, les liens javascript, les ancres et les liens vides
            if (!empty($target) && $item->targetIs() === 'plugin' && stripos($target, 'javascript:') === false && $target !== '#') {
                $itemsByTarget[$target][] = $item;
            }
        }

        foreach ($itemsByTarget as $target => $targetItems) {
            if (count($targetItems) > 1) {
                $logger->warning("Page::cleanupDuplicatePluginItems - Plugin '{$target}' a " . count($targetItems) . " items. Nettoyage.");
                usort($targetItems, function ($a, $b) {
                    $aH = $a->getIsHidden() ? 1 : 0;
                    $bH = $b->getIsHidden() ? 1 : 0;
                    return ($aH != $bH) ? ($aH - $bH) : ((int)$a->getPosition() - (int)$b->getPosition());
                });

                for ($i = 1; $i < count($targetItems); $i++) {
                    $targetItems[$i]->delete();
                    $modified = true;
                }
            }
        }

        if ($modified) {
            $this->items = $this->loadPages();
        }
    }

    /**
     * Conservé pour compatibilité de boot : déclenche la migration initiale vers MenuService
     * sans plus injecter ni créer automatiquement de PageItem pour les plugins.
     */
    public static function addToNavigation()
    {
        \Core\Navigation\MenuService::getInstance()->ensureMigrated();
    }

    public static function getPageContent($id)
    {
        $page = new Page();
        $temp = $page->create($id);
        return $temp ? $temp->getContent() : '';
    }

    public function getItems() { return $this->items; }
    
    public function create($id)
    {
        foreach ($this->items as $pageItem) {
            if ($pageItem->getId() == $id) return $pageItem;
        }
        return false;
    }

    public function createHomepage()
    {
        foreach ($this->items as $pageItem) {
            if ($pageItem->getIshomepage()) return $pageItem;
        }
        return false;
    }

    public function count() { return count($this->items); }

    public function listTemplates()
    {
        $core = Core::getInstance();
        $theme = THEMES . $core->getConfigVal('theme') . '/';
        $items = Util::scanDir($theme, ['404.tpl', 'layout.tpl']);
        $data = [];
        foreach ($items['file'] as $file) {
            if (Util::getFileExtension($file) === 'tpl') $data[] = $file;
        }
        return $data;
    }

    public function makeUrl($obj)
    {
        $core = Core::getInstance();
        if ($obj->targetIs() == 'page') {
            return ($core->getConfigVal('defaultPlugin') == 'page' && $obj->getIsHomepage()) 
                ? $core->getConfigVal('siteUrl') 
                : Router::getInstance()->generate('page-read', ['name' => Util::strToUrl(preg_replace("#\<i.+\<\/i\>#i", '', $obj->getName())), 'id' => $obj->getId()]);
        }
        if ($obj->targetIs() == 'url') return $obj->getTarget();
        $url = Router::getInstance()->generate($obj->getTarget());
        return ($url !== '#') ? $url : $core->getConfigVal('siteUrl') . '/' . $obj->getTarget() . '/';
    }

    public function isUnlocked($obj)
    {
        if ($obj->getPassword() == '') return true;
        $ipHash = sha1((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        $expected = sha1((string) $obj->getId()) . $obj->getPassword() . $ipHash;
        if (isset($_SESSION['pagePasswords'][$obj->getId()]) && hash_equals((string) $_SESSION['pagePasswords'][$obj->getId()], $expected)) {
            return true;
        }
        return (isset($_SESSION['pagePassword']) && hash_equals((string) $_SESSION['pagePassword'], $expected));
    }

    public function unlock($obj, $password)
    {
        if (hash_equals((string) $obj->getPassword(), sha1(trim((string) $password)))) {
            $ipHash = sha1((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
            $token = sha1((string) $obj->getId()) . $obj->getPassword() . $ipHash;
            $_SESSION['pagePassword'] = $token;
            $_SESSION['pagePasswords'][$obj->getId()] = $token;
            return true;
        }
        return false;
    }

    private function initIshomepageVal($excludeId = null)
    {
        foreach ($this->items as $obj) {
            if ($excludeId !== null && $obj->getId() == $excludeId) {
                continue;
            }
            if ($obj->getIsHomepage() > 0) {
                $obj->setIsHomepage(0);
                $obj->save();
            }
        }
    }

    public function makePosition()
    {
        $pos = [0];
        foreach ($this->items as $p) $pos[] = (float)$p->getPosition();
        return max($pos) + 1;
    }

    public function registerRoutes(Router $router): void { require_once COMMON . 'Core/Page/routes.php'; }
    public function getAdminNavigationEntries(): array { return [['name' => 'page', 'label' => Lang::get('page.name'), 'icon' => 'fa-regular fa-file-lines']]; }
}
