<?php

namespace Dons\Controllers;

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Utils\Show;
use Core\Lang;
use Core\Router\Router;
use Core\Core;
use Utils\Util;
use Core\Plugin\PluginsManager;
use Dons\Entities\Don;
use Dons\Entities\DonManager;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('No direct script access allowed');

class DonController extends PublicController
{
    public function home()
    {
        $donManager = new DonManager();
        $totalAmount = $donManager->getTotalAmount();
        $targetAmount = floatval($this->runPlugin->getConfigVal('targetAmount'));
        $progress = $targetAmount > 0 ? ($totalAmount / $targetAmount) * 100 : 0;
        if ($progress > 100) $progress = 100;

        $response = new PublicResponse();
        $response->setTitle('<i class="fa-solid fa-hand-holding-heart"></i> ' . ($this->runPlugin->getConfigVal('pageTitle') ?: Lang::get('dons.name')));

        $tpl = $response->createPluginTemplate('dons', 'donate');

        $paypalClientId = $this->runPlugin->getConfigVal('paypalClientId');
        $stripePublishableKey = $this->runPlugin->getConfigVal('stripePublishableKey');
        
        $tpl->set('description', $this->runPlugin->getConfigVal('description'));
        $tpl->set('totalAmount', $totalAmount);
        $tpl->set('totalAmountFormatted', number_format($totalAmount, 2, ',', ' '));
        $tpl->set('targetAmount', $targetAmount);
        $tpl->set('targetAmountFormatted', number_format($targetAmount, 2, ',', ' '));
        $tpl->set('progress', $progress);
        $tpl->set('progressFormatted', number_format($progress, 1, ',', ' '));
        $tpl->set('hasPayPal', !empty($paypalClientId));
        $tpl->set('hasStripe', !empty($stripePublishableKey));
        $tpl->set('paypalClientId', $paypalClientId);
        $tpl->set('stripePublishableKey', $stripePublishableKey);
        $tpl->set('paypalMode', $this->runPlugin->getConfigVal('paypalMode'));
        $tpl->set('paypalCreateUrl', $this->router->generate('dons-paypal-create'));
        $tpl->set('paypalCaptureUrl', $this->router->generate('dons-paypal-capture'));
        $tpl->set('stripeCreateUrl', $this->router->generate('dons-stripe-create'));
        $tpl->set('stripeSuccessUrl', $this->router->generate('dons-stripe-success'));
        
        // Public Donors List
        $showDonorsList = $this->runPlugin->getConfigVal('showDonorsList');
        $continueAfterTarget = $this->runPlugin->getConfigVal('continueAfterTarget');
        $goalReached = ($targetAmount > 0 && $totalAmount >= $targetAmount);
        $showDonationGraph = $this->runPlugin->getConfigVal('showDonationGraph');

        $tpl->set('showDonorsList', $showDonorsList);
        $tpl->set('continueAfterTarget', $continueAfterTarget);
        $tpl->set('goalReached', $goalReached);
        $tpl->set('showDonationGraph', $showDonationGraph);
        if ($showDonorsList) {
            $dons = $donManager->getDons();
            $recentDonors = [];
            foreach ($dons as $don) {
                if ($don->getStatus() === 'completed' && !$don->isAnonymous()) {
                    $recentDonors[] = [
                        'firstName' => $don->getFirstName(),
                        'lastName' => $don->getLastName(),
                        'amount' => $don->getAmount(),
                        'amountFormatted' => number_format($don->getAmount(), 2, ',', ' '),
                        'date' => Util::FormatDate($don->getDate(), 'en', 'fr')
                    ];
                }
            }
            // Sort by date desc
            usort($recentDonors, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
            $tpl->set('recentDonors', array_slice($recentDonors, 0, 10));
        }
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function createPayPalPayment()
    {
        $amount = floatval($_POST['amount'] ?? 0);
        $firstName = filter_var($_POST['firstName'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $lastName = filter_var($_POST['lastName'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $message = filter_var($_POST['message'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $anonymous = isset($_POST['anonymous']) ? '1' : '0';

        if ($amount < 1) {
            header('Content-Type: application/json');
            echo json_encode(['error' => Lang::get('dons.invalid-amount')]);
            die();
        }

        // Check if target reached and continue forbidden
        $donManager = new DonManager();
        $totalAmount = $donManager->getTotalAmount();
        $targetAmount = floatval($this->runPlugin->getConfigVal('targetAmount'));
        $continueAfterTarget = $this->runPlugin->getConfigVal('continueAfterTarget');

        if ($targetAmount > 0 && $totalAmount >= $targetAmount && !$continueAfterTarget) {
            header('Content-Type: application/json');
            echo json_encode(['error' => Lang::get('dons.goal-reached')]);
            die();
        }

        $paypalMode = $this->runPlugin->getConfigVal('paypalMode');
        $baseUrl = $paypalMode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
        
        $clientId = $this->runPlugin->getConfigVal('paypalClientId');
        $secret = $this->runPlugin->getConfigVal('paypalSecret');

        // Obtenir le token d'accès
        $ch = curl_init($baseUrl . '/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $clientId . ':' . $secret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Accept-Language: en_US']);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($httpCode !== 200) {
            header('Content-Type: application/json');
            echo json_encode(['error' => Lang::get('dons.paypal-error')]);
            die();
        }
        
        $tokenData = json_decode($response, true);
        $accessToken = $tokenData['access_token'] ?? '';

        // Créer le paiement
        $router = Router::getInstance();
        $returnUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $router->generate('dons-paypal-capture');
        $cancelUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $router->generate('dons-home');

        $paymentData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => 'EUR',
                    'value' => number_format($amount, 2, '.', '')
                ],
                'description' => Lang::get('dons.donation-description')
            ]],
            'application_context' => [
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl
            ]
        ];

        $ch = curl_init($baseUrl . '/v2/checkout/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 201) {
            header('Content-Type: application/json');
            echo json_encode(['error' => Lang::get('dons.paypal-error')]);
            die();
        }

        $payment = json_decode($response, true);
        $orderId = $payment['id'] ?? '';

        // Sauvegarder le don en attente
        $donManager = new DonManager();
        $don = new Don();
        $don->setAmount($amount);
        $don->setFirstName($firstName);
        $don->setLastName($lastName);
        $don->setEmail($email);
        $don->setMessage($message);
        $don->setGateway('paypal');
        $don->setTransactionId($orderId);
        $don->setStatus('pending');
        $don->setAnonymous($anonymous);
        $donManager->saveDon($don);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'approvalUrl' => $payment['links'][1]['href'] ?? ''
        ]);
        die();
    }

    public function capturePayPalPayment()
    {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            Show::msg(Lang::get('dons.paypal-error'), 'error');
            Core::getInstance()->redirect($this->router->generate('dons-home'));
            return;
        }

        $paypalMode = $this->runPlugin->getConfigVal('paypalMode');
        $baseUrl = $paypalMode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
        
        $clientId = $this->runPlugin->getConfigVal('paypalClientId');
        $secret = $this->runPlugin->getConfigVal('paypalSecret');

        // Obtenir le token d'accès
        $ch = curl_init($baseUrl . '/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $clientId . ':' . $secret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Accept-Language: en_US']);
        $response = curl_exec($ch);
        $tokenData = json_decode($response, true);
        $accessToken = $tokenData['access_token'] ?? '';
        curl_close($ch);

