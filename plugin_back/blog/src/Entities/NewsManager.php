<?php

namespace Blog\Entities;

use Utils\Util;
use Core\Router\Router;
use Core\Core;
use Core\Plugin\PluginsManager;
use Blog\Entities\News;
use Blog\Entities\Comment;
use Blog\Entities\CategoriesManager;

defined('ROOT') OR exit('Access denied!');

class NewsManager
{

    private $items;
    private $comments;

    /**
     * All comments from a news, non imbricated
     */
    protected $flatComments;

    private int $nbItemsToPublic;

    public function __construct()
    {
        $categoriesManager = new CategoriesManager();
        $i = 0;
        $data = [];
        // Use DATA_PLUGIN constant if available
        $dataFile = (defined('DATA_PLUGIN') ? DATA_PLUGIN : ROOT . 'data/plugin/') . 'blog/blog.json';

        if (file_exists($dataFile)) {
            $temp = Util::readJsonFile($dataFile);
            if (!is_array($temp)) {
                $temp = [];
            }
            $temp = Util::sort2DimArray($temp, 'date', 'desc');
            foreach ($temp as $k => $v) {
                $categories = [];
                foreach ($categoriesManager->getCategories() as $cat) {
                    if (in_array($v['id'], $cat->items)) {
                        $categories['categories'][$cat->id] = [
                            'label' => $cat->label,
                            'url' => Router::getInstance()->generate('blog-category', ['name' => Util::strToUrl($cat->label), 'id' => $cat->id]),
                            'id' => $cat->id
                        ];
                    }
                }
                $v = array_merge($v, $categories);
                $data[] = new News($v);
                if ($v['draft'] === "0") {
                    $i++;
                }
            }
        }
        $this->nbItemsToPublic = $i;
        $this->items = $data;
    }

    /**
     * Retrieves all news items.
     *
     * @return News[] An array of news objects.
     */

    public function getItems()
    {
        return $this->items;
    }


    /**
     * @param mixed $id
     * @return News|null
     */
    public function create($id): ?News
    {
        foreach ($this->items as $obj) {
            if ($obj->getId() == $id)
                return $obj;
        }
        return null;
    }

    public function saveNews($obj)
    {
        $id = intval($obj->getId());
        if ($id < 1) {
            $obj->setId($this->makeId());
            $this->items[] = $obj;
        } else {
            foreach ($this->items as $k => $v) {
                if ($id == $v->getId())
                    $this->items[$k] = $obj;
            }
        }
        return $this->save();
    }

    /**
     * Delete a News from blog & her comments
     * @param News $obj
     * @return bool News correctly deleted
     */
    public function delNews(News $obj): bool
    {
        $id = $obj->getId();
        foreach ($this->items as $k => $v) {
            if ($id == $v->getId())
                unset($this->items[$k]);
        }
        if ($this->save()) {
            return $this->deleteCommentsFromNews($id);
        }
        return false;
    }

    public function count()
    {
        return count($this->items);
    }

    /**
     * Return number of news who can be displayed in public mode
     */
    public function getNbItemsToPublic()
    {
        return $this->nbItemsToPublic;
    }

