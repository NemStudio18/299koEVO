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
                            $news[$k]['cats'][] = [
                                'label' => $cat->label,
                                'url' => $this->router->generate('blog-category', ['name' => Util::strToUrl($cat->label), 'id' => $cat->id]),
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
        // Traitements divers : métas, fil d'ariane...
        $response = new PublicResponse();
        $response->setTitle('<i class="fa-solid fa-newspaper"></i> ' . ($this->pluginsManager->getPlugin('blog')->getConfigVal('label') ?: 'Actualités'));
        
        $tpl = $response->createPluginTemplate('blog', 'list');

        $tpl->set('news', $news);
        $tpl->set('newsManager', $newsManager);
        $tpl->set('pagination', $pagination);
        $tpl->set('mode', $mode);
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
                            $news[$k]['cats'][] = [
                                'label' => $cat->label,
                                'url' => $this->router->generate('blog-category', ['name' => Util::strToUrl($cat->label), 'id' => $cat->id]),
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
            if ($nbPages > 1) {
                $pagination = [];
                for ($i = 0; $i != $nbPages; $i++) {
                    if ($i != 0)
                        $pagination[$i]['url'] = $this->router->generate('blog-category-page', ['name' => Util::strToUrl($category->label), 'id' => $category->id, 'page' => $i + 1]);
                    else
                        $pagination[$i]['url'] = $this->router->generate('blog-category', ['name' => Util::strToUrl($category->label), 'id' => $category->id]);
                    $pagination[$i]['num'] = $i + 1;
                }
            } else {
                $pagination = false;
            }
        } else {
            $pagination = false;
        }



        // Traitements divers : métas, fil d'ariane...
        $response = new PublicResponse();
        $response->setTitle('<i class="fa-solid fa-folder-open"></i> ' . $category->label);
        
        $tpl = $response->createPluginTemplate('blog', 'list');

        $tpl->set('news', $news);
        $tpl->set('newsManager', $newsManager);
        $tpl->set('pagination', $pagination);
        $tpl->set('mode', $mode);
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