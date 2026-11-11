<?php

namespace Ecommerce\Entities;

defined('ROOT') or exit('Access denied!');

class OrderManager
{
    public function getAll(): array
    {
        return Order::queryBuilder()->orderBy('created_at', 'DESC')->get();
    }

    public function getById(int $id): ?Order
    {
        return Order::findPK($id);
    }

    public function getByOrderNumber(string $orderNumber): ?Order
    {
        return Order::find('order_number', $orderNumber);
    }

    public function create(array $data, array $items): ?Order
    {
        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        $data['order_number'] = $orderNumber;
        
        $order = new Order($data);
        
        if ($order->save()) {
            $orderId = $order->getId();
            
            // Insert order items
            foreach ($items as $item) {
                $orderItem = new OrderItem([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
                $orderItem->save();
            }
            
            return $order;
        }
        return null;
    }

    public function updateStatus(int $id, string $status): bool
    {
        $order = $this->getById($id);
        if (!$order) {
            return false;
        }
        
        $order->status = $status;
        return $order->save();
    }
}