    public function rss()
    {
        $core = Core::getInstance();
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<rss version="2.0">';
        $xml .= '<channel>';
        $xml .= ' <title>' . $core->getConfigVal('siteName') . ' - ' . PluginsManager::getPluginConfVal('blog', 'label') . '</title>';
        $xml .= ' <link>' . $core->getConfigVal('siteUrl') . '/</link>';
        $xml .= ' <description>' . $core->getConfigVal('siteDescription') . '</description>';
        $xml .= ' <language>' . $core->getConfigVal('siteLang') . '</language>';
        foreach ($this->getItems() as $k => $v)
            if (!$v->getDraft()) {
                $xml .= '<item>';
                $xml .= '<title><![CDATA[' . $v->getName() . ']]></title>';
                $xml .= '<link>' . $core->getConfigVal('siteUrl') . '/news/' . Util::strToUrl($v->getName()) . '-' . $v->getId() . '.html</link>';
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

    private function makeId()
    {
        $ids = array(0);
        foreach ($this->items as $obj) {
            $ids[] = $obj->getId();
        }
        return max($ids) + 1;
    }

    private function save()
    {
        $data = array();
        foreach ($this->items as $k => $v) {
            $data[] = array(
                'id' => $v->getId(),
                'name' => $v->getName(),
                'content' => $v->getContent(),
                'intro' => $v->getIntro(),
                'seoDesc' => $v->getSEODesc(),
                'date' => $v->getDate(),
                'draft' => $v->getDraft(),
                'img' => $v->getImg(),
                'commentsOff' => $v->getCommentsOff(),
            );
        }
        $dataFile = (defined('DATA_PLUGIN') ? DATA_PLUGIN : ROOT . 'data/plugin/') . 'blog/blog.json';
        if (Util::writeJsonFile($dataFile, $data))
            return true;
        return false;
    }

    // Comments

    public function getComments()
    {
        return $this->comments;
    }

    public function getFlatComments()
    {
        return $this->flatComments;
    }

    /**
     * @param mixed $id
     * @return Comment|null
     */
    public function createComment($id): ?Comment
    {
        foreach ($this->flatComments as $obj) {
            if ($obj->getId() == $id)
                return $obj;
        }
        return null;
    }

    public function loadComments($idNews)
    {
        $commentsFile = (defined('DATA_PLUGIN') ? DATA_PLUGIN : ROOT . 'data/plugin/') . 'blog/comments.json';
        if (!file_exists($commentsFile))
            Util::writeJsonFile($commentsFile, []);
        $temp = Util::readJsonFile($commentsFile);
        $comments = $temp[$idNews] ?? [];
        $temp = Util::sort2DimArray($comments, 'id', 'asc');
        $data = [];
        foreach ($temp as $v) {
            $data[$v['id']] = new Comment($v);
        }
        $this->flatComments = $data;
        $this->comments = $data;
        foreach ($this->comments as $k => &$com) {
            foreach ($com->repliesId as $comId) {
                $com->replies[$comId] = $data[$comId];
                unset($this->comments[$comId]);
            }
            foreach ($com->replies as &$comment) {
                $comment = $this->hydrateReplies($comment);
            }
        }
    }

    protected function deleteCommentsFromNews($idNews)
    {
        $commentsFile = (defined('DATA_PLUGIN') ? DATA_PLUGIN : ROOT . 'data/plugin/') . 'blog/comments.json';
        $temp = Util::readJsonFile($commentsFile);
        unset($temp[$idNews]);
        return Util::writeJsonFile($commentsFile, $temp);
    }

    public static function getLatestComments(int $nbComments = 10)
    {
        $newsManager = new NewsManager();
        $commentsFile = (defined('DATA_PLUGIN') ? DATA_PLUGIN : ROOT . 'data/plugin/') . 'blog/comments.json';
        $rawComments = Util::readJsonFile($commentsFile);
        $timeComments = [];
        foreach ($rawComments as $idNews => $comments) {
            $news = $newsManager->create($idNews);
            foreach ($comments as $comment) {
                $timeComments[Util::getTimestampFromDate($comment['date'])] = ['comment' => new Comment($comment), 'news' => $news];
            }
        }
        krsort($timeComments);
        return array_slice($timeComments, 0, $nbComments, true);
    }

    protected function hydrateReplies(Comment $comment): Comment
    {
        if (!empty($comment->repliesId)) {
            foreach ($comment->repliesId as $comId) {
                $comment->replies[$comId] = $this->comments[$comId];
                unset($this->comments[$comId]);
            }
            foreach ($comment->replies as &$childComment) {
                $childComment = $this->hydrateReplies($childComment);
            }
        }
        return $comment;

    }

    public function countComments($idNews = 0)
    {
        if ($idNews == 0)
            return count($this->flatComments);
        else {
            $this->loadComments($idNews);
            return count($this->flatComments);
        }
    }

    public function saveComment(Comment $comment)
    {

        $this->flatComments[$comment->getId()] = $comment;
        $this->saveComments($comment->getIdNews());
        return true;
    }

    public function addReplyToComment(Comment $comment, int $parentId): void
    {
        if (isset($this->flatComments[$parentId]) && $comment->getIdNews()) {
            $this->flatComments[$parentId]->repliesId[] = $comment->getId();
            $this->flatComments[$parentId]->repliesId = array_unique($this->flatComments[$parentId]->repliesId);
            $this->saveComments($comment->getIdNews());
        }
    }

    protected function saveComments($idNews): void
    {
        $commentsFile = (defined('DATA_PLUGIN') ? DATA_PLUGIN : ROOT . 'data/plugin/') . 'blog/comments.json';
        $rawComments = Util::readJsonFile($commentsFile);
        $objData = [];
        foreach ($rawComments as $newsId => $comment) {
            foreach ($comment as $idComment => $v) {
                $objData[$newsId][$idComment] = new Comment($v);
            }
        }
        $objData[$idNews] = $this->flatComments;
        $data = [];
        foreach ($objData as $newsId) {
            foreach ($newsId as $k => $v) {
                // Remove debug log if too verbose or keep it
                // Core::getInstance()->getLogger()->log('DEBUG', "Saving comment for news ID: " . (is_array($newsId) ? json_encode($newsId) : $newsId));
                $data[$v->getIdNews()][$k] = [
                    'id' => $v->getId(),
                    'idNews' => $v->getIdNews(),
                    'content' => $v->getContent(),
                    'date' => $v->getDate(),
                    'author' => $v->getAuthor(),
                    'authorEmail' => $v->getAuthorEmail(),
                    'authorWebsite' => $v->getAuthorWebsite(),
                    'replies' => $v->repliesId
                ];
            }
        }
        Util::writeJsonFile($commentsFile, $data);
    }

    /**
     * Delete a comment and all occurences from this comment in others it was a reply
     * and save the comments
     * @param Comment $obj
     * @return bool Comment deleted
     */
    public function delComment(Comment $obj): bool
    {
        $commentsFile = (defined('DATA_PLUGIN') ? DATA_PLUGIN : ROOT . 'data/plugin/') . 'blog/comments.json';
        $rawComments = Util::readJsonFile($commentsFile);
        if (isset($rawComments[$obj->getIdNews()][$obj->getId()])) {
            $idNews = $obj->getIdNews();
            $newsComments = &$rawComments[$idNews];
            foreach ($newsComments as $idComment => &$comment) {
                if (!is_array($comment['replies'])) {
                    $comment['replies'] = [];
                }
                if (in_array($obj->getId(), $comment['replies'])) {
                    $comment['replies'] = array_diff($comment['replies'], [$obj->getId()]);
                }
            }
            unset($rawComments[$obj->getIdNews()][$obj->getId()]);
        }
        return Util::writeJsonFile($commentsFile, $rawComments);
    }
}
