<?php

namespace Core\Page;

use Utils\Util;
use Core\Plugin\PluginsManager;
use Core\Navigation\Interfaces\ResourceInterface;

/**
 * Entité représentant une page/navigation dans le core.
 */
defined('ROOT') or exit('Access denied!');

class PageItem extends \Core\Storage\JsonActiveRecord implements ResourceInterface
{
    protected static string $filePath = DATA_CORE_PAGE . 'pages.json';
    protected static string $tableName = 'pages';
    protected static string $primaryKey = 'id';

    /**
     * Map object properties to SQL columns
     * (Core property => SQL column)
     */
    protected static array $attributeMapping = [
        'id' => 'id',
        'name' => 'name',
        'position' => 'position',
        'isHomepage' => 'is_homepage',
        'content' => 'content',
        'isHidden' => 'is_hidden',
        'file' => 'file',
        'mainTitle' => 'main_title',
        'metaDescriptionTag' => 'meta_description_tag',
        'metaTitleTag' => 'meta_title_tag',
        'target' => 'target',
        'targetAttr' => 'target_attr',
        'noIndex' => 'no_index',
        'parent' => 'parent_id',
        'cssClass' => 'css_class',
        'password' => 'password',
        'img' => 'img',
        'navPosition' => 'nav_position',
        'legalType' => 'legal_type',
        'showIcon' => 'show_icon',
        'icon' => 'icon',
        'navContext' => 'nav_context',
        'conditions' => 'conditions'
    ];
    protected static array $casts = [
        'conditions' => 'array'
    ];



    public function getNavContext()
    {
        return $this->attributes['navContext'] ?? $this->attributes['nav_context'] ?? 'main';
    }

    public function setNavContext($val)
    {
        if (is_array($val)) {
            $val = implode(',', $val);
        }
        $this->attributes['navContext'] = trim((string)$val);
    }

    /**
     * Get value of one of the 6 visibility/context flags
     */
    public function getVisibilityFlag(string $flag): bool
    {
        $contexts = explode(',', $this->getNavContext());
        $contexts = array_map('trim', $contexts);

        switch ($flag) {
            case 'MP': return in_array('main', $contexts);
            case 'MA': return in_array('avatar', $contexts);
            case 'PP': return in_array('footer', $contexts);
            case 'AUTO': return in_array('auto', $contexts);
            case 'PUB':
                if ($this->getIsHidden()) return false;
                // Public if no auth or role condition
                foreach ($this->getConditions() as $c) {
                    $type = $c['type'] ?? '';
                    if ($type === 'auth' || $type === 'role') return false;
                }
                return true;
            case 'MEM':
                if ($this->getIsHidden()) return false;
                foreach ($this->getConditions() as $c) {
                    if (($c['type'] ?? '') === 'auth' && ($c['value'] ?? false) === true) return true;
                }
                return false;
            case 'ADM':
                if ($this->getIsHidden()) return false;
                foreach ($this->getConditions() as $c) {
                    if (($c['type'] ?? '') === 'role' && ($c['value'] ?? '') === 'admin') return true;
                }
                return false;
            case 'V':
                return !$this->getIsHidden();
        }
        return false;
    }

    /**
     * Update internal state from the 6 visibility/context flags
     */
    public function updateVisibilityFlags(array $flags)
    {
        // 1. Navigation Contexts
        $contexts = [];
        if ($flags['MP'] ?? false) $contexts[] = 'main';
        if ($flags['MA'] ?? false) $contexts[] = 'avatar';
        if ($flags['PP'] ?? false) $contexts[] = 'footer';
        if ($flags['AUTO'] ?? false) $contexts[] = 'auto';
        $this->setNavContext($contexts);

        // 2. Accessibility conditions
        $conditions = $this->getConditions();
        // Remove existing auth/role conditions
        $newConditions = [];
        foreach ($conditions as $c) {
            $type = $c['type'] ?? '';
            if ($type !== 'auth' && $type !== 'role') {
                $newConditions[] = $c;
            }
        }

        // Add requested conditions
        if ($flags['MEM'] ?? false) {
            $newConditions[] = ['type' => 'auth', 'value' => true];
        }
        if ($flags['ADM'] ?? false) {
            $newConditions[] = ['type' => 'role', 'value' => 'admin'];
        }
        
        $this->setConditions($newConditions);

        // 3. hidden/Active logic
        // Item is ACTIVE (isHidden=0) if at least one of PUB, MEM, or ADM is checked
        $isActive = ($flags['PUB'] ?? false) || ($flags['MEM'] ?? false) || ($flags['ADM'] ?? false);
        $this->setIsHidden($isActive ? 0 : 1);
    }

