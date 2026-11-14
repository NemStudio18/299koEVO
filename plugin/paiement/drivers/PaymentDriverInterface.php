<?php

/**
 * PaymentDriverInterface — Common interface for all payment providers
 * @package Netberry / 299Ko CMS
 */
defined('ROOT') OR exit('Access denied!');

interface PaymentDriverInterface
{
    /**
     * Initiate a payment session
     * @param float  $amount      Amount in euros
     * @param string $description Human-readable description
     * @param string $successUrl  URL after successful payment
     * @param string $cancelUrl   URL after cancelled payment
     * @param array  $metadata    Extra data (type, ref_id, user_id...)
     * @return string|false URL to redirect the user to, or false on failure
     */
    public function createSession(float $amount, string $description, string $successUrl, string $cancelUrl, array $metadata = []): string|false;

    /**
     * Handle webhook payload (raw body + headers)
     * @param string $payload  Raw request body
     * @param array  $headers  Request headers
     * @return array|false Parsed event data, or false on failure
     */
    public function handleWebhook(string $payload, array $headers): array|false;

    /**
     * Return driver name slug
     */
    public function getName(): string;

    /**
     * Is this driver configured (API keys set)?
     */
    public function isConfigured(): bool;
}
