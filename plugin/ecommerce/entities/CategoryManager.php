<?php

namespace Ecommerce\Entities;

use Utils\Util;

defined('ROOT') or exit('Access denied!');

class CategoryManager
{
    public function getAll(): array
    {
        return Category::queryBuilder()->orderBy('name', 'ASC')->get();
    }

    public function getById(int $id): ?Category
    {
        return Category::findPK($id);
    }

    public function getBySlug(string $slug): ?Category
    {
        return Category::find('slug', $slug);
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
        $category = $this->getById($id);
        if (!$category) {
            return false;
        }
        
        if (isset($data['name']) && empty($data['slug'])) {
            $data['slug'] = Util::strToUrl($data['name']);
        }
        
        foreach ($data as $key => $value) {
            $category->{$key} = $value;
        }
        
        return $category->save();
    }

    public function delete(int $id): bool
    {
        $category = $this->getById($id);
        return $category ? $category->delete() : false;
    }
}

