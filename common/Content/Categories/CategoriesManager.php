<?php

namespace Content\Categories;

use Content\Categories\Category;
use Core\Core;
use Utils\Util;

/**
 * @copyright (C) 2022, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('Access denied!');

abstract class CategoriesManager {

  
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
     * className is the name of class who is be used to create new categories, like 'Category' or 'BlogCategory'
     * @var string
     */
    protected string $className;

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

    public $categories = [];
    public $imbricatedCategories = [];

    protected static $file;

    public function __construct() {
        self::$file = DATA_PLUGIN . $this->pluginId . '/categories-' . $this->name . '.json';
        $this->getCategoriesFromMetas();
    }

    public function getCategory(int $id) {
        if (isset($this->categories[$id])) {
            return clone $this->categories[$id];
        }
        return false;
    }
    
    public function getCategories() {
        return $this->categories;
    }
    
    public function getNestedCategories() {
        return $this->imbricatedCategories;
    }

    public function isCategoryExist(int $categoryId) {
        return (isset($this->categories[$categoryId]));
    }

    public function createCategory($label, int $parentId) {
        $cat = new $this->className();
        $cat->label = $label;
        $cat->parentId = $parentId;
        $cat->id = $this->findNextId();
        $this->categories[$cat->id] = $cat;
        $this->rebuildChildrenIndexes();
        $this->sortCategories($this->categories);
        $this->imbricateCategories();
        $this->saveCategories();
        return $cat->id;
    }

    abstract public function getAddCategoryUrl():string;

    abstract public function getDeleteUrl():string;

    abstract public function getAjaxDisplayListUrl():string;

    public function saveCategory(Category $category) {
        $this->categories[$category->id] = $category;
        $this->rebuildChildrenIndexes();
        $this->sortCategories($this->categories);
        $this->imbricateCategories();
        $this->saveCategories();
        return true;
    }

    public function getCategoriesWhereItem(int $itemId) {
        $cats = [];
        foreach ($this->categories as $cat) {
            if (in_array($itemId, $cat->items)) {
                $cats[$cat->id] = $cat;
            }
        }
        return $cats;
    }

    public function deleteCategory($id) {
        if (!isset($this->categories[$id])) {
            return false;
        }
        $cat = $this->categories[$id];
        foreach ($cat->childrenId as $childId) {
            // Childs Categories are affected to the category deleted parent
            $this->categories[$childId]->parentId = $cat->parentId;
        }
        Core::getInstance()->callHook('categoriesDeleteCategory', [$id, $this->pluginId]);
        //Delete the categorie
        unset($this->categories[$id]);
        $this->rebuildChildrenIndexes();
        $this->sortCategories($this->categories);
        $this->imbricateCategories();
        $this->saveCategories();
        return true;
    }

    protected function saveCategories() {
        $this->rebuildChildrenIndexes();
        $metas = [];
        foreach ($this->categories as $cat) {
            $metas[$cat->id] = $cat;
        }
        Util::writeJsonFile(self::$file, $metas);
    }

    protected function getCategoriesFromMetas() {
        $this->categories = [];
        if (!is_file(self::$file)) {
            return [];
        }
        $metas = Util::readJsonFile(self::$file);
        foreach ($metas as $k => $v) {
            $this->categories[$k] = new $this->className($k, $v);
        }
        $this->rebuildChildrenIndexes();
        $this->sortCategories($this->categories);
        $this->imbricateCategories();
    }

    protected function sortCategories(array &$categories): void {
        uasort($categories, function($a, $b) {
            return strcmp($a->label, $b->label);
        });
    }

    protected function rebuildChildrenIndexes(): void {
        if (empty($this->categories)) {
            return;
        }

        foreach ($this->categories as $category) {
            $category->childrenId = [];
            $category->hasChildren = false;
        }

        foreach ($this->categories as $category) {
            if ($category->parentId !== 0 && isset($this->categories[$category->parentId])) {
                $parent = $this->categories[$category->parentId];
                $parent->childrenId[] = $category->id;
                $parent->childrenId = array_values(array_unique($parent->childrenId));
                $parent->hasChildren = true;
            }
        }
    }

    protected function imbricateCategories() {
        if ($this->nested === false) {
            $this->imbricatedCategories = $this->categories;
            $this->calcDepth($this->imbricatedCategories, 0);
            return;
        }

        $byParent = [];
        foreach ($this->categories as $category) {
            $category->children = [];
            $category->hasChildren = false;
            $category->isChild = ($category->parentId !== 0);
            $byParent[$category->parentId][] = $category;
        }

        $buildTree = function(int $parentId) use (&$buildTree, &$byParent) {
            $nodes = $byParent[$parentId] ?? [];
            foreach ($nodes as $node) {
                $children = $buildTree($node->id);
                if (!empty($children)) {
                    $node->hasChildren = true;
                    foreach ($children as $child) {
                        $node->children[$child->id] = $child;
                    }
                }
            }
            return $nodes;
        };

        $this->imbricatedCategories = $buildTree(0);
        $this->calcDepth($this->imbricatedCategories, 0);
    }
    
    protected function calcDepth(&$categories, int $depth = 0) {
        foreach ($categories as &$cat) {
            $cat->depth = $depth;
            if ($cat->hasChildren) {
                $this->calcDepth($cat->children, $depth + 1);
            }
        }
    }

    public function outputAsCheckbox($itemId = 0) {
        $catDisplay = 'root';
        ob_start();
        require COMMON . 'Content' . DS . 'Categories' . DS . 'Template' . DS . 'checkboxCategories.php';
        return ob_get_clean();
    }

    public function outputAsSelect($parentId, $categoryId, $fieldName = "parentId") {
        $catDisplay = 'root';
        ob_start();
        require COMMON . 'Content' . DS . 'Categories' . DS . 'Template' . DS . 'selectCategory.php';
        return ob_get_clean();
    }
    
    public function outputAsSelectOne($itemId, $fieldName = 'category-one') {
        $catDisplay = 'root';
        ob_start();
        require COMMON . 'Content' . DS . 'Categories' . DS . 'Template' . DS . 'selectOneCategory.php';
        return ob_get_clean();
    }

    public function outputAsList() {
        $catDisplay = 'root';
        ob_start();
        require COMMON . 'Content' . DS . 'Categories' . DS . 'Template' . DS . 'listCategories.php';
        return ob_get_clean();
    }

    public function getPrefix() {
        return $this->pluginId . '.' . $this->name . '.';
    }

    public static function getAllCategoriesPluginId() {
        $metas = self::getMetas();
        return array_keys($metas);
    }

    public static function saveItemToCategories($itemId, $categoriesId) {
        $metas = self::getMetas();
        if (!is_array($categoriesId)) {
            $categoriesId = [$categoriesId];
        }
        $categories = [];
        foreach ($metas as $cat) {
            $key = array_search($itemId, $cat['items'], true);
            if ($key !== false) {
                // Item is here. We delete it before see if it is in categorie anymore
                unset($cat['items'][$key]);
            }
            if (in_array($cat['id'], $categoriesId)) {
                // Categorie has been checked
                array_push($cat['items'], $itemId);
                $cat['items'] = array_values(array_unique($cat['items']));
            }
            $categories[$cat['id']] = $cat;
        }
        Util::writeJsonFile(self::$file, $categories);
    }
    
    
    public static function deleteItemFromCategories($itemId, $categoriesId = []) {
        $metas = self::getMetas();
        if (!is_array($categoriesId)) {
            $categoriesId = [$categoriesId];
        }
        $categories = [];
        foreach ($metas as $cat) {
            $key = array_search($itemId, $cat['items'], true);
            if ($key !== false && (empty($categoriesId) || in_array($cat['id'], $categoriesId))) {
                // Item is here. We delete it
                unset($cat['items'][$key]);
                $cat['items'] = array_values($cat['items']);
            }
            $categories[$cat['id']] = $cat;
        }
        Util::writeJsonFile(self::$file, $categories);
    }

    protected static function getMetas() {
        if (!is_file(self::$file)) {
            return [];
        }
        return Util::readJsonFile(self::$file);
    }

    protected function findNextId(): int {
        if (!file_exists(self::$file)) {
            return 1;
        }
        $cats = Util::readJsonFile(self::$file);
        if (empty($cats)) {
            return 1;
        }
        return max(array_column($cats, 'id')) + 1;
    }

}
