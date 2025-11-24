<?php

namespace Core\Page;

use Core\Page\PageItem;
use Core\Plugin\PluginsManager;
use Core\Core;
use Utils\Util;
use Core\Router\Router;
use Core\Lang;

/**
 * Gestion des pages/navigation désormais intégrée au core.
 */
defined('ROOT') or exit('Access denied!');

class Page
{
    private $items;
    private string $pagesFile;
    private string $legacyPagesFile;

    public function __construct()
    {
        $this->pagesFile = DATA_CORE_PAGE . 'pages.json';
        $this->legacyPagesFile = DATA_PLUGIN . 'page/pages.json';
        $this->bootstrapStorage();
        $this->items = $this->loadPages();
    }

    public static function addToNavigation()
    {
        $page = new Page();
        $pluginsManager = PluginsManager::getInstance();
        // Création d'items de navigation absents (plugins)
        foreach ($pluginsManager->getPlugins() as $plugin) {
            if ($plugin->getConfigVal('activate')
                && $plugin->getInfoVal('hideInPublicMenu') !== true
                && ($plugin->getPublicFile() || $plugin->getIsCallableOnPublic())
                && $plugin->getName() !== 'page'
            ) {
                $find = false;
                foreach ($page->getItems() as $pageItem) {
                    if ($pageItem->getTarget() == $plugin->getName()) {
                        $find = true;
                    }
                }
                if (!$find) {
                    $pageItem = new PageItem();
                    $pageItem->setName($plugin->getInfoVal('name'));
                    $pageItem->setPosition($page->makePosition());
                    $pageItem->setIsHomepage(0);
                    $pageItem->setContent('');
                    $pageItem->setIsHidden(0);
                    $pageItem->setFile('');
                    $pageItem->setTarget($plugin->getName());
                    $pageItem->setNoIndex(0);
                    $page->save($pageItem);
                }
            }
        }
        // génération de la navigation
        $core = Core::getInstance();
        foreach ($page->getItems() as $pageItem) {
            if (!$pageItem->getIsHidden()) {
                if ($pageItem->targetIs() == 'plugin') {
                    // Vérifier si le plugin est actif
                    if (!$pluginsManager->isActivePlugin($pageItem->getTarget())) {
                        continue;
                    }
                    // Vérifier si le plugin doit être masqué du menu public
                    $plugin = $pluginsManager->getPlugin($pageItem->getTarget());
                    if ($plugin && $plugin->getInfoVal('hideInPublicMenu') === true) {
                        continue;
                    }
                }
                $url = ($pageItem->targetIs() == 'parent') ? $pageItem->getTarget() : $page->makeUrl($pageItem);
                $targetAttr = $pageItem->getTargetAttr() ?? '_self';
                $cssClass = $pageItem->getCssClass() ?? '';
                $core->addToCoreNavigation($pageItem->getName(), $url, $targetAttr, (int)$pageItem->getId(), (int)$pageItem->getParent(), $cssClass);
            }
        }
    }

    public static function getPageContent($id)
    {
        $page = new Page();
        if ($temp = $page->create($id))
            return $temp->getContent();
        else
            return '';
    }

    public function getItems()
    {
        return $this->items;
    }

    public function create($id)
    {
        foreach ($this->items as $pageItem) {
            if ($pageItem->getId() == $id)
                return $pageItem;
        }
        return false;
    }

    public function createHomepage()
    {
        foreach ($this->items as $pageItem) {
            if ($pageItem->getIshomepage())
                return $pageItem;
        }
        return false;
    }

    public function save($obj)
    {
        $id = intval($obj->getId());
        if ($id < 1) {
            $id = $this->makeId();
            $obj->setId($id);
        }
        $position = floatval($obj->getPosition());
        if ($position < 0.5)
            $position = $this->makePosition();
        $data = array(
            'id' => $id,
            'name' => $obj->getName(),
            'position' => $position,
            'isHomepage' => $obj->getIsHomepage(),
            'content' => $obj->getContent(),
            'isHidden' => $obj->getIsHidden(),
            'file' => $obj->getFile(),
            'mainTitle' => $obj->getMainTitle(),
            'metaDescriptionTag' => $obj->getMetaDescriptionTag(),
            'metaTitleTag' => $obj->getMetaTitleTag(),
            'targetAttr' => $obj->getTargetAttr(),
            'target' => $obj->getTarget(),
            'noIndex' => $obj->getNoIndex(),
            'parent' => $obj->getParent(),
            'cssClass' => $obj->getCssClass(),
            'password' => $obj->getPassword(),
            'img' => $obj->getImg(),
        );
        $update = false;
        foreach ($this->items as $k => $v) {
            if ($v->getId() == $obj->getId()) {
                $this->items[$k] = $obj;
                $update = true;
            }
        }
        if (!$update)
            $this->items[] = $obj;
        if ($obj->getIsHomepage() > 0)
            $this->initIshomepageVal();
        $pages = $this->loadPages(true);
        if ($update) {
            if (is_array($pages)) {
                foreach ($pages as $k => $v) {
                    if ($v['id'] == $obj->getId()) {
                        $pages[$k] = $data;
                        $update = true;
                    }
                }
            }
        } else
            $pages[] = $data;
        $pages = Util::sort2DimArray($pages, 'position', 'num');
        if (Util::writeJsonFile($this->pagesFile, $pages))
            return true;
        return false;
    }

