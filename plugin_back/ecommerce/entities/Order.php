<?php

namespace Ecommerce\Entities;

defined('ROOT') or exit('Access denied!');

class Order extends \Core\Storage\JsonActiveRecord
{
    protected static string $filePath = DATA_PLUGIN . 'ecommerce/orders.json';
    protected static string $tableName = 'orders';
    protected static string $primaryKey = 'id';

    public function getId(): ?int
    {
        return isset($this->attributes['id']) ? (int)$this->attributes['id'] : null;
    }

    public function getOrderNumber(): string
    {
        return $this->attributes['order_number'] ?? '';
    }

    public function getCustomerName(): string
    {
        return $this->attributes['customer_name'] ?? '';
    }

    public function getCustomerEmail(): string
    {
        return $this->attributes['customer_email'] ?? '';
    }

    public function getCustomerAddress(): string
    {
        return $this->attributes['customer_address'] ?? '';
    }

    public function getCustomerPhone(): string
    {
        return $this->attributes['customer_phone'] ?? '';
    }

    public function getTotal(): float
    {
        return (float)($this->attributes['total'] ?? 0);
    }

    public function getStatus(): string
    {
        return $this->attributes['status'] ?? 'pending';
    }

    public function getPaymentMethod(): string
    {
        return $this->attributes['payment_method'] ?? '';
    }

    /**
     * @return OrderItem[]
     */
    public function getItems(): array
    {
        return OrderItem::queryBuilder()
            ->where('order_id', '=', $this->getId())
            ->get();
    }
}

