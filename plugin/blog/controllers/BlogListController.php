<?php

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Utils\Util;
use Blog\Entities\NewsManager;
use Blog\Entities\CategoriesManager;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class BlogListController extends PublicController
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->runPlugin) {
            $this->runPlugin = $this->pluginsManager->getPlugin('blog');
        }
    }

    public function home($currentPage = 1)
    {
        $newsManager = new NewsManager();
        $categoriesManager = new CategoriesManager();
        // Mode d'affichage
        $mode = ($newsManager->count() > 0) ? 'list' : 'list_empty';

        // Contruction de la pagination
        $nbNews = $newsManager->getNbItemsToPublic();
        $newsByPage = $this->runPlugin->getConfigVal('items_per_page') ?: $this->runPlugin->getConfigVal('itemsByPage') ?: 10;
        $nbPages = ceil($nbNews / $newsByPage);
        $start = ($currentPage - 1) * $newsByPage + 1;
        $end = $start + $newsByPage - 1;
        if ($nbPages > 1) {
            $pagination = [];
            for ($i = 0; $i != $nbPages; $i++) {
                if ($i != 0)
                    $pagination[$i]['url'] = $this->router->generate('blog-page', ['page' => $i + 1]);
                else
                    $pagination[$i]['url'] = $this->runPlugin->getPublicUrl();
                $pagination[$i]['num'] = $i + 1;
            }
        } else {
            $pagination = false;
        }
        // Récupération des news
        $news = [];
        $i = 1;
        foreach ($newsManager->getItems() as $k => $v)
            if (!$v->getDraft()) {
                $date = $v->getDate();
                if ($i >= $start && $i <= $end) {
                    $news[$k]['name'] = $v->getName();
                    $news[$k]['date'] = Util::FormatDate($date, 'en', 'fr');
                    $news[$k]['id'] = $v->getId();
                    $news[$k]['cats'] = [];
                    foreach ($categoriesManager->getCategories() as $cat) {
                        if (in_array($v->getId(), $cat->items)) {
                            $cleanLabel = html_entity_decode($cat->label, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                            $news[$k]['cats'][] = [
                                'label' => $cleanLabel,
                                'url' => $this->router->generate('blog-category', ['name' => Util::strToUrl($cleanLabel), 'id' => $cat->id]),
                            ];
                        }
                    }
                    $news[$k]['content'] = $v->getContent();
                    $news[$k]['intro'] = $v->getIntro();
                    $news[$k]['url'] = $this->runPlugin->getPublicUrl() . Util::strToUrl($v->getName()) . '-' . $v->getId() . '.html';
                    $news[$k]['img'] = $v->getImg();
                    $news[$k]['imgUrl'] = $v->getImgUrl();
                    $news[$k]['commentsOff'] = $v->getcommentsOff();
                }
                $i++;
            }
        // Catégories globales pour filtre et navigation
        $publishedItemIds = [];
        foreach ($newsManager->getItems() as $pubItem) {
            if (!$pubItem->getDraft()) {
                $publishedItemIds[] = $pubItem->getId();
            }
        }
        $allCategories = [];
        foreach ($categoriesManager->getCategories() as $cat) {
            $catItems = is_array($cat->items ?? null) ? $cat->items : [];
            $catCount = count(array_intersect($catItems, $publishedItemIds));
            if ($catCount > 0) {
                $cleanLabel = html_entity_decode($cat->label, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $allCategories[] = [
                    'id' => $cat->id,
                    'label' => $cleanLabel,
                    'url' => $this->router->generate('blog-category', ['name' => Util::strToUrl($cleanLabel), 'id' => $cat->id]),
                    'count' => $catCount,
                ];
            }
        }

        // Traitements divers : métas, fil d'ariane...
        $response = new PublicResponse();
        $response->setTitle('<i class="fa-solid fa-newspaper"></i> ' . ($this->pluginsManager->getPlugin('blog')->getConfigVal('label') ?: 'Actualités'));
        
        $tpl = $response->createPluginTemplate('blog', 'list');

        $tpl->set('news', $news);
        $tpl->set('newsManager', $newsManager);
        $tpl->set('pagination', $pagination);
        $tpl->set('mode', $mode);
        $tpl->set('allCategories', $allCategories);
        $tpl->set('currentCategory', null);
        $response->addTemplate($tpl);
        return $response;
    }

    public function category($name, $id, $currentPage = 1)
    {
        $categoriesManager = new CategoriesManager();
        $category = $categoriesManager->getCategory($id);
        if (!$category) {
            $this->core->error404();
        }
        $newsManager = new NewsManager();
        $news = [];

        $newsByPage = $this->runPlugin->getConfigVal('items_per_page') ?: $this->runPlugin->getConfigVal('itemsByPage') ?: 10;

        $start = ($currentPage - 1) * $newsByPage + 1;
        $end = $start + $newsByPage - 1;
        $i = 1;

        foreach ($newsManager->getItems() as $k => $v) {
            if ($v->getDraft()) {
                continue;
            }
            if (in_array($v->getId(), $category->items)) {
                $date = $v->getDate();
                if ($i >= $start && $i <= $end) {
                    $news[$k]['name'] = $v->getName();
                    $news[$k]['date'] = Util::FormatDate($date, 'en', 'fr');
                    $news[$k]['id'] = $v->getId();
                    $news[$k]['cats'] = [];
                    foreach ($categoriesManager->getCategories() as $cat) {
                        if (in_array($v->getId(), $cat->items)) {
                            $cleanLabel = html_entity_decode($cat->label, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                            $news[$k]['cats'][] = [
                                'label' => $cleanLabel,
                                'url' => $this->router->generate('blog-category', ['name' => Util::strToUrl($cleanLabel), 'id' => $cat->id]),
                            ];
                        }
                    }
                    $news[$k]['content'] = $v->getContent();
                    $news[$k]['intro'] = $v->getIntro();
                    $news[$k]['url'] = $this->runPlugin->getPublicUrl() . Util::strToUrl($v->getName()) . '-' . $v->getId() . '.html';
                    $news[$k]['img'] = $v->getImg();
                    $news[$k]['imgUrl'] = $v->getImgUrl();
                    $news[$k]['commentsOff'] = $v->getcommentsOff();

                }
                $i++;
            }
        }
        $nbNews = $i - 1;
        $mode = ($nbNews > 0) ? 'list' : 'list_empty';
        if ($mode === 'list') {
            $nbPages = ceil($nbNews / $newsByPage);
            if ($currentPage > $nbPages) {
                return $this->category($name, $id, 1);
            }
            $cleanCatLabel = html_entity_decode($category->label, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $cleanCatSlug = Util::strToUrl($cleanCatLabel);
            if ($nbPages > 1) {
                $pagination = [];
                for ($i = 0; $i != $nbPages; $i++) {
                    if ($i != 0)
                        $pagination[$i]['url'] = $this->router->generate('blog-category-page', ['name' => $cleanCatSlug, 'id' => $category->id, 'page' => $i + 1]);
                    else
                        $pagination[$i]['url'] = $this->router->generate('blog-category', ['name' => $cleanCatSlug, 'id' => $category->id]);
                    $pagination[$i]['num'] = $i + 1;
                }
            } else {
                $pagination = false;
            }
        } else {
            $pagination = false;
        }

        // Catégories globales pour filtre et navigation
        $publishedItemIds = [];
        foreach ($newsManager->getItems() as $pubItem) {
            if (!$pubItem->getDraft()) {
                $publishedItemIds[] = $pubItem->getId();
            }
        }
        $allCategories = [];
        foreach ($categoriesManager->getCategories() as $cat) {
            $catItems = is_array($cat->items ?? null) ? $cat->items : [];
            $catCount = count(array_intersect($catItems, $publishedItemIds));
            if ($catCount > 0) {
                $cLabel = html_entity_decode($cat->label, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $allCategories[] = [
                    'id' => $cat->id,
                    'label' => $cLabel,
                    'url' => $this->router->generate('blog-category', ['name' => Util::strToUrl($cLabel), 'id' => $cat->id]),
                    'count' => $catCount,
                ];
            }
        }

        $cleanCatTitle = html_entity_decode($category->label, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Traitements divers : métas, fil d'ariane...
        $response = new PublicResponse();
        $response->setTitle('<i class="fa-solid fa-folder-open"></i> ' . $cleanCatTitle);
        
        $tpl = $response->createPluginTemplate('blog', 'list');

        $tpl->set('news', $news);
        $tpl->set('newsManager', $newsManager);
        $tpl->set('pagination', $pagination);
        $tpl->set('mode', $mode);
        $tpl->set('allCategories', $allCategories);
        $tpl->set('currentCategory', [
            'id' => $category->id,
            'label' => $cleanCatTitle,
        ]);
        $response->addTemplate($tpl);
        return $response;
    }

    public function page(int $page)
    {
        $page = $page > 1 ? $page : 1;
        return $this->home($page);
    }

    public function categoryPage(int $id, string $name, int $page)
    {
        $page = $page > 1 ? $page : 1;
        return $this->category($name, $id, $page);
    }
}