    public function getConditions()
    {
        $val = $this->attributes['conditions'] ?? $this->attributes['conditions'] ?? [];
        return is_array($val) ? $val : (json_decode($val, true) ?: []);
    }

    public function setConditions($val)
    {
        $this->attributes['conditions'] = is_array($val) ? $val : [];
    }

    public function getShowIcon(): bool
    {
        return (bool)($this->attributes['showIcon'] ?? $this->attributes['show_icon'] ?? true);
    }

    public function setShowIcon($val)
    {
        $this->attributes['showIcon'] = (bool)$val;
    }

    public function getIcon()
    {
        return $this->attributes['icon'] ?? '';
    }

    public function setIcon($val)
    {
        $this->attributes['icon'] = trim((string)$val);
    }

    public function setName($val)
    {
        $val = trim($val);
        $this->attributes['name'] = $val;
    }

    public function setPosition($val)
    {
        $this->attributes['position'] = trim($val);
    }

    public function setIsHomepage($val)
    {
        $this->attributes['isHomepage'] = trim($val);
    }

    public function setContent($val)
    {
        $this->attributes['content'] = trim($val);
    }

    public function setIsHidden($val)
    {
        $this->attributes['isHidden'] = intval($val);
    }

    public function setFile($val)
    {
        $this->attributes['file'] = trim($val);
    }

    public function setMainTitle($val)
    {
        $this->attributes['mainTitle'] = trim($val);
    }

    public function setMetaDescriptionTag($val)
    {
        $val = trim($val);
        if (mb_strlen($val) > 150)
            $val = mb_strcut($val, 0, 150) . '...';
        $this->attributes['metaDescriptionTag'] = $val;
    }

    public function setMetaTitleTag($val)
    {
        $val = trim($val);
        if (mb_strlen($val) > 50)
            $val = mb_strcut($val, 0, 50) . '...';
        $this->attributes['metaTitleTag'] = $val;
    }

    public function setTarget($val)
    {
        $this->attributes['target'] = trim($val);
    }

    public function setTargetAttr($val)
    {
        $this->attributes['targetAttr'] = trim($val);
    }

    public function setNoIndex($val)
    {
        $this->attributes['noIndex'] = trim($val);
    }

    public function setParent($val)
    {
        $this->attributes['parent'] = trim($val);
    }

    public function setCssClass($val)
    {
        $this->attributes['cssClass'] = trim($val);
    }

    public function setPassword($val)
    {
        $this->attributes['password'] = ($val == '') ? $val : sha1(trim($val));
    }

    public function setImg($val)
    {
        $this->attributes['img'] = trim($val);
    }

    public function getId()
    {
        return $this->attributes['id'] ?? null;
    }

    public function setId($id)
    {
        $this->attributes['id'] = $id;
    }

    /**
     * @inheritDoc
     */
    public function getResourceId(): string
    {
        return 'page:' . $this->getId();
    }

    /**
     * @inheritDoc
     */
    public function getPolicy(): array
    {
        return $this->getConditions();
    }

