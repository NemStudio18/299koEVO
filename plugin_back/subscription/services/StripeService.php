<?php

namespace Subscription\Services;

use Core\Logger;

defined('ROOT') or exit('Access denied!');

/**
 * Service pour gérer les paiements Stripe via cURL
 */
class StripeService
{
    private string $apiKey;
    private $logger;
    private string $apiBase = 'https://api.stripe.com/v1';

    public function __construct(string $apiKey, ?object $core = null)
    {
        $this->apiKey = $apiKey;
        // Handle core/logger injection
        if ($core && method_exists($core, 'getLogger')) {
            $this->logger = $core->getLogger();
        } else {
            // Fallback mock logger if not provided
            $this->logger = new class {
                public function error($msg)
                {
                    error_log('Stripe Error: ' . $msg);
                }
                public function info($msg)
                {
                    error_log('Stripe Info: ' . $msg);
                }
                public function warning($msg)
                {
                    error_log('Stripe Warning: ' . $msg);
                }
            };
        }
    }

    private function request(string $method, string $endpoint, array $data = []): ?array
    {
        $url = $this->apiBase . $endpoint;
        $ch = curl_init();

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/x-www-form-urlencoded',
        ];

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        } elseif ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Disable SSL verify for local dev/testing if needed, but safer to keep it. 
        // If user has issues, they might need to config cacert.pem. Assuming server is fine.

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $this->logger->error('Stripe cURL Error: ' . curl_error($ch));
            curl_close($ch);
            return null;
        }

        curl_close($ch);

        $json = json_decode($response, true);

        if ($httpCode >= 400) {
            $this->logger->error('Stripe API Error (' . $httpCode . '): ' . ($json['error']['message'] ?? 'Unknown error'));
            return null;
        }

        return $json;
    }

    public function createCustomer(string $email, string $name): ?array
    {
        return $this->request('POST', '/customers', [
            'email' => $email,
            'name' => $name,
        ]);
    }

    public function getCustomer(string $customerId): ?array
    {
        return $this->request('GET', '/customers/' . $customerId);
    }

    /**
     * Crée une session Checkout pour un abonnement
     * @param string $customerId ID du client Stripe
     * @param float $amount Montant en euros
     * @param string $interval 'month' ou 'year'
     * @param string $name Nom du produit/plan
     * @param string $successUrl URL de callback succès
     * @param string $cancelUrl URL de callback annulation
     * @param array $metadata Métadonnées additionnelles
     */
    public function createCheckoutSession(
        string $customerId,
        float $amount,
        string $interval,
        string $name,
        string $successUrl,
        string $cancelUrl,
        array $metadata = []
    ): ?array {
        $data = [
            'customer' => $customerId,
            'payment_method_types' => ['card'], // Add 'sepa_debit' if needed
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $name,
                        ],
                        'unit_amount' => (int) ($amount * 100), // Stripe expects cents
                        'recurring' => [
                            'interval' => $interval === '1 year' ? 'year' : 'month',
                        ],
                    ],
                    'quantity' => 1,
                ]
            ],
            'mode' => 'subscription',
            'success_url' => $successUrl . '&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
            'subscription_data' => [
                'metadata' => $metadata,
            ],
            'metadata' => $metadata,
        ];

        return $this->request('POST', '/checkout/sessions', $data);
    }

    public function cancelSubscription(string $subscriptionId): bool
    {
        $result = $this->request('DELETE', '/subscriptions/' . $subscriptionId);
        return $result !== null;
    }

    public function getSubscription(string $subscriptionId): ?array
    {
        return $this->request('GET', '/subscriptions/' . $subscriptionId);
    }

    public function getSession(string $sessionId): ?array
    {
        return $this->request('GET', '/checkout/sessions/' . $sessionId);
    }

    public function getInvoice(string $invoiceId): ?array
    {
        return $this->request('GET', '/invoices/' . $invoiceId);
    }

    // Unused in Stripe Checkout flow usually, but needed if we want to retrieve payment intent
    public function getPaymentIntent(string $paymentIntentId): ?array
    {
        return $this->request('GET', '/payment_intents/' . $paymentIntentId);
    }

    /**
     * Vérifie la signature du webhook Stripe
     * @param string $payload Corps de la requête
     * @param string $sigHeader En-tête Stripe-Signature
     * @param string $endpointSecret Secret de signature du webhook (whsec_...)
     * @return bool True si la signature est valide
     */
    public function verifyWebhookSignature(string $payload, string $sigHeader, string $endpointSecret): bool
    {
        if (empty($sigHeader) || empty($endpointSecret)) {
            return false;
        }

        // Parse the header
        $signedPayload = $payload;
        $signature = '';
        $timestamp = -1;

        $items = explode(',', $sigHeader);
        foreach ($items as $item) {
            $itemParts = explode('=', $item, 2);
            if (count($itemParts) === 2) {
                if ($itemParts[0] === 't') {
                    $timestamp = $itemParts[1];
                } elseif ($itemParts[0] === 'v1') {
                    $signature = $itemParts[1];
                }
            }
        }

        if ($timestamp === -1 || empty($signature)) {
            return false;
        }

        // Check for replay attacks (tolerance of 5 minutes)
        if (abs(time() - $timestamp) > 300) {
            $this->logger->error('Stripe Webhook Error: Timestamp too old');
            return false;
        }

        // Compute expected signature
        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $endpointSecret);

        if (!hash_equals($expectedSignature, $signature)) {
            $this->logger->error('Stripe Webhook Error: Signature mismatch');
            return false;
        }

        return true;
    }
}
