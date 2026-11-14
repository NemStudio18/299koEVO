<?php

namespace Ecommerce\Controllers;

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Utils\Show;
use Core\Lang;
use Utils\Util;
use Core\Core;

defined('ROOT') or exit('Access denied!');

class EcommerceController extends PublicController
{
    private $productManager;
    private $categoryManager;
    private $orderManager;

    public function __construct()
    {
        parent::__construct();
        $this->productManager = new \Ecommerce\Entities\ProductManager();
        $this->categoryManager = new \Ecommerce\Entities\CategoryManager();
        $this->orderManager = new \Ecommerce\Entities\OrderManager();
    }

    public function shop()
    {
        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('ecommerce', 'shop');
        
        $products = $this->productManager->getAll(true);
        $categories = $this->categoryManager->getAll();
        
        $tpl->set('products', $products);
        $tpl->set('categories', $categories);
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function product()
    {
        $response = new PublicResponse();
        $id = (int)$this->router->getParam('id');
        
        $product = $this->productManager->getById($id);
        if (!$product || !$product->isActive()) {
            return Core::getInstance()->redirect('/shop');
        }
        
        $tpl = $response->createPluginTemplate('ecommerce', 'product');
        $tpl->set('product', $product);
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function category()
    {
        $response = new PublicResponse();
        $id = (int)$this->router->getParam('id');
        
        $category = $this->categoryManager->getById($id);
        if (!$category) {
            return Core::getInstance()->redirect('/shop');
        }
        
        $products = $this->productManager->getByCategory($id, true);
        
        $tpl = $response->createPluginTemplate('ecommerce', 'category');
        $tpl->set('category', $category);
        $tpl->set('products', $products);
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function cart()
    {
        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('ecommerce', 'cart');
        
        $items = \Ecommerce\Entities\Cart::getItemsWithProducts();
        $total = \Ecommerce\Entities\Cart::getTotal();
        
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('ecommerce');
        $shippingCost = (float)$plugin->getConfigVal('shippingCost');
        $freeShippingThreshold = (float)$plugin->getConfigVal('freeShippingThreshold');
        
        $finalShipping = ($total >= $freeShippingThreshold) ? 0 : $shippingCost;
        $grandTotal = $total + $finalShipping;
        
        $tpl->set('items', $items);
        $tpl->set('subtotal', $total);
        $tpl->set('shipping', $finalShipping);
        $tpl->set('total', $grandTotal);
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function addToCart()
    {
        $response = new \Core\Responses\ApiResponse();
        
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);
        
        $product = $this->productManager->getById($productId);
        if (!$product || !$product->isActive() || !$product->isInStock()) {
            $response->status = \Core\Responses\ApiResponse::STATUS_ERROR;
            $response->message = Lang::get('ecommerce.product-unavailable');
            return $response;
        }
        
        if ($quantity > $product->getStock()) {
            $response->status = \Core\Responses\ApiResponse::STATUS_ERROR;
            $response->message = Lang::get('ecommerce.insufficient-stock');
            return $response;
        }
        
        \Ecommerce\Entities\Cart::add($productId, $quantity);
        
        $response->status = \Core\Responses\ApiResponse::STATUS_SUCCESS;
        $response->message = Lang::get('ecommerce.product-added-to-cart');
        $response->data = ['cart_count' => \Ecommerce\Entities\Cart::getItemCount()];
        
        return $response;
    }

    public function updateCart()
    {
        $response = new \Core\Responses\ApiResponse();
        
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        
        if ($quantity <= 0) {
            \Ecommerce\Entities\Cart::remove($productId);
        } else {
            \Ecommerce\Entities\Cart::update($productId, $quantity);
        }
        
        $response->status = \Core\Responses\ApiResponse::STATUS_SUCCESS;
        $response->message = Lang::get('ecommerce.cart-updated');
        
        return $response;
    }

    public function removeFromCart()
    {
        $id = (int)$this->router->getParam('id');
        \Ecommerce\Entities\Cart::remove($id);
        return Core::getInstance()->redirect('/cart');
    }

    public function checkout()
    {
        $response = new PublicResponse();
        
        $items = \Ecommerce\Entities\Cart::getItemsWithProducts();
        if (empty($items)) {
            return Core::getInstance()->redirect('/cart');
        }
        
        $tpl = $response->createPluginTemplate('ecommerce', 'checkout');
        
        $total = \Ecommerce\Entities\Cart::getTotal();
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('ecommerce');
        $shippingCost = (float)$plugin->getConfigVal('shippingCost');
        $freeShippingThreshold = (float)$plugin->getConfigVal('freeShippingThreshold');
        
        $finalShipping = ($total >= $freeShippingThreshold) ? 0 : $shippingCost;
        $grandTotal = $total + $finalShipping;
        
        $tpl->set('items', $items);
        $tpl->set('subtotal', $total);
        $tpl->set('shipping', $finalShipping);
        $tpl->set('total', $grandTotal);
        $tpl->set('token', Show::tokenField());
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function processCheckout()
    {
        $response = new \Core\Responses\ApiResponse();
        
        if (!Show::tokenCheck($_POST['token'] ?? '')) {
            $response->status = \Core\Responses\ApiResponse::STATUS_BAD_REQUEST;
            $response->message = Lang::get('core.bad-token');
            return $response;
        }
        
        $items = \Ecommerce\Entities\Cart::getItemsWithProducts();
        if (empty($items)) {
            $response->status = \Core\Responses\ApiResponse::STATUS_ERROR;
            $response->message = Lang::get('ecommerce.cart-empty');
            return $response;
        }
        
        // Validate stock
        foreach ($items as $item) {
            $product = $item['product'];
            if (!$product->isInStock() || $item['quantity'] > $product->getStock()) {
                $response->status = \Core\Responses\ApiResponse::STATUS_ERROR;
                $response->message = Lang::get('ecommerce.insufficient-stock');
                return $response;
            }
        }
        
        $total = \Ecommerce\Entities\Cart::getTotal();
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('ecommerce');
        $shippingCost = (float)$plugin->getConfigVal('shippingCost');
        $freeShippingThreshold = (float)$plugin->getConfigVal('freeShippingThreshold');
        $finalShipping = ($total >= $freeShippingThreshold) ? 0 : $shippingCost;
        $grandTotal = $total + $finalShipping;
        
        $orderData = [
            'customer_name' => $_POST['customer_name'] ?? '',
            'customer_email' => $_POST['customer_email'] ?? '',
            'customer_address' => $_POST['customer_address'] ?? '',
            'customer_phone' => $_POST['customer_phone'] ?? '',
            'total' => $grandTotal,
            'status' => 'pending',
            'payment_method' => $_POST['payment_method'] ?? 'bank_transfer',
        ];
        
        $orderItems = [];
        foreach ($items as $item) {
            $orderItems[] = [
                'product_id' => $item['product']->getId(),
                'product_name' => $item['product']->getName(),
                'quantity' => $item['quantity'],
                'price' => $item['product']->getPrice(),
            ];
        }
        
        $order = $this->orderManager->create($orderData, $orderItems);
        
        if ($order) {
            // Decrease stock
            foreach ($items as $item) {
                $this->productManager->decreaseStock($item['product']->getId(), $item['quantity']);
            }
            
            \Ecommerce\Entities\Cart::clear();

            // Envoi des emails de confirmation et notification
            $this->sendOrderEmails($order, $orderItems, $orderData, $plugin);
            
            $response->status = \Core\Responses\ApiResponse::STATUS_SUCCESS;
            $response->message = Lang::get('ecommerce.order-created');
            $response->redirect = Util::urlBuild('/order/success/' . $order->getId());
        } else {
            $response->status = \Core\Responses\ApiResponse::STATUS_ERROR;
            $response->message = Lang::get('ecommerce.order-create-error');
        }
        
        return $response;
    }

    public function orderSuccess($id)
    {
        $order = $this->orderManager->findById((int)$id);
        if (!$order) {
            \Utils\Show::msg('Commande introuvable.', 'error');
            return $this->core->redirect(Util::urlBuild('/shop'));
        }

        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('ecommerce');
        $currency = $plugin ? $plugin->getConfigVal('currency') : '€';

        $items = $this->orderManager->getOrderItems($order->getId());

        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('ecommerce', 'order-success');
        $tpl->set('order', $order);
        $tpl->set('items', $items);
        $tpl->set('currency', $currency ?: '€');

        $bankDetails = [
            'owner' => $this->core->getConfigVal('siteName') ?: 'Boutique en ligne',
            'iban' => $plugin ? ($plugin->getConfigVal('bank_iban') ?: 'FR76 1234 5678 9012 3456 7890 123') : '',
            'bic' => $plugin ? ($plugin->getConfigVal('bank_bic') ?: 'BNPAFRPPXXX') : '',
        ];
        $tpl->set('bankDetails', $bankDetails);

        $response->addTemplate($tpl);
        return $response;
    }

    private function sendOrderEmails($order, array $orderItems, array $orderData, $plugin): void
    {
        $siteName = $this->core->getConfigVal('siteName') ?: '299koEVO';
        $siteEmail = $this->core->getConfigVal('siteEmail') ?: 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $customerEmail = trim($orderData['customer_email'] ?? '');
        $currency = $plugin ? $plugin->getConfigVal('currency') : '€';

        $itemsText = "";
        foreach ($orderItems as $item) {
            $lineTotal = (float)$item['price'] * (int)$item['quantity'];
            $itemsText .= "- " . $item['product_name'] . " x" . $item['quantity'] . " : " . number_format($lineTotal, 2) . " " . $currency . "\n";
        }

        // 1. Email au client
        if (!empty($customerEmail) && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $clientSubject = "[" . $siteName . "] Confirmation de votre commande #" . $order->getId();
            $clientMsg = "Bonjour " . ($orderData['customer_name'] ?: 'Cher client') . ",\n\n";
            $clientMsg .= "Nous vous remercions pour votre commande #" . $order->getId() . " passée sur notre boutique " . $siteName . ".\n\n";
            $clientMsg .= "Détail de vos articles :\n" . $itemsText . "\n";
            $clientMsg .= "Total TTC : " . number_format((float)$orderData['total'], 2) . " " . $currency . "\n";
            $clientMsg .= "Mode de paiement : " . $orderData['payment_method'] . "\n\n";
            $clientMsg .= "Adresse de livraison :\n" . $orderData['customer_address'] . "\n\n";
            if ($orderData['payment_method'] === 'bank_transfer') {
                $iban = $plugin ? ($plugin->getConfigVal('bank_iban') ?: 'FR76 1234 5678 9012 3456 7890 123') : '';
                $bic = $plugin ? ($plugin->getConfigVal('bank_bic') ?: 'BNPAFRPPXXX') : '';
                $clientMsg .= "Pour finaliser votre commande, merci d'effectuer votre virement avec la référence CMD-" . $order->getId() . " :\n";
                $clientMsg .= "IBAN : " . $iban . "\nBIC : " . $bic . "\n\n";
            }
            $clientMsg .= "À très bientôt sur " . $siteName . " !\n";

            @Util::sendEmail($siteEmail, $siteEmail, $customerEmail, $clientSubject, $clientMsg);
        }

        // 2. Notification à l'administrateur
        if (!empty($siteEmail) && filter_var($siteEmail, FILTER_VALIDATE_EMAIL)) {
            $adminSubject = "[Nouvelle Commande #" . $order->getId() . "] " . ($orderData['customer_name'] ?: 'Client');
            $adminMsg = "Une nouvelle commande #" . $order->getId() . " vient d'être passée sur votre boutique.\n\n";
            $adminMsg .= "Client : " . $orderData['customer_name'] . " (" . $orderData['customer_email'] . ")\n";
            $adminMsg .= "Téléphone : " . ($orderData['customer_phone'] ?: 'Non renseigné') . "\n";
            $adminMsg .= "Total : " . number_format((float)$orderData['total'], 2) . " " . $currency . "\n";
            $adminMsg .= "Mode de règlement : " . $orderData['payment_method'] . "\n\n";
            $adminMsg .= "Articles commandés :\n" . $itemsText . "\n";
            $adminMsg .= "Consultez la commande dans l'administration : " . Util::urlBuild('/admin/ecommerce/orders/view/' . $order->getId()) . "\n";

            @Util::sendEmail($siteEmail, $customerEmail ?: $siteEmail, $siteEmail, $adminSubject, $adminMsg);
        }
    }
}

