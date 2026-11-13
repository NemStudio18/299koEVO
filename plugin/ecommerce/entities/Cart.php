<?php

namespace Ecommerce\Entities;

defined('ROOT') or exit('Access denied!');

class Cart
{
    private static $sessionKey = 'ecommerce_cart';

    public static function get(): array
    {
        if (!isset($_SESSION[self::$sessionKey])) {
            $_SESSION[self::$sessionKey] = [];
        }
        return $_SESSION[self::$sessionKey];
    }

    public static function add(int $productId, int $quantity = 1): void
    {
        $cart = self::get();
        if (isset($cart[$productId])) {
            $cart[$productId] += $quantity;
        } else {
            $cart[$productId] = $quantity;
        }
        $_SESSION[self::$sessionKey] = $cart;
    }

    public static function update(int $productId, int $quantity): void
    {
        $cart = self::get();
        if ($quantity <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = $quantity;
        }
        $_SESSION[self::$sessionKey] = $cart;
    }

    public static function remove(int $productId): void
    {
        $cart = self::get();
        unset($cart[$productId]);
        $_SESSION[self::$sessionKey] = $cart;
    }

    public static function clear(): void
    {
        $_SESSION[self::$sessionKey] = [];
    }

    public static function getItemsWithProducts(): array
    {
        $cart = self::get();
        if (empty($cart)) {
            return [];
        }
        
        $productManager = new ProductManager();
        $items = [];
        
        foreach ($cart as $productId => $quantity) {
            $product = $productManager->getById($productId);
            if ($product && $product->isActive()) {
                $items[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $product->getPrice() * $quantity,
                ];
            }
        }
        
        return $items;
    }

    public static function getTotal(): float
    {
        $items = self::getItemsWithProducts();
        $total = 0;
        foreach ($items as $item) {
            $total += $item['subtotal'];
        }
        return $total;
    }

    public static function getItemCount(): int
    {
        $cart = self::get();
        return array_sum($cart);
    }
}

