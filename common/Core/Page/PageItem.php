<?php

namespace Core\Page;

use Utils\Util;
use Core\Plugin\PluginsManager;

/**
 * Entité représentant une page/navigation dans le core.
 */
defined('ROOT') or exit('Access denied!');

class PageItem
{
    private $id;
    private $name;
    private $position;
    private $isHomepage;
    private $content;
    private $isHidden;
    private $file;
    private $mainTitle;
    private $metaDescriptionTag;
    private $metaTitleTag;
    private $target;
    private $targetAttr;
    private $noIndex;
    private $parent;
    private $cssClass;
    private $password;
    private $img;

    public function __construct($val = array())
    {
        if (count($val) > 0) {
            $this->id = $val['id'];
            $this->name = $val['name'];
            $this->position = $val['position'];
            $this->isHomepage = $val['isHomepage'];
            $this->content = $val['content'];
            $this->isHidden = $val['isHidden'];
            $this->file = $val['file'];
            $this->mainTitle = $val['mainTitle'];
            $this->metaDescriptionTag = $val['metaDescriptionTag'];
            $this->metaTitleTag = (isset($val['metaTitleTag']) ? $val['metaTitleTag'] : '');
            $this->target = (isset($val['target']) ? $val['target'] : '');
            $this->targetAttr = (isset($val['targetAttr']) ? $val['targetAttr'] : '_self');
            $this->noIndex = (isset($val['noIndex']) ? $val['noIndex'] : 0);
            $this->parent = (isset($val['parent']) ? $val['parent'] : 0);
            $this->cssClass = (isset($val['cssClass']) ? $val['cssClass'] : '');
            $this->password = (isset($val['password']) ? $val['password'] : '');
            $this->img = (isset($val['img']) ? $val['img'] : '');
        }
    }

    public function setName($val)
    {
        $val = trim($val);
        $this->name = $val;
    }

    public function setPosition($val)
    {
        $this->position = trim($val);
    }

    public function setIsHomepage($val)
    {
        $this->isHomepage = trim($val);
    }

    public function setContent($val)
    {
        $this->content = trim($val);
    }

    public function setIsHidden($val)
    {
        $this->isHidden = intval($val);
    }

    public function setFile($val)
    {
        $this->file = trim($val);
    }

    public function setMainTitle($val)
    {
        $this->mainTitle = trim($val);
    }

    public function setMetaDescriptionTag($val)
    {
        $val = trim($val);
        if (mb_strlen($val) > 150)
            $val = mb_strcut($val, 0, 150) . '...';
        $this->metaDescriptionTag = $val;
    }

    public function setMetaTitleTag($val)
    {
        $val = trim($val);
        if (mb_strlen($val) > 50)
            $val = mb_strcut($val, 0, 50) . '...';
        $this->metaTitleTag = $val;
    }

    public function setTarget($val)
    {
        $this->target = trim($val);
    }

    public function setTargetAttr($val)
    {
        $this->targetAttr = trim($val);
    }

    public function setNoIndex($val)
    {
        $this->noIndex = trim($val);
    }

    public function setParent($val)
    {
        $this->parent = trim($val);
    }

    public function setCssClass($val)
    {
        $this->cssClass = trim($val);
    }

    public function setPassword($val)
    {
        $this->password = ($val == '') ? $val : sha1(trim($val));
    }

    public function setImg($val)
    {
        $this->img = trim($val);
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function getIsHomepage()
    {
        return $this->isHomepage;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getIsHidden()
    {
        return $this->isHidden;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function getMainTitle()
    {
        return $this->mainTitle;
    }

    public function getMetaDescriptionTag()
    {
        return $this->metaDescriptionTag;
    }

    public function getMetaTitleTag()
    {
        return $this->metaTitleTag;
    }

    public function getTarget()
    {
        if ($this->target == 'url')
            return '';
        return $this->target;
    }

    public function getTargetAttr()
    {
        return $this->targetAttr;
    }

    public function getNoIndex()
    {
        return $this->noIndex;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getCssClass()
    {
        return $this->cssClass;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getImg()
    {
        return $this->img;
    }

    public function getImgUrl()
    {
        return Util::urlBuild(UPLOAD . 'galerie/' . $this->img);
    }

    public function targetIs()
    {
        if ($this->target == '')
            return 'page';
        elseif ($this->target == 'javascript:')
            return 'parent';
        elseif (filter_var($this->target, FILTER_VALIDATE_URL) || $this->target == 'url')
            return 'url';
        else
            return 'plugin';
    }

    public function isVisibleOnList(): bool
    {
        return $this->targetIs() != "plugin" || ($this->targetIs() == "plugin" && PluginsManager::isActivePlugin($this->getTarget()));
    }
}


