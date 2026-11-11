<?php

namespace Subscription\Services;

use Subscription\Entities\Subscription;
use Subscription\Entities\Payment;
use Subscription\Entities\Addon;
use Subscription\Entities\UserAddon;
use Subscription\Entities\PaymentMethod;
use Core\Core;

defined('ROOT') or exit('Access denied!');

class SubscriptionService
{
    private ?Core $core;
    private array $config;

    public function __construct(?Core $core = null)
    {
        $this->core = $core ?? (class_exists('\Core\Core') ? Core::getInstance() : null);
        $this->loadConfig();
    }

    /**
     * Charge la configuration du plugin
     */
    private function loadConfig(): void
    {
        $this->config = [];
        
        $pricesFile = DATA_PLUGIN . 'subscription' . DS . 'prices.json';
        if (file_exists($pricesFile)) {
            $prices = json_decode(file_get_contents($pricesFile), true) ?: [];
            $this->config['monthly_price'] = (float)($prices['monthly_price'] ?? 9.99);
            $this->config['annual_price'] = (float)($prices['annual_price'] ?? ($this->config['monthly_price'] * 12));
            $this->config['annual_discount'] = (float)($prices['annual_discount'] ?? 0);
            $this->config['monthly_title'] = $prices['monthly_title'] ?? 'Abonnement mensuel';
            $this->config['monthly_description'] = $prices['monthly_description'] ?? '';
            $this->config['annual_title'] = $prices['annual_title'] ?? 'Abonnement annuel';
            $this->config['annual_description'] = $prices['annual_description'] ?? '';
            $this->config['enabled'] = isset($prices['enabled']) ? (bool)$prices['enabled'] : true;
            $this->config['disabled_message'] = $prices['disabled_message'] ?? 'Les abonnements sont temporairement indisponibles.';
            $this->config['trial_enabled'] = isset($prices['trial_enabled']) ? (bool)$prices['trial_enabled'] : false;
            $this->config['trial_duration'] = isset($prices['trial_duration']) ? (int)$prices['trial_duration'] : 7;
            $this->config['trial_title'] = $prices['trial_title'] ?? 'Essai gratuit';
            $this->config['trial_description'] = $prices['trial_description'] ?? 'Profitez de toutes les fonctionnalités gratuitement.';
        } else {
            $this->config = [
                'monthly_price' => 9.99,
                'annual_price' => 99.99,
                'annual_discount' => 0,
                'monthly_title' => 'Abonnement mensuel',
                'monthly_description' => '',
                'annual_title' => 'Abonnement annuel',
                'annual_description' => '',
                'enabled' => true,
                'disabled_message' => 'Les abonnements sont temporairement indisponibles.',
                'trial_enabled' => false,
                'trial_duration' => 7,
                'trial_title' => 'Essai gratuit',
                'trial_description' => 'Profitez de toutes les fonctionnalités gratuitement.'
            ];
        }
        
        $stripeFile = DATA_PLUGIN . 'subscription' . DS . 'stripe.json';
        if (file_exists($stripeFile)) {
            $stripe = json_decode(file_get_contents($stripeFile), true) ?: [];
            $this->config['stripe_api_key'] = $stripe['stripe_api_key'] ?? '';
            $this->config['stripe_webhook_secret'] = $stripe['stripe_webhook_secret'] ?? '';
        } else {
            $this->config['stripe_api_key'] = '';
            $this->config['stripe_webhook_secret'] = '';
        }
    }

    private function log(string $message, string $level = 'info'): void
    {
        if ($this->core && method_exists($this->core, 'getLogger')) {
            $logger = $this->core->getLogger();
            $logger->$level($message);
        } else {
            error_log($message);
        }
    }

    /**
     * Récupère l'abonnement actif d'un utilisateur.
     */
    public function getUserSubscription(int $userId): ?Subscription
    {
        // Mode Test : vérifier les abonnements volatils en session
        $testMode = \Core\Auth\AdminTestMode::getInstance();
        if ($testMode->isActive() && $testMode->getTestUserFixture()) {
            $volatileSubs = $testMode->getVolatileRows('subscriptions');
            foreach ($volatileSubs as $vs) {
                if (($vs['user_id'] ?? 0) == $userId && ($vs['status'] ?? '') === 'active') {
                    if (!empty($vs['expires_at']) && strtotime($vs['expires_at']) < time()) {
                        continue;
                    }
                    return new Subscription($vs);
                }
            }
            return null;
        }

        $subscriptions = Subscription::query()
            ->where('user_id', '=', $userId)
            ->where('status', '=', 'active')
            ->orderBy('created_at', 'DESC')
            ->get();

        foreach ($subscriptions as $sub) {
            // Un abonnement est valide s'il n'a pas de date d'expiration ou si celle-ci est dans le futur
            if ($sub->expires_at === null || strtotime($sub->expires_at) > time()) {
                return $sub;
            }
        }

        return null;
    }

