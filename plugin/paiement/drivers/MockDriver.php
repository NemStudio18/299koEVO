<?php

/**
 * MockDriver — Simulated payment for testing (no real credentials needed)
 * Immediately redirects to success URL without any external API call.
 * @package Netberry / 299Ko CMS
 * @security REMOVE or DISABLE this driver in production!
 */
defined('ROOT') OR exit('Access denied!');

require_once __DIR__ . '/PaymentDriverInterface.php';

class MockDriver implements PaymentDriverInterface
{
    public function getName(): string { return 'mock'; }

    public function isConfigured(): bool
    {
        // Only enabled if MOCK_PAYMENT env var or config is set
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('paiement');
        return (bool)($plugin ? $plugin->getConfigVal('mock_enabled') : false);
    }

    /**
     * Simulates a payment by redirecting directly to the success URL.
     * Optionally simulates failure based on mock_fail_rate config (0–100).
     */
    public function createSession(float $amount, string $description, string $successUrl, string $cancelUrl, array $metadata = []): string|false
    {
        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('paiement');
        $failRate = (int)($plugin ? $plugin->getConfigVal('mock_fail_rate') : 0);

        // Simulate random payment failures
        if ($failRate > 0 && rand(1, 100) <= $failRate) {
            \Core\Storage\EmergencyLogger::log("MOCK_PAYMENT: Simulated failure for user {$metadata['user_id']} type={$metadata['type']} ref={$metadata['ref_id']}");
            return $cancelUrl . '?mock_reason=payment_failed';
        }

        \Core\Storage\EmergencyLogger::log("MOCK_PAYMENT: Simulated success for user {$metadata['user_id']} type={$metadata['type']} ref={$metadata['ref_id']} amount={$amount}");
        return $successUrl;
    }

    public function handleWebhook(string $payload, array $headers): array|false
    {
        return false; // Mock doesn't use webhooks
    }

    public function getPublicKey(): string { return 'mock_public_key'; }
}
