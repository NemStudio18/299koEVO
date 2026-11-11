<?php

namespace Content\Categories;

use Core\Storage\JsonActiveRecord;
use Utils\Util;

/**
 * @copyright (C) 2022, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('Access denied!');

abstract class Category implements \JsonSerializable {

    public $id;
    /**
     * plugin ID, like 'blog'
     * @var string
     */
    protected string $pluginId;

    /**
     * Name of categories, like 'categories' or 'tags'
     * @var string
     */
    protected string $name;

    /**
     * Categories can be nested or not. For example, for 'tags', $nested will be set to false
     * @var bool
     */
    protected bool $nested;

    /**
     * If user can choose multiple categories for an item
     * @var bool
     */
    protected bool $chooseMany;
    
    public $items = [];
    public $label = '';
    public int $parentId;
    public array $childrenId = [];
    public array $children = [];
    public bool $isChild = false;
    public bool $hasChildren = false;
    public int $depth = 0;
    
    public array $pluginArgs = [];

    protected static string $file;

    public function __construct(int $id = -1, ?array $data = null) {
        $this->id = $id;
        self::$file = DATA_PLUGIN . $this->pluginId . '/categories-' . $this->name . '.json';
        if ($this->id !== -1) {
            if ($data === null) {
                $metas = Util::readJsonFile(self::$file);
                $data = $metas[$this->id] ?? [];
            }
            $this->hydrate($data ?? []);
        }
    }

    protected function hydrate(array $data): void {
        $this->items = $data['items'] ?? [];
        $this->label = $data['label'] ?? '';
        $this->childrenId = $data['childrenId'] ?? [];
        $this->parentId = $data['parentId'] ?? 0;
        $this->pluginArgs = $data['pluginArgs'] ?? [];
    }


    public function jsonSerialize():array {
        return
                ['items' => $this->items,
                    'label' => $this->label,
                    'id' => $this->id,
                    'parentId' => $this->parentId,
                    'childrenId' => $this->childrenId,
                    'pluginArgs' => $this->pluginArgs];
    }

    public function outputAsCheckbox($itemId) {
        $catDisplay = 'sub';
        require COMMON . 'Content' . DS . 'Categories' . DS . 'Template' . DS . 'checkboxCategories.php';
    }
    
    public function outputAsSelect($parentId, $categorieId) {
        $catDisplay = 'sub';
        require COMMON . 'Content' . DS . 'Categories' . DS . 'Template' . DS . 'selectCategory.php';
    }
    
    public function outputAsSelectOne($itemId) {
        $catDisplay = 'sub';
        require COMMON . 'Content' . DS . 'Categories' . DS . 'Template' . DS . 'selectOneCategory.php';
    }

    public function outputAsList() {
        $catDisplay = 'sub';
        require COMMON . 'Content' . DS . 'Categories' . DS . 'Template' . DS . 'listCategories.php';
    }

    public function getCategoryById(int $id) {
        if ($id === $this->id) {
            // We search this categorie
            return $this;
        }
        if (empty($this->children)) {
            // No child
            return false;
        }
        foreach ($this->children as $parent) {
            // Search in childs
            $res = $parent->getCategoryById($id);
            if (is_object($res) && get_class($res) === get_class($this)) {
                return $res;
            }
        }
        return false;
    }

    public function addChild(Category $category) {
        $category->isChild = true;
        $this->hasChildren = true;
        $this->children[$category->id] = $category;
    }

    public function getPrefix() {
        return $this->pluginId . '.' . $this->name . '.';
    }

}