    /**
     * @inheritDoc
     */
    public function getVisibilityFlags(): array
    {
        return [
            'MP' => $this->getVisibilityFlag('MP'),
            'MA' => $this->getVisibilityFlag('MA'),
            'PP' => $this->getVisibilityFlag('PP'),
            'AUTO' => $this->getVisibilityFlag('AUTO'),
            'PUB' => $this->getVisibilityFlag('PUB'),
            'MEM' => $this->getVisibilityFlag('MEM'),
            'ADM' => $this->getVisibilityFlag('ADM'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getResourceParent(): ?ResourceInterface
    {
        $parentId = (int) $this->getParent();
        if ($parentId <= 0) {
            return null;
        }

        $page = new Page();
        $parent = $page->create($parentId);
        return $parent instanceof ResourceInterface ? $parent : null;
    }

    public function getName()
    {
        return $this->attributes['name'] ?? '';
    }

    public function getPosition()
    {
        return $this->attributes['position'] ?? 0;
    }

    public function getIsHomepage()
    {
        return $this->attributes['isHomepage'] ?? $this->attributes['is_homepage'] ?? 0;
    }

    public function getContent()
    {
        return $this->attributes['content'] ?? '';
    }

    /**
     * Retourne le contenu HTML décodé et interprété via ContentParser (shortcodes).
     */
    public function getParsedContent(): string
    {
        $decoded = htmlspecialchars_decode((string) $this->getContent());
        if (class_exists('\Content\ContentParser')) {
            return (new \Content\ContentParser($decoded))->getParsedContent();
        }
        return $decoded;
    }

    public function getIsHidden()
    {
        return $this->attributes['isHidden'] ?? $this->attributes['is_hidden'] ?? 0;
    }

    public function getFile()
    {
        return $this->attributes['file'] ?? '';
    }

    public function getMainTitle()
    {
        return $this->attributes['mainTitle'] ?? $this->attributes['main_title'] ?? '';
    }

    public function getMetaDescriptionTag()
    {
        return $this->attributes['metaDescriptionTag'] ?? $this->attributes['meta_description_tag'] ?? '';
    }

    public function getMetaTitleTag()
    {
        return $this->attributes['metaTitleTag'] ?? $this->attributes['meta_title_tag'] ?? '';
    }

    public function getTarget()
    {
        $val = $this->attributes['target'] ?? '';
        if ($val == 'url')
            return '';
        return $val;
    }

    public function getTargetAttr()
    {
        return $this->attributes['targetAttr'] ?? $this->attributes['target_attr'] ?? '_self';
    }

    public function getNoIndex()
    {
        return $this->attributes['noIndex'] ?? $this->attributes['no_index'] ?? 0;
    }

    public function getParent()
    {
        return $this->attributes['parent'] ?? $this->attributes['parent_id'] ?? 0;
    }

    public function getCssClass()
    {
        return $this->attributes['cssClass'] ?? $this->attributes['css_class'] ?? '';
    }

    public function getPassword()
    {
        return $this->attributes['password'] ?? '';
    }

    public function getImg()
    {
        return $this->attributes['img'] ?? '';
    }

    public function getImgUrl()
    {
        return Util::urlBuild(UPLOAD . 'galerie/' . $this->getImg());
    }

    public function targetIs()
    {
        $target = $this->getTarget();
        if ($target == '')
            return 'page';
        elseif ($target == 'javascript:')
            return 'parent';
        elseif (filter_var($target, FILTER_VALIDATE_URL) || $target == 'url')
            return 'url';
        else
            return 'plugin';
    }

    public function isVisibleOnList(): bool
    {
        return $this->targetIs() != "plugin" || ($this->targetIs() == "plugin" && PluginsManager::isActivePlugin($this->getTarget()));
    }

    public function getNavPosition()
    {
        return $this->attributes['navPosition'] ?? $this->attributes['nav_position'] ?? 'menu';
    }

    public function setNavPosition($val)
    {
        $this->attributes['navPosition'] = trim($val);
    }

    public function getLegalType()
    {
        return $this->attributes['legalType'] ?? $this->attributes['legal_type'] ?? 'none';
    }

    public function setLegalType($val)
    {
        $this->attributes['legalType'] = trim($val);
    }

    public function __toString()
    {
        return '';
    }
}


