<?php

namespace Forum\Entities;

use Utils\Util;

defined('ROOT') or exit('Access denied!');

class CategoryManager
{
    public function __construct()
    {
    }

    /**
     * Vérifie si une catégorie est de niveau 2 (a un parent qui est lui-même racine)
     */
    public function isLevel2(Category $category): bool
    {
        if ($category->getParentId() === null) {
            return false;
        }
        $parent = $this->getById($category->getParentId());
        return $parent && $parent->getParentId() === null;
    }

    /**
     * @return Category[]
     */
    public function getAll(): array
    {
        $categories = Category::query()
            ->where('parent_id', 'IS NULL', null)
            ->orderBy('order_index', 'ASC')
            ->all();

        foreach ($categories as $category) {
            $counts = $this->getRecursiveCounts($category->id);
            $category->setTopicCount($counts['topic_count']);
            $category->setPostCount($counts['post_count']);
            $category->setSubcategories($this->getSubcategoriesRecursive($category->id));
        }
        return $categories;
    }

    /**
     * Récupère les sous-catégories récursivement
     */
    /**
     * @return Category[]
     */
    public function getSubcategoriesRecursive(int $parentId): array
    {
        /** @var Category[] $subcategories */
        $subcategories = Category::query()
            ->where('parent_id', '=', $parentId)
            ->orderBy('order_index', 'ASC')
            ->all();

        foreach ($subcategories as $category) {
            $counts = $this->getRecursiveCounts($category->id);
            $category->setTopicCount($counts['topic_count']);
            $category->setPostCount($counts['post_count']);
            
            // Appel récursif
            $subs = $this->getSubcategoriesRecursive($category->id);
            $category->setSubcategories($subs);
            
            $subcategories[] = $category;
        }
        return $subcategories;
    }
    
    /**
     * Récupère récursivement tous les IDs de catégories enfants (sous-catégories)
     */
    private function getChildCategoryIds(int $parentId, int $depth = 0): array
    {
        if ($depth > 10) return [$parentId];
        
        $ids = [$parentId];
        $children = Category::query()->where('parent_id', '=', $parentId)->all();
        foreach ($children as $child) {
            $ids = array_merge($ids, $this->getChildCategoryIds((int)$child->id, $depth + 1));
        }
        return array_unique($ids);
    }
    
    /**
     * Calcule les compteurs de topics et posts de manière récursive
     */
    private function getRecursiveCounts(int $categoryId): array
    {
        $categoryIds = $this->getChildCategoryIds($categoryId);
        
        $topicCount = Topic::query()->whereIn('category_id', $categoryIds)->count();
        
        // Pour les posts, on doit passer par les topics
        $topicIds = array_map(fn($t) => $t->id, Topic::query()->whereIn('category_id', $categoryIds)->all());
        $postCount = 0;
        if (!empty($topicIds)) {
            $postCount = Post::query()
                ->whereIn('topic_id', $topicIds)
                ->where('is_approved', '=', 1)
                ->count();
        }

        return [
            'topic_count' => $topicCount,
            'post_count' => $postCount,
        ];
    }
    
    /**
     * @return Category[]
     */
    public function getSubcategories(int $parentId): array
    {
        /** @var Category[] $categories */
        $categories = Category::query()
            ->where('parent_id', '=', $parentId)
            ->orderBy('order_index', 'ASC')
            ->orderBy('name', 'ASC')
            ->get();
        
        foreach ($categories as $category) {
            // Calculer les compteurs récursifs (incluant les sous-catégories)
            $counts = $this->getRecursiveCounts($category->getId());
            $category->setTopicCount($counts['topic_count']);
            $category->setPostCount($counts['post_count']);
        }
        return $categories;
    }
    
