<?php

namespace Docs\Controllers;

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Core\Lang;
use Core\Router\Router;
use Docs\Entities\WikiCategoriesManager;
use Docs\Entities\WikiPageManager;
use Utils\Show;
use Utils\Util;

defined('ROOT') or exit('Access denied!');

class DocsReadController extends PublicController
{

    public function read($name, $id)
    {

        $wikiPageManager = new WikiPageManager();
        $categoriesManager = new WikiCategoriesManager();

        $item = $wikiPageManager->create($id);
        if (!$item) {
            $this->core->error404();
        }


        $this->addMetas($item);


        // Traitements divers : métas, fil d'ariane...
        $this->runPlugin->setMainTitle($item->getName());
        $this->runPlugin->setTitleTag($item->getName());

        $generatedHTML = Util::generateIdForTitle(htmlspecialchars_decode($item->getParsedContent()));
        $toc = $this->generateTOC($generatedHTML);

        $categories = [];
        foreach ($categoriesManager->getCategories() as $cat) {
            if (in_array($item->getId(), $cat->items)) {
                $categories[] = [
                    'label' => $cat->label,
                    'url' => $this->router->generate('docs-category', ['name' => Util::strToUrl($cat->label), 'id' => $cat->id]),
                ];
            }
        }

        // Navigation Précédent / Suivant
        $allPages = $wikiPageManager->getItems();
        $prevPage = null;
        $nextPage = null;
        $currentIndex = null;
        foreach ($allPages as $idx => $p) {
            if ($p->getId() == $item->getId()) {
                $currentIndex = $idx;
                break;
            }
        }
        if ($currentIndex !== null) {
            if ($currentIndex > 0) {
                $prevObj = $allPages[$currentIndex - 1];
                $prevPage = [
                    'name' => $prevObj->getName(),
                    'url' => $this->router->generate('docs-read', ['name' => Util::strToUrl($prevObj->getName()), 'id' => $prevObj->getId()])
                ];
            }
            if ($currentIndex < count($allPages) - 1) {
                $nextObj = $allPages[$currentIndex + 1];
                $nextPage = [
                    'name' => $nextObj->getName(),
                    'url' => $this->router->generate('docs-read', ['name' => Util::strToUrl($nextObj->getName()), 'id' => $nextObj->getId()])
                ];
            }
        }

        $allCategories = $categoriesManager->getCategories();

        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('docs', 'read');

        $tpl->set('item', $item);
        $tpl->set('generatedHtml', $generatedHTML);
        $tpl->set('TOC', $toc);
        $tpl->set('categories', $categories);
        $tpl->set('allCategories', $allCategories);
        $tpl->set('allPages', $allPages);
        $tpl->set('prevPage', $prevPage);
        $tpl->set('nextPage', $nextPage);
        $tpl->set('wikiPageManager', $wikiPageManager);


        $response->addTemplate($tpl);
        return $response;

    }

    protected function generateTOC($html)
    {
        $displayTOC = $this->runPlugin->getConfigVal('displayTOC');
        $toc = false;

        if ($displayTOC === 'content') {
            $toc = Util::generateTableOfContents($html, Lang::get('wiki-toc-title'));
            if (!$toc) {
                return false;
            }
        } elseif ($displayTOC === 'sidebar') {
            $toc = Util::generateTableOfContentAsModule($html);
            if ($toc) {
                Show::addSidebarPublicModule(Lang::get('wiki-toc-title'), $toc);
                return false;
            }
        }
        return $toc;
    }

    protected function addMetas($item)
    {
        $this->core->addMeta('<meta property="og:url" content="' . Util::getCurrentURL() . '" />');
        $this->core->addMeta('<meta property="twitter:url" content="' . Util::getCurrentURL() . '" />');
        $this->core->addMeta('<meta property="og:type" content="article" />');
        $this->core->addMeta('<meta property="og:title" content="' . $item->getName() . '" />');
        $this->core->addMeta('<meta name="twitter:card" content="summary" />');
        $this->core->addMeta('<meta name="twitter:title" content="' . $item->getName() . '" />');
        $this->core->addMeta('<meta property="og:description" content="' . $item->getSEODesc() . '" />');
        $this->core->addMeta('<meta name="twitter:description" content="' . $item->getSEODesc() . '" />');

        if ($this->pluginsManager->isActivePlugin('galerie') && \galerie::searchByfileName($item->getImg())) {
            $this->core->addMeta('<meta property="og:image" content="' . Util::urlBuild(UPLOAD . 'galerie/' . $item->getImg()) . '" />');
            $this->core->addMeta('<meta name="twitter:image" content="' . Util::urlBuild(UPLOAD . 'galerie/' . $item->getImg()) . '" />');
        }
    }





    protected function generateCategoriesSidebar() {
        $content = '';
        $categoriesManager = new WikiCategoriesManager();
        $categories = $categoriesManager->getNestedCategories();
        if (empty($categories)) {
            return false;
        }
        $content .= '<ul>';
        foreach ($categories as $category) {
            $content .= $this->generateCategorySidebar($category);
        }
        $content .= '</ul>';
        return $content;
    }

    protected function generateCategorySidebar($category) {
        $router = Router::getInstance();
        $content = '<li><a href="' . $router->generate('docs-category', ['name' => Util::strToUrl($category->label), 'id' => $category->id]) . '">' .
            $category->label . '</a>';
        if (!empty($category->children)) {
            $content .= '<ul>';
            foreach ($category->children as $child) {
                $content .= $this->generateCategorySidebar($child);
            }
            $content .= '</ul>';
        }
        $content .= '</li>';
        return $content;
    }
}