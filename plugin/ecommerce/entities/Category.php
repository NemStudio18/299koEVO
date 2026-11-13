<?php

namespace Ecommerce\Entities;

defined('ROOT') or exit('Access denied!');

class Category extends \Core\Storage\JsonActiveRecord
{
    protected static string $filePath = DATA_PLUGIN . 'ecommerce/categories.json';
    protected static string $tableName = 'ecommerce_categories';
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

    public function getParentId(): ?int
    {
        return isset($this->attributes['parent_id']) ? (int)$this->attributes['parent_id'] : null;
    }

    public function getUrl(): string
    {
        $router = \Core\Router\Router::getInstance();
        return $router->generate('ecommerce-category', ['id' => $this->getId()]);
    }
}