        // Capturer le paiement
        $ch = curl_init($baseUrl . '/v2/checkout/orders/' . $token . '/capture');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $donManager = new DonManager();
        $don = $donManager->findByTransactionId($token);
        
        if ($httpCode === 201 && $don) {
            $don->setStatus('completed');
            $donManager->saveDon($don);
            
            // Mettre à jour le montant total dans la config
            $this->runPlugin->setConfigVal('currentAmount', $donManager->getTotalAmount());
            $pluginsManager = pluginsManager::getInstance();
            $pluginsManager->savePluginConfig($this->runPlugin);
            
            Show::msg(Lang::get('dons.donation-success'), 'success');
        } else {
            if ($don) {
                $don->setStatus('failed');
                $donManager->saveDon($don);
            }
            Show::msg(Lang::get('dons.donation-error'), 'error');
        }

        Core::getInstance()->redirect($this->router->generate('dons-home'));
    }

    public function createStripePayment()
    {
        $amount = floatval($_POST['amount'] ?? 0) * 100; // Stripe utilise les centimes
        $firstName = filter_var($_POST['firstName'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $lastName = filter_var($_POST['lastName'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $message = filter_var($_POST['message'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $anonymous = isset($_POST['anonymous']) ? '1' : '0';

        if ($amount < 100) {
            header('Content-Type: application/json');
            echo json_encode(['error' => Lang::get('dons.invalid-amount')]);
            die();
        }

        // Check if target reached and continue forbidden
        $donManager = new DonManager();
        $totalAmount = $donManager->getTotalAmount();
        $targetAmount = floatval($this->runPlugin->getConfigVal('targetAmount'));
        $continueAfterTarget = $this->runPlugin->getConfigVal('continueAfterTarget');

        if ($targetAmount > 0 && $totalAmount >= $targetAmount && !$continueAfterTarget) {
            header('Content-Type: application/json');
            echo json_encode(['error' => Lang::get('dons.goal-reached')]);
            die();
        }

        $stripeSecretKey = $this->runPlugin->getConfigVal('stripeSecretKey');
        
        $router = Router::getInstance();
        $successUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $router->generate('dons-stripe-success');
        $cancelUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $router->generate('dons-home');

        // Créer une session Checkout Stripe
        $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERPWD, $stripeSecretKey . ':');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'payment_method_types[]' => 'card',
            'line_items[0][price_data][currency]' => 'eur',
            'line_items[0][price_data][product_data][name]' => Lang::get('dons.donation-description'),
            'line_items[0][price_data][unit_amount]' => intval($amount),
            'line_items[0][quantity]' => 1,
            'mode' => 'payment',
            'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
            'customer_email' => $email
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($httpCode !== 200) {
            header('Content-Type: application/json');
            echo json_encode(['error' => Lang::get('dons.stripe-error')]);
            die();
        }

        $session = json_decode($response, true);
        $sessionId = $session['id'] ?? '';

        // Sauvegarder le don en attente
        $donManager = new DonManager();
        $don = new Don();
        $don->setAmount($amount / 100);
        $don->setFirstName($firstName);
        $don->setLastName($lastName);
        $don->setEmail($email);
        $don->setMessage($message);
        $don->setGateway('stripe');
        $don->setTransactionId($sessionId);
        $don->setStatus('pending');
        $don->setAnonymous($anonymous);
        $donManager->saveDon($don);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'sessionId' => $sessionId
        ]);
        die();
    }

    public function stripeSuccess()
    {
        $sessionId = $_GET['session_id'] ?? '';
        
        if (empty($sessionId)) {
            Show::msg(Lang::get('dons.stripe-error'), 'error');
            Core::getInstance()->redirect($this->router->generate('dons-home'));
            return;
        }

        $stripeSecretKey = $this->runPlugin->getConfigVal('stripeSecretKey');
        
        // Récupérer la session Stripe
        $ch = curl_init('https://api.stripe.com/v1/checkout/sessions/' . $sessionId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $stripeSecretKey . ':');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $donManager = new DonManager();
        $don = $donManager->findByTransactionId($sessionId);
        
        if ($httpCode === 200 && $don) {
            $session = json_decode($response, true);
            if ($session['payment_status'] === 'paid') {
                $don->setStatus('completed');
                $donManager->saveDon($don);
                
                // Mettre à jour le montant total dans la config
                $this->runPlugin->setConfigVal('currentAmount', $donManager->getTotalAmount());
                $pluginsManager = PluginsManager::getInstance();
                $pluginsManager->savePluginConfig($this->runPlugin);
                
                Show::msg(Lang::get('dons.donation-success'), 'success');
            } else {
                $don->setStatus('failed');
                $donManager->saveDon($don);
                Show::msg(Lang::get('dons.donation-error'), 'error');
            }
        } else {
            if ($don) {
                $don->setStatus('failed');
                $donManager->saveDon($don);
            }
            Show::msg(Lang::get('dons.donation-error'), 'error');
        }

        Core::getInstance()->redirect($this->router->generate('dons-home'));
    }

    public function stripeCancel()
    {
        Show::msg(Lang::get('dons.donation-cancelled'), 'error');
        Core::getInstance()->redirect($this->router->generate('dons-home'));
    }
}

