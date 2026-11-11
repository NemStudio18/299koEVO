<?php

namespace Blog\Entities;

use Utils\Util;
use Core\Router\Router;
use Content\ContentParser;

defined('ROOT') or exit('Access denied!');

class News
{
    private $id;
    private $name;
    private $date;
    private $content = '';
    private $intro;
    private $seoDesc;
    private $draft;
    private $img;
    private $commentsOff;

    private ContentParser $parser;

    /**
     * Array with Id of categories
     * @var array
     */
    public array $categories = [];

    public function __construct($val = [])
    {
        if (count($val) > 0) {
            $this->id = $val['id'];
            $this->name = $val['name'];
            $this->content = $val['content'];
            $this->intro = $val['intro'] ?? '';
            $this->seoDesc = $val['seoDesc'] ?? '';
            $this->date = $val['date'];
            $this->draft = $val['draft'];
            $this->img = (isset($val['img']) ? $val['img'] : '');
            $this->commentsOff = (isset($val['commentsOff']) ? $val['commentsOff'] : 0);
            $this->categories = $val['categories'] ?? [];
        }
        $this->parser = new ContentParser($this->content);
    }

    public function setId($val)
    {
        $this->id = intval($val);
    }

    public function setName($val)
    {
        $this->name = trim($val);
    }

    public function setContent($val)
    {
        $this->content = trim($val);
        $this->parser->setContent($this->content);
    }

    public function setIntro($val)
    {
        $this->intro = trim($val);
    }

    public function setSEODesc($val)
    {
        $this->seoDesc = trim($val);
    }

    public function setDate($val)
    {
        if ($val === null || empty($val)) {
            $val = date('Y-m-d');
        }
        $val = trim($val);
        $this->date = $val;
    }

    public function setDraft($val)
    {
        $this->draft = trim($val);
    }

    public function setImg($val)
    {
        $this->img = trim($val);
    }

    public function setCommentsOff($val)
    {
        $this->commentsOff = trim($val);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getParsedContent(): string
    {
        return $this->parser->getParsedContent();
    }

    public function getContentWithoutShortcodes(): string
    {
        return $this->parser->getWithoutShortcodesContent();
    }

    public function getUrl()
    {
        return Router::getInstance()->generate('blog-read', ['name' => Util::strToUrl($this->name), 'id' => $this->id]);
    }

    public function getIntro()
    {
        return ($this->intro === '' ? false : $this->intro);
    }

    public function getSEODesc()
    {
        return ($this->seoDesc === '' ? false : $this->seoDesc);
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getReadableDate()
    {
        return Util::getDate($this->date);
    }

    public function getDraft()
    {
        return $this->draft;
    }

    public function getImg()
    {
        return $this->img;
    }

    public function getImgUrl()
    {
        return Util::urlBuild($this->img);
    }

    public function getCommentsOff()
    {
        return $this->commentsOff;
    }
}
