<?php

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('Access denied!');

$router = \Core\Router\Router::getInstance();

// Admin routes
$router->map('GET', '/admin/ecommerce[/?]', '\\Ecommerce\\Controllers\\EcommerceAdminController#home', 'admin-ecommerce-home');
$router->map('GET', '/admin/ecommerce/products[/?]', '\\Ecommerce\\Controllers\\EcommerceAdminProductsController#list', 'admin-ecommerce-products');
$router->map('GET', '/admin/ecommerce/products/add', '\\Ecommerce\\Controllers\\EcommerceAdminProductsController#add', 'admin-ecommerce-products-add');
$router->map('POST', '/admin/ecommerce/products/add', '\\Ecommerce\\Controllers\\EcommerceAdminProductsController#addSend', 'admin-ecommerce-products-add-send');
$router->map('GET', '/admin/ecommerce/products/edit/[i:id]', '\\Ecommerce\\Controllers\\EcommerceAdminProductsController#edit', 'admin-ecommerce-products-edit');
$router->map('POST', '/admin/ecommerce/products/edit/[i:id]', '\\Ecommerce\\Controllers\\EcommerceAdminProductsController#editSend', 'admin-ecommerce-products-edit-send');
$router->map('GET', '/admin/ecommerce/products/delete/[i:id]', '\\Ecommerce\\Controllers\\EcommerceAdminProductsController#delete', 'admin-ecommerce-products-delete');
$router->map('GET', '/admin/ecommerce/orders[/?]', '\\Ecommerce\\Controllers\\EcommerceAdminOrdersController#list', 'admin-ecommerce-orders');
$router->map('GET', '/admin/ecommerce/orders/view/[i:id]', '\\Ecommerce\\Controllers\\EcommerceAdminOrdersController#view', 'admin-ecommerce-orders-view');
$router->map('POST', '/admin/ecommerce/orders/update-status/[i:id]', '\\Ecommerce\\Controllers\\EcommerceAdminOrdersController#updateStatus', 'admin-ecommerce-orders-update-status');
$router->map('GET', '/admin/ecommerce/categories[/?]', '\\Ecommerce\\Controllers\\EcommerceAdminCategoriesController#list', 'admin-ecommerce-categories');

// Public routes
$router->map('GET', '/shop[/?]', '\\Ecommerce\\Controllers\\EcommerceController#shop', 'ecommerce-shop');
$router->map('GET', '/shop/product/[i:id]', '\\Ecommerce\\Controllers\\EcommerceController#product', 'ecommerce-product');
$router->map('GET', '/shop/category/[i:id]', '\\Ecommerce\\Controllers\\EcommerceController#category', 'ecommerce-category');
$router->map('GET', '/cart[/?]', '\\Ecommerce\\Controllers\\EcommerceController#cart', 'ecommerce-cart');
$router->map('POST', '/cart/add', '\\Ecommerce\\Controllers\\EcommerceController#addToCart', 'ecommerce-cart-add');
$router->map('POST', '/cart/update', '\\Ecommerce\\Controllers\\EcommerceController#updateCart', 'ecommerce-cart-update');
$router->map('GET', '/cart/remove/[i:id]', '\\Ecommerce\\Controllers\\EcommerceController#removeFromCart', 'ecommerce-cart-remove');
$router->map('GET', '/checkout[/?]', '\\Ecommerce\\Controllers\\EcommerceController#checkout', 'ecommerce-checkout');
$router->map('POST', '/checkout/process', '\\Ecommerce\\Controllers\\EcommerceController#processCheckout', 'ecommerce-checkout-process');
$router->map('GET', '/order/success/[i:id]', '\\Ecommerce\\Controllers\\EcommerceController#orderSuccess', 'ecommerce-order-success');

