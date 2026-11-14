<?php

/**
 * PaypalDriver — PayPal payment integration
 * @package Netberry / 299Ko CMS
 * Uses PayPal Orders API v2 directly via cURL
 */
defined('ROOT') OR exit('Access denied!');

require_once __DIR__ . '/PaymentDriverInterface.php';

use Core\Core;

class PaypalDriver implements PaymentDriverInterface
{
    private string $clientId;
    private string $clientSecret;
    private string $mode; // 'sandbox' or 'live'

    private string $baseUrl;

    public function __construct()
    {
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('paiement');
        $this->clientId     = (string)($plugin ? $plugin->getConfigVal('paypal_client_id')     : '');
        $this->clientSecret = (string)($plugin ? $plugin->getConfigVal('paypal_client_secret') : '');
        $this->mode         = (string)($plugin ? $plugin->getConfigVal('paypal_mode')          : 'sandbox');
        $this->baseUrl      = ($this->mode === 'live')
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    public function getName(): string { return 'paypal'; }

    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    /**
     * Get OAuth2 access token from PayPal
     */
    private function getAccessToken(): string|false
    {
        $ch = curl_init($this->baseUrl . '/v1/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
            CURLOPT_USERPWD        => $this->clientId . ':' . $this->clientSecret,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        if (!$result) return false;
        $data = json_decode($result, true);
        return $data['access_token'] ?? false;
    }

    /**
     * Create a PayPal Order and return the approval URL
     */
    public function createSession(float $amount, string $description, string $successUrl, string $cancelUrl, array $metadata = []): string|false
    {
        if (!$this->isConfigured()) return false;

        $token = $this->getAccessToken();
        if (!$token) return false;

        $orderData = json_encode([
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => 'EUR',
                    'value'         => number_format($amount, 2, '.', ''),
                ],
                'description' => $description,
                'custom_id'   => json_encode($metadata),
            ]],
            'application_context' => [
                'return_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'brand_name' => 'NetBerry',
                'locale'     => 'fr-FR',
                'user_action' => 'PAY_NOW',
            ],
        ]);

        $ch = curl_init($this->baseUrl . '/v2/checkout/orders');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $orderData,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Prefer: return=representation',
            ],
        ]);
        $result = curl_exec($ch);
        curl_close($ch);

        if (!$result) return false;
        $data = json_decode($result, true);

        // Find the approval URL
        foreach ($data['links'] ?? [] as $link) {
            if ($link['rel'] === 'approve') return $link['href'];
        }
        return false;
    }

    /**
     * Handle PayPal IPN/webhook
     */
    public function handleWebhook(string $payload, array $headers): array|false
    {
        $data = json_decode($payload, true);
        if (!$data) return false;
        return $data;
    }
}
