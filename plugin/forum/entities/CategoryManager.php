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
        return $this->getSubcategoriesRecursive(null, 3);
    }

    /**
     * Récupère les catégories et sous-catégories récursivement
     *
     * @return Category[]
     */
    public function getSubcategoriesRecursive(?int $parentId = null, int $maxLevel = 3, int $currentLevel = 1): array
    {
        $query = Category::query();
        if ($parentId === null) {
            $query->where('parent_id', 'IS NULL', null);
        } else {
            $query->where('parent_id', '=', $parentId);
        }

        /** @var Category[] $subcategories */
        $subcategories = $query
            ->orderBy('order_index', 'ASC')
            ->orderBy('name', 'ASC')
            ->get();

        foreach ($subcategories as $category) {
            $counts = $this->getRecursiveCounts((int) $category->id);
            $category->setTopicCount($counts['topic_count']);
            $category->setPostCount($counts['post_count']);

            if ($currentLevel < $maxLevel) {
                $subs = $this->getSubcategoriesRecursive((int) $category->id, $maxLevel, $currentLevel + 1);
                $category->setSubcategories($subs);
            } else {
                $category->setSubcategories([]);
            }
        }
        return $subcategories;
    }

    /**
     * Récupère récursivement tous les IDs de catégories enfants (sous-catégories)
     */
    private function getChildCategoryIds(int $parentId, int $depth = 0): array
    {
        if ($depth > 10) {
            return [$parentId];
        }

        $ids = [$parentId];
        $children = Category::query()->where('parent_id', '=', $parentId)->get();
        foreach ($children as $child) {
            $ids = array_merge($ids, $this->getChildCategoryIds((int) $child->id, $depth + 1));
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

        $topicIds = array_map(fn($t) => $t->id, Topic::query()->whereIn('category_id', $categoryIds)->get());
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
        /** @var Category[] $rows */
        $rows = Category::query()
            ->orderBy('parent_id', 'ASC')
            ->orderBy('order_index', 'ASC')
            ->orderBy('name', 'ASC')
            ->get();

        $allCategories = [];
        $categoriesMap = [];

        foreach ($rows as $category) {
            $counts = $this->getRecursiveCounts($category->getId());
            $category->setTopicCount($counts['topic_count']);
            $category->setPostCount($counts['post_count']);
            $allCategories[] = $category;
            $categoriesMap[$category->getId()] = $category;
        }

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
            $counts = $this->getRecursiveCounts((int) $category->id);
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
        if (!$category) {
            return false;
        }

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
     * Récupère le chemin complet d'une catégorie (du parent racine jusqu'à la catégorie)
     *
     * @return Category[]
     */
    public function getCategoryPath(int $categoryId): array
    {
        $path = [];
        $currentId = $categoryId;
        $visited = [];

        while ($currentId !== null && !in_array($currentId, $visited, true)) {
            $visited[] = $currentId;
            $category = $this->getById($currentId);
            if (!$category) {
                break;
            }

            array_unshift($path, $category);
            $currentId = $category->getParentId();
        }

        return $path;
    }

    public function getOrphanSubcategories(): array
    {
        $allIds = array_map(fn($c) => (int) $c->id, Category::query()->get());
        $allSubcategories = Category::query()->where('parent_id', 'IS NOT NULL', null)->get();

        $orphans = [];
        foreach ($allSubcategories as $sub) {
            if (!in_array((int) $sub->parent_id, $allIds, true)) {
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
