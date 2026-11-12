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
        // Contruction de la pagination
        $nbNews = $newsManager->getNbItemsToPublic();
        $mode = ($nbNews > 0) ? 'list' : 'list_empty';
        $newsByPage = max(1, (int)($this->runPlugin->getConfigVal('items_per_page') ?: $this->runPlugin->getConfigVal('itemsByPage') ?: 10));
        $nbPages = max(1, (int)ceil($nbNews / $newsByPage));
        $currentPage = max(1, (int)$currentPage);
        if ($currentPage > $nbPages) {
            $currentPage = 1;
        }
        $start = ($currentPage - 1) * $newsByPage + 1;
        $end = $start + $newsByPage - 1;
        $pagination = $this->buildPagination($currentPage, $nbPages, function (int $page) {
            return $page > 1
                ? $this->router->generate('blog-page', ['page' => $page])
                : $this->runPlugin->getPublicUrl();
        });
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

        $displayCategoriesBar = $this->applyCategoriesDisplay($allCategories, null);

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
        $tpl->set('displayCategoriesBar', $displayCategoriesBar);
        $tpl->set('blogHomeUrl', $this->runPlugin->getPublicUrl());
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

        $newsByPage = max(1, (int)($this->runPlugin->getConfigVal('items_per_page') ?: $this->runPlugin->getConfigVal('itemsByPage') ?: 10));
        $currentPage = max(1, (int)$currentPage);

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
            $nbPages = max(1, (int)ceil($nbNews / $newsByPage));
            if ($currentPage > $nbPages) {
                return $this->category($name, $id, 1);
            }
            $cleanCatLabel = html_entity_decode($category->label, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $cleanCatSlug = Util::strToUrl($cleanCatLabel);
            $pagination = $this->buildPagination($currentPage, $nbPages, function (int $page) use ($cleanCatSlug, $category) {
                return $page > 1
                    ? $this->router->generate('blog-category-page', ['name' => $cleanCatSlug, 'id' => $category->id, 'page' => $page])
                    : $this->router->generate('blog-category', ['name' => $cleanCatSlug, 'id' => $category->id]);
            });
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

        $currentCategory = [
            'id' => $category->id,
            'label' => $cleanCatTitle,
        ];
        $displayCategoriesBar = $this->applyCategoriesDisplay($allCategories, $currentCategory);

        // Traitements divers : métas, fil d'ariane...
        $response = new PublicResponse();
        $response->setTitle('<i class="fa-solid fa-folder-open"></i> ' . $cleanCatTitle);
        
        $tpl = $response->createPluginTemplate('blog', 'list');

        $tpl->set('news', $news);
        $tpl->set('newsManager', $newsManager);
        $tpl->set('pagination', $pagination);
        $tpl->set('mode', $mode);
        $tpl->set('allCategories', $allCategories);
        $tpl->set('currentCategory', $currentCategory);
        $tpl->set('displayCategoriesBar', $displayCategoriesBar);
        $tpl->set('blogHomeUrl', $this->runPlugin->getPublicUrl());
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

    /**
     * Build a sliding-window pagination list with ellipsis when there are many pages.
     *
     * @return array<int, array{num: int|string, url: string, active: bool, ellipsis: bool}>|false
     */
    private function buildPagination(int $currentPage, int $nbPages, callable $urlGenerator)
    {
        if ($nbPages <= 1) {
            return false;
        }

        if ($nbPages <= 7) {
            $pages = range(1, $nbPages);
        } else {
            $pages = [1];
            $windowStart = max(2, $currentPage - 1);
            $windowEnd = min($nbPages - 1, $currentPage + 1);

            if ($currentPage <= 2) {
                $windowStart = 2;
                $windowEnd = 2;
            } elseif ($currentPage >= $nbPages - 1) {
                $windowStart = $nbPages - 1;
                $windowEnd = $nbPages - 1;
            }

            if ($windowStart > 2) {
                $pages[] = '...';
            }
            for ($p = $windowStart; $p <= $windowEnd; $p++) {
                $pages[] = $p;
            }
            if ($windowEnd < $nbPages - 1) {
                $pages[] = '...';
            }
            $pages[] = $nbPages;
        }

        $pagination = [];
        foreach ($pages as $item) {
            if ($item === '...') {
                $pagination[] = [
                    'num' => '…',
                    'url' => '',
                    'active' => false,
                    'ellipsis' => true,
                ];
            } else {
                $pagination[] = [
                    'num' => $item,
                    'url' => (string)$urlGenerator((int)$item),
                    'active' => ($item === $currentPage),
                    'ellipsis' => false,
                ];
            }
        }
        return $pagination;
    }

    /**
     * Handle category navigation placement ('content', 'sidebar', or 'no').
     * Returns true if the horizontal bar should render inside the list template.
     */
    private function applyCategoriesDisplay(array $allCategories, ?array $currentCategory): bool
    {
        if (empty($allCategories)) {
            return false;
        }
        $placement = $this->runPlugin->getConfigVal('displayCategories') ?: 'content';
        if ($placement === 'no') {
            return false;
        }
        if ($placement === 'sidebar') {
            $allLabel = htmlspecialchars(\Core\Lang::get('blog-all-categories') ?: 'Toutes', ENT_QUOTES, 'UTF-8');
            $homeUrl = htmlspecialchars($this->runPlugin->getPublicUrl(), ENT_QUOTES, 'UTF-8');
            $isAllActive = ($currentCategory === null);
            $html = '<ul class="blog-categories-sidebar-list">';
            $html .= '<li class="' . ($isAllActive ? 'active' : '') . '"><a href="' . $homeUrl . '">' . $allLabel . '</a></li>';
            foreach ($allCategories as $cat) {
                $isActive = ($currentCategory !== null && (int)$currentCategory['id'] === (int)$cat['id']);
                $catUrl = htmlspecialchars($cat['url'], ENT_QUOTES, 'UTF-8');
                $catLabel = htmlspecialchars($cat['label'], ENT_QUOTES, 'UTF-8');
                $catCount = (int)$cat['count'];
                $html .= '<li class="' . ($isActive ? 'active' : '') . '"><a href="' . $catUrl . '"><span>' . $catLabel . '</span><span class="cat-pill-count">' . $catCount . '</span></a></li>';
            }
            $html .= '</ul>';
            \Utils\Show::addSidebarPublicModule(\Core\Lang::get('blog-categories') ?: 'Catégories', $html);
            return false;
        }
        return true;
    }
}