    public function getUserAddons(int $userId, int $subscriptionId): array
    {
        return UserAddon::query()
            ->where('user_id', '=', $userId)
            ->where('subscription_id', '=', $subscriptionId)
            ->where('status', '=', 'active')
            ->get();
    }

    /**
     * Récupère tous les addons actifs pour un utilisateur (indépendamment du lien subscriptionId si nécessaire)
     */
    public function getUserActiveAddons(int $userId): array
    {
        $stmt = \Core\Database::getInstance()->getPdo()->prepare("
            SELECT a.* 
            FROM subscription_addons a
            JOIN subscription_user_addons ua ON a.id = ua.addon_id
            WHERE ua.user_id = ? AND ua.status = 'active'
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getUserPaymentMethods(int $userId): array
    {
        return PaymentMethod::query()
            ->where('user_id', '=', $userId)
            ->orderBy('is_default', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    public function getStripeService(): ?\Subscription\Services\StripeService
    {
        $apiKey = $this->config['stripe_api_key'] ?? '';
        if (empty($apiKey)) return null;
        return new \Subscription\Services\StripeService($apiKey, $this->core);
    }

    public function getAvailableAddons(): array
    {
        return Addon::query()
            ->where('active', '=', 1)
            ->orderBy('monthly_price', 'ASC')
            ->get();
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function calculateAnnualPrice(): float
    {
        $monthlyPrice = (float)($this->config['monthly_price'] ?? 9.99);
        $annualPrice = (float)($this->config['annual_price'] ?? ($monthlyPrice * 12));
        $discount = (float)($this->config['annual_discount'] ?? 0);
        
        if ($discount > 0) {
            return $annualPrice * (1 - ($discount / 100));
        }
        
        return $annualPrice;
    }

    /**
     * Calcule le prix final pour un utilisateur, incluant les remises automatiques (Légende).
     */
    public function calculateFinalPrice(float $basePrice, int $userId): float
    {
        $finalPrice = $basePrice;

        // 1. Remise automatique Légende (-10%)
        if ($this->isLegende($userId)) {
            $finalPrice = $finalPrice * 0.90;
        }

        return round($finalPrice, 2);
    }

    /**
     * Vérifie si un utilisateur a atteint le rang Légende (20,000 pts).
     */
    public function isLegende(int $userId): bool
    {
        if (\Core\Plugin\PluginsManager::isActivePlugin('gamification')) {
            try {
                $pointsService = new \Gamification\Services\PointsService();
                $points = $pointsService->getUserPoints($userId);
                return ($points >= 20000);
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }

    public function getStats(): array
    {
        $all = Subscription::query()->get();
        $stats = [
            'total_subscriptions' => count($all),
            'active_count' => 0,
            'cancelled_count' => 0,
            'expired_count' => 0
        ];
        
        foreach ($all as $sub) {
            if ($sub->status === 'active') $stats['active_count']++;
            elseif ($sub->status === 'cancelled') $stats['cancelled_count']++;
            elseif ($sub->status === 'expired') $stats['expired_count']++;
        }
        
        return $stats;
    }

    public function getAllSubscribers(int $limit = 50, int $offset = 0): array
    {
        return Subscription::query()
            ->orderBy('created_at', 'DESC')
            ->limit($limit, $offset)
            ->get();
    }

    public function getPayments(int $limit = 50, int $offset = 0): array
    {
        return Payment::query()
            ->orderBy('created_at', 'DESC')
            ->limit($limit, $offset)
            ->get();
    }

    public function canActivateTrial(int $userId): bool
    {
        if (empty($this->config['trial_enabled'])) return false;

        $testMode = \Core\Auth\AdminTestMode::getInstance();
        if ($testMode->isActive() && $testMode->getTestUserFixture()) {
            $volatileSubs = $testMode->getVolatileRows('subscriptions');
            foreach ($volatileSubs as $vs) {
                if (($vs['user_id'] ?? 0) == $userId) return false;
            }
            return true;
        }

        return Subscription::query()->where('user_id', '=', $userId)->count() === 0;
    }

    public function activateTrial(int $userId): bool
    {
        if (!$this->canActivateTrial($userId)) return false;

        $duration = (int)($this->config['trial_duration'] ?? 7);

        $testMode = \Core\Auth\AdminTestMode::getInstance();
        if ($testMode->isActive() && $testMode->getTestUserFixture()) {
            $testMode->addVolatileRow('subscriptions', [
                'id' => 999000 + rand(1, 999),
                'user_id' => $userId,
                'plan_type' => 'trial',
                'status' => 'active',
                'monthly_price' => 0.0,
                'annual_price' => 0.0,
                'started_at' => date('Y-m-d H:i:s'),
                'expires_at' => date('Y-m-d H:i:s', strtotime("+{$duration} days")),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            return true;
        }
        
        $sub = new Subscription([
            'user_id' => $userId,
            'plan_type' => 'trial',
            'status' => 'active',
            'monthly_price' => 0.0,
            'annual_price' => 0.0,
            'started_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$duration} days"))
        ]);
        
        return $sub->save();
    }
}