    public function del($obj)
    {
        if ($obj->getIsHomepage() < 1 && $this->count() > 1) {
            foreach ($this->items as $k => $v) {
                if ($v->getId() == $obj->getId())
                    unset($this->items[$k]);
                if ($v->getParent() == $obj->getId())
                    unset($this->items[$k]);
            }
            $pages = $this->loadPages(true);
            foreach ($pages as $k => $v) {
                if ($v['id'] == $obj->getId())
                    unset($pages[$k]);
                if ($v['parent'] == $obj->getId())
                    unset($pages[$k]);
            }
            if (Util::writeJsonFile($this->pagesFile, $pages))
                return true;
            return false;
        }
        return false;
    }

    public function makePosition()
    {
        $pos = array(0);
        foreach ($this->items as $pageItem) {
            $pos[] = $pageItem->getPosition();
        }
        return max($pos) + 1;
    }

    public function count()
    {
        return count($this->items);
    }

    public function listTemplates()
    {
        $core = Core::getInstance();
        $data = [];
        $items = Util::scanDir(THEMES . $core->getConfigVal('theme') . '/', ['404.tpl', 'layout.tpl']);
        foreach ($items['file'] as $file) {
            if (Util::getFileExtension($file) === 'tpl')
                $data[] = $file;
        }
        return $data;
    }

    public function makeUrl($obj)
    {
        $core = Core::getInstance();
        if ($obj->targetIs() == 'page')
            $temp = ($core->getConfigVal('defaultPlugin') == 'page' && $obj->getIsHomepage()) ? $core->getConfigVal('siteUrl') : Router::getInstance()->generate('page-read', ['name' => Util::strToUrl(preg_replace("#\<i.+\<\/i\>#i", '', $obj->getName())), 'id' => $obj->getId()]);
        elseif ($obj->targetIs() == 'url')
            $temp = $obj->getTarget();
        else
            $temp = $core->getConfigVal('siteUrl') . '/' . $obj->getTarget() . '/';
        return $temp;
    }

    public function isUnlocked($obj)
    {
        if ($obj->getPassword() == '')
            return true;
        elseif (isset($_SESSION['pagePassword']) && sha1($obj->getId()) . $obj->getPassword() . sha1($_SERVER['REMOTE_ADDR']) == $_SESSION['pagePassword'])
            return true;
        else
            return false;
    }

    public function unlock($obj, $password)
    {
        if (sha1(trim($password)) == $obj->getPassword()) {
            $_SESSION['pagePassword'] = sha1($obj->getId()) . $obj->getPassword() . sha1($_SERVER['REMOTE_ADDR']);
            return true;
        }
        return false;
    }

    private function makeId()
    {
        $ids = array(0);
        foreach ($this->items as $pageItem) {
            $ids[] = $pageItem->getId();
        }
        return max($ids) + 1;
    }

    private function initIshomepageVal()
    {
        foreach ($this->items as $obj) {
            $obj->setIsHomepage(0);
            $this->save($obj);
        }
    }

    private function loadPages($array = false)
    {
        $data = array();
        if (file_exists($this->pagesFile)) {
            $items = Util::readJsonFile($this->pagesFile);
            $items = Util::sort2DimArray($items, 'position', 'num');
            for ($i = 0; $i != count($items); $i++) {
                $pos = $i + 1;
                $items[$i]['position'] = $pos;
            }
            Util::writeJsonFile($this->pagesFile, $items);
            if ($array)
                return $items;
            foreach ($items as $pageItem) {
                $data[] = new PageItem($pageItem);
            }
        }
        return $data;
    }

    private function bootstrapStorage(): void
    {
        if (!is_dir(DATA_CORE_PAGE)) {
            @mkdir(DATA_CORE_PAGE, 0755, true);
        }

        if (!file_exists($this->pagesFile)) {
            if (file_exists($this->legacyPagesFile)) {
                $payload = Util::readJsonFile($this->legacyPagesFile, true);
                Util::writeJsonFile($this->pagesFile, $payload);
            } else {
                Util::writeJsonFile($this->pagesFile, []);
            }
        }
    }

    /**
     * Register routes for Page module
     * @param Router $router
     */
    public function registerRoutes(Router $router): void {
        require_once COMMON . 'Core/Page/routes.php';
    }

    /**
     * Get admin navigation entries for Page module
     * @return array
     */
    public function getAdminNavigationEntries(): array
    {
        return [
            [
                'name' => 'page',
                'label' => Lang::get('page.name'),
                'icon' => 'fa-regular fa-file-lines',
            ],
        ];
    }
}


