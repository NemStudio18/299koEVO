<?php

/**
 * StripeDriver — Stripe payment integration
 * @package Netberry / 299Ko CMS
 *
 * NOTE: Requires stripe/stripe-php (composer require stripe/stripe-php)
 * Until Composer is configured, uses direct Stripe API calls via cURL
 */
defined('ROOT') OR exit('Access denied!');

require_once __DIR__ . '/PaymentDriverInterface.php';

use Core\Core;

class StripeDriver implements PaymentDriverInterface
{
    private string $publicKey;
    private string $secretKey;
    private string $webhookSecret;

    public function __construct()
    {
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('paiement');
        $this->publicKey     = (string)($plugin ? $plugin->getConfigVal('stripe_public_key')     : '');
        $this->secretKey     = (string)($plugin ? $plugin->getConfigVal('stripe_secret_key')     : '');
        $this->webhookSecret = (string)($plugin ? $plugin->getConfigVal('stripe_webhook_secret') : '');
    }

    public function getName(): string { return 'stripe'; }

    public function isConfigured(): bool
    {
        return !empty($this->secretKey) && !empty($this->publicKey);
    }

    /**
     * Create a Stripe Checkout Session via direct cURL API call
     */
    public function createSession(float $amount, string $description, string $successUrl, string $cancelUrl, array $metadata = []): string|false
    {
        if (!$this->isConfigured()) return false;

        $amountCents = (int)round($amount * 100);

        $postData = http_build_query([
            'payment_method_types[]'                    => 'card',
            'line_items[0][price_data][currency]'       => 'eur',
            'line_items[0][price_data][unit_amount]'    => $amountCents,
            'line_items[0][price_data][product_data][name]' => $description,
            'line_items[0][quantity]'                   => 1,
            'mode'                                      => 'payment',
            'success_url'                               => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'                                => $cancelUrl,
            'metadata[type]'                            => $metadata['type'] ?? '',
            'metadata[ref_id]'                          => $metadata['ref_id'] ?? '',
            'metadata[user_id]'                         => $metadata['user_id'] ?? '',
        ]);

        $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postData,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->secretKey,
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        $result = curl_exec($ch);
        curl_close($ch);

        if (!$result) return false;
        $data = json_decode($result, true);
        return $data['url'] ?? false;
    }

    /**
     * Handle Stripe webhook
     */
    public function handleWebhook(string $payload, array $headers): array|false
    {
        $sigHeader = $headers['Stripe-Signature'] ?? ($headers['stripe-signature'] ?? '');

        if (empty($this->webhookSecret) || empty($sigHeader)) {
            // No signature verification (dev mode)
            $event = json_decode($payload, true);
            return $event ?: false;
        }

        // Signature verification
        $parts     = explode(',', $sigHeader);
        $timestamp = null;
        $signature = null;

        foreach ($parts as $part) {
            [$key, $val] = explode('=', $part, 2);
            if ($key === 't') $timestamp = $val;
            if ($key === 'v1') $signature = $val;
        }

        if (!$timestamp || !$signature) return false;

        $signed = $timestamp . '.' . $payload;
        $expected = hash_hmac('sha256', $signed, $this->webhookSecret);

        if (!hash_equals($expected, $signature)) return false;

        return json_decode($payload, true) ?: false;
    }

    public function getPublicKey(): string { return $this->publicKey; }
}