    /**
     * @return Category[]
     */
    public function getAllWithSubcategories(): array
    {
        // Récupérer toutes les catégories (y compris les sous-catégories)
        /** @var Category[] $rows */
        $rows = Category::query()
            ->orderBy('parent_id', 'ASC')
            ->orderBy('order_index', 'ASC')
            ->orderBy('name', 'ASC')
            ->get();
        
        $allCategories = [];
        $categoriesMap = [];
        
        // Créer toutes les catégories avec compteurs récursifs
        foreach ($rows as $category) {
            // Calculer les compteurs récursifs (incluant les sous-catégories)
            $counts = $this->getRecursiveCounts($category->getId());
            $category->setTopicCount($counts['topic_count']);
            $category->setPostCount($counts['post_count']);
            $allCategories[] = $category;
            $categoriesMap[$category->getId()] = $category;
        }
        
        // Organiser en hiérarchie
        $rootCategories = [];
        foreach ($allCategories as $category) {
            if ($category->getParentId() === null) {
                $rootCategories[] = $category;
            } else {
                $parent = $categoriesMap[$category->getParentId()] ?? null;
                if ($parent) {
                    $parentSubs = $parent->getSubcategories();
                    $parentSubs[] = $category;
                    $parent->setSubcategories($parentSubs);
                }
            }
        }
        
        return $rootCategories;
    }

    public function getById(int $id): ?Category
    {
        /** @var Category|null $category */
        $category = Category::findPK($id);
        if ($category) {
            $counts = $this->getRecursiveCounts($category->id);
            $category->setTopicCount($counts['topic_count']);
            $category->setPostCount($counts['post_count']);
        }
        return $category;
    }

    public function create(array $data): ?Category
    {
        if (empty($data['slug'])) {
            $data['slug'] = Util::strToUrl($data['name']);
        }
        
        $category = new Category($data);
        if ($category->save()) {
            return $category;
        }
        return null;
    }

    public function update(int $id, array $data): bool
    {
        $category = Category::findPK($id);
        if (!$category) return false;

        if (isset($data['name']) && empty($data['slug'])) {
            $data['slug'] = Util::strToUrl($data['name']);
        }
        
        foreach ($data as $key => $value) {
            $category->$key = $value;
        }
        
        return $category->save();
    }

    public function delete(int $id): bool
    {
        return Category::deletePK($id);
    }
    
    /**
     * Récupère le chemin complet d'une catégorie (toutes les catégories parentes jusqu'à la racine)
     * Retourne un tableau de catégories du parent le plus éloigné jusqu'à la catégorie elle-même
     * 
     * @param int $categoryId ID de la catégorie
     * @return array Tableau de Category, du parent racine jusqu'à la catégorie
     */
    public function getCategoryPath(int $categoryId): array
    {
        $path = [];
        $currentId = $categoryId;
        $visited = []; // Protection contre les boucles infinies
        
        while ($currentId !== null && !in_array($currentId, $visited)) {
            $visited[] = $currentId;
            $category = $this->getById($currentId);
            if (!$category) {
                break;
            }
            
            // Ajouter la catégorie au début du chemin (pour avoir parent -> enfant -> ...)
            array_unshift($path, $category);
            
            // Remonter au parent
            $currentId = $category->getParentId();
        }
        
        return $path;
    }

    public function getOrphanSubcategories(): array
    {
        // On récupère tous les IDs de catégories existantes
        $allIds = array_map(fn($c) => $c->id, Category::query()->get());
        
        // On cherche celles qui ont un parent_id qui n'est pas dans la liste
        $allSubcategories = Category::query()->where('parent_id', 'IS NOT NULL', null)->get();
        
        $orphans = [];
        foreach ($allSubcategories as $sub) {
            if (!in_array($sub->parent_id, $allIds)) {
                $orphans[] = $sub;
            }
        }
        return $orphans;
    }

    public function recoverOrphans(int $targetCategoryId): int
    {
        $orphans = $this->getOrphanSubcategories();
        $count = 0;
        foreach ($orphans as $orphan) {
            $orphan->parent_id = $targetCategoryId;
            if ($orphan->save()) {
                $count++;
            }
        }
        return $count;
    }
}

