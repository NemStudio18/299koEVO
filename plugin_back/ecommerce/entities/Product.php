<?php

namespace Ecommerce\Entities;

use Utils\Util;

defined('ROOT') or exit('Access denied!');

class Product extends \Core\Storage\JsonActiveRecord
{
    protected static string $filePath = DATA_PLUGIN . 'ecommerce/products.json';
    protected static string $tableName = 'products';
    protected static string $primaryKey = 'id';

    public function getId(): ?int
    {
        return isset($this->attributes['id']) ? (int)$this->attributes['id'] : null;
    }

    public function getName(): string
    {
        return $this->attributes['name'] ?? '';
    }

    public function getSlug(): string
    {
        return $this->attributes['slug'] ?? '';
    }

    public function getDescription(): string
    {
        return $this->attributes['description'] ?? '';
    }

    public function getPrice(): float
    {
        return (float)($this->attributes['price'] ?? 0);
    }

    public function getStock(): int
    {
        return (int)($this->attributes['stock'] ?? 0);
    }

    public function getImage(): string
    {
        return $this->attributes['image'] ?? '';
    }

    public function getCategoryId(): ?int
    {
        return isset($this->attributes['category_id']) ? (int)$this->attributes['category_id'] : null;
    }

    public function isActive(): bool
    {
        return (int)($this->attributes['active'] ?? 1) === 1;
    }

    public function isInStock(): bool
    {
        return $this->getStock() > 0;
    }

    public function getUrl(): string
    {
        $router = \Core\Router\Router::getInstance();
        return $router->generate('ecommerce-product', ['id' => $this->getId()]);
    }
}

