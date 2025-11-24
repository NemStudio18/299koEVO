<?php

namespace Core\Page\Controllers;

use Core\Controllers\PublicController;
use Core\Page\Page;
use Core\Page\PageItem;
use Utils\Util;
use Core\Responses\PublicResponse;
use Template\Template;

/**
 * @copyright (C) 2023, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class PageController extends PublicController
{

    /**
     * page Object
     * @var Page
     */
    protected Page $page;

    /**
     * pageItem Object
     * @var PageItem
     */
    protected PageItem $pageItem;

    public function home()
    {
        $this->page = new Page();
        $this->pageItem = $this->page->createHomepage();

        return $this->renderPage();
    }

    public function read($name, $id)
    {
        $this->page = new Page();
        $this->pageItem = $this->page->create($id);

        return $this->renderPage();
    }

    protected function renderPage()
    {
        if ($this->pageItem->targetIs() != 'page' || $this->pageItem === false) {
            $this->core->error404();
        }
        $action = (isset($_POST['unlock'])) ? 'unlock' : '';
        $url = $this->page->makeUrl($this->pageItem);
        if ($action === 'unlock') {
            // quelques contrôle et temps mort volontaire avant le send...
            sleep(2);
        
            if (isset($_POST['password']) && $_SERVER['HTTP_REFERER'] === Util::urlBuild($url)) {
                $this->page->unlock($this->pageItem, $_POST['password']);
            }
            header('location:' . $url);
            die();
        }

        $this->setPageMetas();
        if ($this->page->isUnlocked($this->pageItem)) {
            // template
            $pageFile = ($this->pageItem->getFile()) ? THEMES . $this->core->getConfigVal('theme') . '/' . $this->pageItem->getFile() : false;
        }

        if (isset($pageFile) && $pageFile !== false) {
            if (Util::getFileExtension($this->pageItem->getFile()) === 'tpl') {
                $response = new PublicResponse();
                $filephp = preg_replace('"\.tpl$"', '.php', $this->pageItem->getFile());
                $tpl = new Template($pageFile);
                if (file_exists(THEMES . $this->core->getConfigVal('theme') . '/' . $filephp)) {
                    require THEMES . $this->core->getConfigVal('theme') . '/' . $filephp;
                }
            } else {
				$core = $this->core;
                include_once(THEMES . $this->core->getConfigVal('theme') . '/header.php');
                include_once(THEMES . $this->core->getConfigVal('theme') . '/' . $this->pageItem->getFile());
                include_once(THEMES . $this->core->getConfigVal('theme') . '/footer.php');
                die();
            }
        } else {
            $response = new PublicResponse();
            $tpl = $response->createPluginTemplate('page', 'read');
        }

        $tpl->set('page', $this->page);
        $tpl->set('pageItem', $this->pageItem);
        $tpl->set('sendUrl', $url);

        $response->addTemplate($tpl);
        return $response;
    }

    protected function setPageMetas()
    {
        if ($this->page->isUnlocked($this->pageItem)) {
            # Gestion du titre
            if ($this->core->getConfigVal('hideTitles'))
                $this->core->setMainTitle('');
            else
                $this->core->setMainTitle(($this->pageItem->getMainTitle() != '') ? $this->pageItem->getMainTitle() : $this->pageItem->getName());
            # Gestion des metas
            if ($this->pageItem->getMetaTitleTag())
                $this->core->setTitleTag($this->pageItem->getMetaTitleTag());
            else
                $this->core->setTitleTag($this->pageItem->getName());
            if ($this->pageItem->getMetaDescriptionTag())
                $this->core->setMetaDescription($this->pageItem->getMetaDescriptionTag());
        } else {
            $this->core->setTitleTag('Accès restreint');
            $this->core->setMainTitle('Accès restreint');
        }
    }
}