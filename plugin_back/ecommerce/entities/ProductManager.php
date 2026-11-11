<?php

namespace Ecommerce\Entities;

use Utils\Util;

defined('ROOT') or exit('Access denied!');

class ProductManager
{
    public function getAll(bool $activeOnly = false): array
    {
        $query = Product::queryBuilder();
        if ($activeOnly) {
            $query->where('active', '=', 1);
        }
        return $query->orderBy('created_at', 'DESC')->get();
    }

    public function getById(int $id): ?Product
    {
        return Product::findPK($id);
    }

    public function getBySlug(string $slug): ?Product
    {
        return Product::find('slug', $slug);
    }

    public function getByCategory(int $categoryId, bool $activeOnly = false): array
    {
        $query = Product::queryBuilder()->where('category_id', '=', $categoryId);
        if ($activeOnly) {
            $query->where('active', '=', 1);
        }
        return $query->orderBy('created_at', 'DESC')->get();
    }

    public function create(array $data): ?Product
    {
        if (empty($data['slug'])) {
            $data['slug'] = Util::strToUrl($data['name']);
        }
        
        $product = new Product($data);
        if ($product->save()) {
            return $product;
        }
        return null;
    }

    public function update(int $id, array $data): bool
    {
        $product = $this->getById($id);
        if (!$product) {
            return false;
        }
        
        if (isset($data['name']) && empty($data['slug'])) {
            $data['slug'] = Util::strToUrl($data['name']);
        }
        
        foreach ($data as $key => $value) {
            $product->{$key} = $value;
        }
        
        return $product->save();
    }

    public function delete(int $id): bool
    {
        $product = $this->getById($id);
        return $product ? $product->delete() : false;
    }

    public function decreaseStock(int $id, int $quantity): bool
    {
        $product = $this->getById($id);
        if (!$product || $product->getStock() < $quantity) {
            return false;
        }
        
        $product->stock = $product->getStock() - $quantity;
        return $product->save();
    }
}

