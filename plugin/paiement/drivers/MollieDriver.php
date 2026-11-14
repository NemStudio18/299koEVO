<?php

/**
 * MollieDriver — Mollie payment integration
 * @package Netberry / 299Ko CMS
 *
 * Uses Mollie REST API directly (no SDK needed)
 * Doc : https://docs.mollie.com/reference/create-payment
 */
defined('ROOT') OR exit('Access denied!');

require_once __DIR__ . '/PaymentDriverInterface.php';

class MollieDriver implements PaymentDriverInterface
{
    private string $apiKey;
    private const API_BASE = 'https://api.mollie.com/v2';

    public function __construct()
    {
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('paiement');
        $this->apiKey = (string)($plugin ? $plugin->getConfigVal('mollie_api_key') : '');
    }

    public function getName(): string { return 'mollie'; }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Create a Mollie payment and return the checkout URL
     */
    public function createSession(float $amount, string $description, string $successUrl, string $cancelUrl, array $metadata = []): string|false
    {
        if (!$this->isConfigured()) return false;

        $payload = json_encode([
            'amount'      => [
                'currency' => 'EUR',
                'value'    => number_format($amount, 2, '.', ''),
            ],
            'description' => $description,
            'redirectUrl' => $successUrl,
            'cancelUrl'   => $cancelUrl,
            'metadata'    => $metadata,
            'method'      => ['creditcard', 'bancontact', 'ideal', 'paypal'],
        ]);

        $ch = curl_init(self::API_BASE . '/payments');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
        ]);

        $result = curl_exec($ch);
        curl_close($ch);

        if (!$result) return false;
        $data = json_decode($result, true);

        // Return checkout URL
        return $data['_links']['checkout']['href'] ?? false;
    }

    /**
     * Handle Mollie webhook
     * Mollie sends a POST with 'id' param, we fetch the payment to verify status
     */
    public function handleWebhook(string $payload, array $headers): array|false
    {
        parse_str($payload, $postData);
        $paymentId = $postData['id'] ?? '';

        if (empty($paymentId)) return false;

        // Fetch payment status from Mollie
        $ch = curl_init(self::API_BASE . '/payments/' . $paymentId);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
            ],
        ]);

        $result = curl_exec($ch);
        curl_close($ch);

        if (!$result) return false;
        return json_decode($result, true) ?: false;
    }
}
