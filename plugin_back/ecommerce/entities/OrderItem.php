<?php

namespace Ecommerce\Entities;

defined('ROOT') or exit('Access denied!');

class OrderItem extends \Core\Storage\JsonActiveRecord
{
    protected static string $filePath = DATA_PLUGIN . 'ecommerce/order_items.json';
    protected static string $tableName = 'order_items';
    protected static string $primaryKey = 'id';

    public function getId(): ?int
    {
        return isset($this->attributes['id']) ? (int)$this->attributes['id'] : null;
    }

    public function getOrderId(): int
    {
        return (int)($this->attributes['order_id'] ?? 0);
    }

    public function getProductId(): int
    {
        return (int)($this->attributes['product_id'] ?? 0);
    }

    public function getProductName(): string
    {
        return $this->attributes['product_name'] ?? '';
    }

    public function getQuantity(): int
    {
        return (int)($this->attributes['quantity'] ?? 0);
    }

    public function getPrice(): float
    {
        return (float)($this->attributes['price'] ?? 0);
    }
}
