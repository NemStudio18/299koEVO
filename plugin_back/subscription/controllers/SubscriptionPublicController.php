<?php

namespace Subscription\Controllers;

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Subscription\Services\SubscriptionService;
use PDO;

defined('ROOT') or exit('Access denied!');

class SubscriptionPublicController extends PublicController
{
    public function home()
    {
        return $this->manage();
    }

    /**
     * Page de gestion de l'abonnement
     */
    public function manage()
    {
        // --- UNIVERSAL POLICY INTEGRATION (Phase 10) ---
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);

        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('user:subscription:view'), $ctx)) {
            \Utils\Show::msg('Vous devez être connecté et éligible pour accéder à cette page.', 'error');
            $this->core->redirect($this->router->generate('login'));
            return;
        }

        try {
            $service = $this->getSubscriptionService();
            $userId = (int)($this->user->attributes['id'] ?? 0);
            $userEmail = $this->user->attributes['email'] ?? null;
            $username = $this->user->attributes['username'] ?? null;

            // La politique user:subscription:manage pourrait aussi vérifier l'ownership via SQL ou JSON condition
            if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('user:subscription:manage'), $ctx)) {
                \Utils\Show::msg('Vous devez être propriétaire d\'au moins une fiche POI pour vous abonner.', 'error');
                $this->core->redirect($this->router->generate('home'));
                return;
            }

            $mysqlUserId = $this->getMySQLUserId($userEmail, $username, $userId);
            if (!$mysqlUserId) {
                \Utils\Show::msg('Utilisateur non trouvé dans la base de données.', 'error');
                $this->core->redirect($this->router->generate('home'));
                return;
            }

            $subscription = $service->getUserSubscription($mysqlUserId);
            $addons = [];
            $paymentMethods = [];
            
            // Ne charger les addons et moyens de paiement que si l'abonnement est actif
            if ($subscription && isset($subscription['status']) && $subscription['status'] === 'active') {
                $addons = $service->getUserAddons($mysqlUserId, $subscription['id']);
                $paymentMethods = $service->getUserPaymentMethods($mysqlUserId);
            } else {
                // S'assurer que subscription est null si ce n'est pas actif
                $subscription = null;
            }

            $availableAddons = $service->getAvailableAddons();
            $config = $service->getConfig();
            $monthlyPrice = $service->calculateFinalPrice((float)$config['monthly_price'], $mysqlUserId);
            $annualPrice = $service->calculateFinalPrice($service->calculateAnnualPrice(), $mysqlUserId);

            $canActivateTrial = $service->canActivateTrial($mysqlUserId);
            $isLegende = $service->isLegende($mysqlUserId);

            $response = new PublicResponse();
            $tpl = $response->createPluginTemplate('subscription', 'manage');
            $tpl->set('subscription', $subscription);
            $tpl->set('addons', $addons);
            $tpl->set('availableAddons', $availableAddons);
            $tpl->set('paymentMethods', $paymentMethods);
            $tpl->set('config', $config);
            $tpl->set('monthlyPrice', $monthlyPrice);
            $tpl->set('annualPrice', $annualPrice);
            $tpl->set('canActivateTrial', $canActivateTrial);
            $tpl->set('isLegende', $isLegende);
            $response->setTitle('Gestion de mon abonnement');
            $response->addTemplate($tpl);
            return $response;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('SubscriptionPublicController::manage - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            $this->core->redirect($this->router->generate('home'));
            return;
        }
    }

    /**
     * Souscrire à un abonnement
     */
    public function subscribe()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('user:subscription:manage'), $ctx)) {
            \Utils\Show::msg('Vous devez être connecté.', 'error');
            $this->core->redirect($this->router->generate('login'));
            return;
        }

        try {
            $service = $this->getSubscriptionService();
            $userId = (int)($this->user->attributes['id'] ?? 0);
            $userEmail = $this->user->attributes['email'] ?? null;
            $username = $this->user->attributes['username'] ?? null;

            // Vérifier que l'utilisateur est propriétaire via le hook
            $isOwner = \Core\Core::getInstance()->callHook('entity:ownership-verify', [
                'entity_type' => 'poi',
                'entity_id' => 0,
                'user_id' => $userId,
                'email' => $userEmail,
                'username' => $username
            ]);

            if ($isOwner !== true) {
                \Utils\Show::msg('Vous devez être propriétaire d\'au moins une fiche POI.', 'error');
                $this->core->redirect($this->router->generate('subscription-home'));
                return;
            }

            $mysqlUserId = $this->getMySQLUserId($userEmail, $username, $userId);
            if (!$mysqlUserId) {
                \Utils\Show::msg('Utilisateur non trouvé.', 'error');
                $this->core->redirect($this->router->generate('subscription-home'));
                return;
            }

            // Vérifier que les abonnements sont activés
            $config = $service->getConfig();
            if (!($config['enabled'] ?? true)) {
                \Utils\Show::msg('Les abonnements sont actuellement désactivés.', 'error');
                $this->core->redirect($this->router->generate('subscription-manage'));
                return;
            }

            $planType = trim($_POST['plan_type'] ?? 'monthly');
            if (!in_array($planType, ['monthly', 'annual'])) {
                \Utils\Show::msg('Type d\'abonnement invalide.', 'error');
                $this->core->redirect($this->router->generate('subscription-home'));
                return;
            }

            $stripeService = $service->getStripeService();
            if (!$stripeService) {
                \Utils\Show::msg('Configuration Stripe manquante. Veuillez contacter l\'administrateur.', 'error');
                $this->core->redirect($this->router->generate('subscription-manage'));
                return;
            }

            // PROMO CODE LOGIC
            $promoCodeStr = trim($_POST['promo_code'] ?? '');
            $appliedPromo = null;
            if (!empty($promoCodeStr)) {
                $promoManager = new \Subscription\Entities\PromoCodeManager($this->getPdo(), $this->getTablePrefix());
                $validation = $promoManager->validateCode($promoCodeStr);
                
                if (!$validation['valid']) {
                    \Utils\Show::msg($validation['message'], 'error');
                    $this->core->redirect($this->router->generate('subscription-manage'));
                    return;
                }
                
                $appliedPromo = $validation['promo'];
            }

            // 100% DISCOUNT BYPASS
            if ($appliedPromo && $appliedPromo['discount_percentage'] == 100) {
                // Determine expiration date based on promo duration
                $expiresAt = null;
                $duration = $appliedPromo['duration'];
                if ($duration === '1_month') {
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 month'));
                } elseif ($duration === '3_months') {
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+3 months'));
                } elseif ($duration === '1_year' || $planType === 'annual') {
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 year'));
                } else {
                    // Infinite 100% or monthly generic -> we will give 100 years for infinite 100%
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+100 years'));
                }

                // Delete any existing pending or active sub to replace it
                $stmtDel = $this->getPdo()->prepare("DELETE FROM {$this->getTablePrefix()}subscriptions WHERE user_id = ?");
                $stmtDel->execute([$mysqlUserId]);

                // Create the active subscription directly
                $sqlInput = "INSERT INTO {$this->getTablePrefix()}subscriptions 
                        (user_id, plan_type, status, monthly_price, annual_price, stripe_customer_id, stripe_subscription_id, started_at, expires_at, created_at)
                        VALUES (?, ?, 'active', 0, 0, NULL, NULL, NOW(), ?, NOW())";
                $stmtInput = $this->getPdo()->prepare($sqlInput);
                $stmtInput->execute([
                    $mysqlUserId,
                    $planType,
                    $expiresAt
                ]);

                // Increment promo usage
                $promoManager->incrementUsage($appliedPromo['id']);

                \Utils\Show::msg('Code promotionnel 100% appliqué. Votre abonnement est maintenant actif !', 'success');
                $this->core->redirect($this->router->generate('subscription-manage'));
                return;
            }

            // Calculer le prix (si la promo n'est pas 100% on lance Stripe quand même. Idéalement on appliquerait un coupon Stripe, 
            // mais l'intégration partielle nécessite une config avancée API Stripe, donc on gère les 100% pour l'instant)
            if ($appliedPromo && $appliedPromo['discount_percentage'] < 100) {
                 \Utils\Show::msg("Les réductions partielles (" . $appliedPromo['discount_percentage'] . "%) nécessitent une configuration de coupon dans la passerelle de paiement, non implémentée actuellement.", 'error');
                 $this->core->redirect($this->router->generate('subscription-manage'));
                 return;
            }

            $config = $service->getConfig();
            $amount = $planType === 'annual' ? $service->calculateAnnualPrice() : (float)$config['monthly_price'];
            $interval = $planType === 'annual' ? 'year' : 'month';
            $description = $planType === 'annual' ? 'Abonnement annuel' : 'Abonnement mensuel';

            // Créer ou récupérer le client Stripe
            $customer = $this->getOrCreateStripeCustomer($stripeService, $mysqlUserId, $userEmail, $username);
            if (!$customer) {
                \Utils\Show::msg('Erreur lors de la création du client.', 'error');
                $this->core->redirect($this->router->generate('subscription-manage'));
                return;
            }

            $router = $this->router;
            $successUrl = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' 
                . $_SERVER['HTTP_HOST'] 
                . $router->generate('subscription-payment-success');
            
            $cancelUrl = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') . '://' 
                . $_SERVER['HTTP_HOST'] 
                . $router->generate('subscription-manage');

            // Créer la session Stripe Checkout
            $session = $stripeService->createCheckoutSession(
                $customer['id'],
                $amount,
                $interval,
                $description,
                $successUrl,
                $cancelUrl,
                [
                    'user_id' => $mysqlUserId,
                    'plan_type' => $planType,
                ]
            );

            if (!$session || !isset($session['url'])) {
                \Utils\Show::msg('Erreur lors de la création de la session de paiement.', 'error');
                $this->core->redirect($this->router->generate('subscription-manage'));
                return;
            }

            // Enregistrer l'abonnement en attente
            $this->savePendingSubscription($mysqlUserId, $planType, $amount, $customer['id'], $session['id'], null);

            // Rediriger vers Stripe
            header('Location: ' . $session['url']);
            exit;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('SubscriptionPublicController::subscribe - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            $this->core->redirect($this->router->generate('subscription-manage'));
            return;
        }
    }

    /**
     * Activer l'essai gratuit
     */
    public function startTrial()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('user:subscription:manage'), $ctx)) {
            \Utils\Show::msg('Vous devez être connecté.', 'error');
            $this->core->redirect($this->router->generate('login'));
            return;
        }

        try {
            $service = $this->getSubscriptionService();
            $userId = (int)($this->user->attributes['id'] ?? 0);
            $userEmail = $this->user->attributes['email'] ?? null;
            $username = $this->user->attributes['username'] ?? null;

            // Vérifier que l'utilisateur est propriétaire via le hook
            $isOwner = \Core\Core::getInstance()->callHook('entity:ownership-verify', [
                'entity_type' => 'poi',
                'entity_id' => 0,
                'user_id' => $userId,
                'email' => $userEmail,
                'username' => $username
            ]);

            if ($isOwner !== true) {
                \Utils\Show::msg('Vous devez être propriétaire d\'au moins une fiche POI.', 'error');
                $this->core->redirect($this->router->generate('subscription-manage'));
                return;
            }

            $mysqlUserId = $this->getMySQLUserId($userEmail, $username, $userId);
            if (!$mysqlUserId) {
                \Utils\Show::msg('Utilisateur non trouvé.', 'error');
                $this->core->redirect($this->router->generate('subscription-manage'));
                return;
            }

            if (!$service->activateTrial($mysqlUserId)) {
                \Utils\Show::msg('Impossible d\'activer l\'essai gratuit (déjà utilisé ou désactivé).', 'error');
            } else {
                \Utils\Show::msg('Essai gratuit activé avec succès !', 'success');
            }

            $this->core->redirect($this->router->generate('subscription-manage'));
            return;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('SubscriptionPublicController::startTrial - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            $this->core->redirect($this->router->generate('subscription-manage'));
            return;
        }
    }

    /**
     * Annuler un abonnement
     */
    public function cancel()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('user:subscription:manage'), $ctx)) {
            \Utils\Show::msg('Vous devez être connecté.', 'error');
            $this->core->redirect($this->router->generate('login'));
            return;
        }

        try {
            $service = $this->getSubscriptionService();
            $userId = (int)($this->user->attributes['id'] ?? 0);
            $userEmail = $this->user->attributes['email'] ?? null;
            $username = $this->user->attributes['username'] ?? null;

            $mysqlUserId = $this->getMySQLUserId($userEmail, $username, $userId);
            if (!$mysqlUserId) {
                \Utils\Show::msg('Utilisateur non trouvé.', 'error');
                $this->core->redirect($this->router->generate('subscription-manage'));
                return;
            }

            $subscription = $service->getUserSubscription($mysqlUserId);
            if (!$subscription) {
                \Utils\Show::msg('Aucun abonnement actif trouvé.', 'error');
                $this->core->redirect($this->router->generate('subscription-manage'));
                return;
            }

            // Annuler l'abonnement dans Stripe
            $stripeService = $service->getStripeService();
            if ($stripeService && !empty($subscription['stripe_subscription_id'])) {
                $cancelled = $stripeService->cancelSubscription(
                    $subscription['stripe_subscription_id']
                );
                if (!$cancelled) {
                    $this->core->getLogger()->warning('SubscriptionPublicController::cancel - Failed to cancel in Stripe');
                }
            }

            // Marquer comme annulé en base
            $sql = "UPDATE {$this->getTablePrefix()}subscriptions 
                    SET status = 'cancelled', cancelled_at = NOW() 
                    WHERE id = ? AND user_id = ?";
            $stmt = $this->getPdo()->prepare($sql);
            $stmt->execute([$subscription['id'], $mysqlUserId]);

            \Utils\Show::msg('Votre abonnement a été annulé.', 'success');
            $this->core->redirect($this->router->generate('subscription-manage'));
            return;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('SubscriptionPublicController::cancel - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            $this->core->redirect($this->router->generate('subscription-manage'));
            return;
        }
    }

    /**
     * Gérer les moyens de paiement
     */
    public function paymentMethods()
    {
        \Utils\Show::msg('Fonctionnalité en cours de développement.', 'info');
        return $this->manage();
    }

    /**
     * Ajouter un moyen de paiement
     */
    public function addPaymentMethod()
    {
        \Utils\Show::msg('Fonctionnalité en cours de développement.', 'info');
        $this->core->redirect($this->router->generate('subscription-manage'));
        return;
    }

    /**
     * Supprimer un moyen de paiement
     */
    public function removePaymentMethod(int $methodId)
    {
        \Utils\Show::msg('Fonctionnalité en cours de développement.', 'info');
        $this->core->redirect($this->router->generate('subscription-manage'));
        return;
    }

    /**
     * Ajouter un add-on
     */
    public function addAddon()
    {
        // TODO: Ajouter un add-on à l'abonnement
        \Utils\Show::msg('Fonctionnalité en cours de développement.', 'info');
        $this->core->redirect($this->router->generate('subscription-manage'));
        return;
    }

    /**
     * Supprimer un add-on
     */
    public function removeAddon(int $addonId)
    {
        // TODO: Supprimer un add-on de l'abonnement
        \Utils\Show::msg('Fonctionnalité en cours de développement.', 'info');
        $this->core->redirect($this->router->generate('subscription-manage'));
        return;
    }

    /**
     * Webhook Stripe
     */
    public function webhook()
    {
        $payload = @file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        
        $service = $this->getSubscriptionService();
        $config = $service->getConfig();
        $endpointSecret = $config['stripe_webhook_secret'] ?? '';
        $stripeService = $service->getStripeService();
        
        if (!$stripeService) {
            http_response_code(500);
            exit('Stripe service not configured');
        }

        if (!$stripeService->verifyWebhookSignature($payload, $sigHeader, $endpointSecret)) {
            http_response_code(400);
            exit('Invalid signature');
        }

        $event = json_decode($payload, true);
        
        switch ($event['type']) {
            case 'checkout.session.completed':
                $session = $event['data']['object'];
                $this->handleCheckoutCompleted($session);
                break;
            case 'invoice.paid':
                $invoice = $event['data']['object'];
                $this->handleInvoicePaid($invoice);
                break;
            case 'customer.subscription.deleted':
                $subscription = $event['data']['object'];
                $this->handleSubscriptionDeleted($subscription);
                break;
        }

        http_response_code(200);
        echo 'OK';
        exit;
    }

    /**
     * Traite un checkout réussi
     */
    private function handleCheckoutCompleted(array $session): void
    {
        $userId = $session['metadata']['user_id'] ?? null;
        if (!$userId) return;

        $stripeSubscriptionId = $session['subscription'] ?? null;
        $stripeCustomerId = $session['customer'] ?? null;

        $sql = "UPDATE {$this->getTablePrefix()}subscriptions 
                SET stripe_subscription_id = ?, status = 'active', started_at = NOW() 
                WHERE user_id = ?";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute([$stripeSubscriptionId, $userId]);

        // Nouveau : Gérer la commission de parrainage (RevShare 10%)
        $this->processReferralCommission((int)$userId, (float)($session['amount_total'] ?? 0) / 100, 'Premier abonnement');
    }

    /**
     * Traite une facture payée (renouvellement)
     */
    private function handleInvoicePaid(array $invoice): void
    {
        $subscriptionId = $invoice['subscription'] ?? null;
        if (!$subscriptionId) return;

        // Prolonger la date d'expiration
        $sql = "UPDATE {$this->getTablePrefix()}subscriptions 
                SET expires_at = CASE 
                    WHEN plan_type = 'annual' THEN DATE_ADD(expires_at, INTERVAL 1 YEAR)
                    ELSE DATE_ADD(expires_at, INTERVAL 1 MONTH)
                END, status = 'active'
                WHERE stripe_subscription_id = ?";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute([$subscriptionId]);

        // Enregistrer le paiement
        $sql = "SELECT id, user_id FROM {$this->getTablePrefix()}subscriptions WHERE stripe_subscription_id = ?";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute([$subscriptionId]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sub) {
            $amount = (float)($invoice['amount_paid'] / 100);
            $this->recordPayment(
                $sub['id'],
                $sub['user_id'],
                $amount,
                $invoice['payment_intent'] ?? '',
                $invoice['id'],
                'subscription'
            );

            // Nouveau : Gérer la commission de parrainage (RevShare 10%) sur renouvellement
            $this->processReferralCommission((int)$sub['user_id'], $amount, 'Renouvellement');
        }
    }

    /**
     * Traite la suppression d'un abonnement
     */
    private function handleSubscriptionDeleted(array $subscription): void
    {
        $id = $subscription['id'] ?? null;
        if (!$id) return;

        $sql = "UPDATE {$this->getTablePrefix()}subscriptions 
                SET status = 'expired', cancelled_at = NOW() 
                WHERE stripe_subscription_id = ?";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute([$id]);
    }

    /**
     * Enregistre un paiement en base
     */
    private function recordPayment(int $subscriptionId, int $userId, float $amount, string $stripePaymentIntentId, ?string $stripeInvoiceId, string $paymentType): void
    {
        $sql = "INSERT INTO {$this->getTablePrefix()}subscription_payments 
                (subscription_id, user_id, amount, currency, stripe_payment_intent_id, stripe_invoice_id, status, payment_type, created_at)
                VALUES (?, ?, ?, 'EUR', ?, ?, 'succeeded', ?, NOW())";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute([
            $subscriptionId,
            $userId,
            $amount,
            $stripePaymentIntentId,
            $stripeInvoiceId,
            $paymentType,
        ]);
    }

    /**
     * Sauvegarde un abonnement en attente
     */
    private function savePendingSubscription(int $userId, string $planType, float $amount, string $stripeCustomerId, string $stripeSessionId, ?string $stripeSubscriptionId): void
    {
        $sql = "INSERT INTO {$this->getTablePrefix()}subscriptions 
                (user_id, plan_type, status, monthly_price, annual_price, stripe_customer_id, stripe_subscription_id, created_at)
                VALUES (?, ?, 'pending', ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                plan_type = VALUES(plan_type),
                status = 'pending',
                stripe_customer_id = VALUES(stripe_customer_id),
                stripe_subscription_id = COALESCE(VALUES(stripe_subscription_id), stripe_subscription_id),
                updated_at = NOW()";
        
        $service = $this->getSubscriptionService();
        $config = $service->getConfig();
        $monthlyPrice = (float)$config['monthly_price'];
        $annualPrice = $service->calculateAnnualPrice();
        
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute([
            $userId,
            $planType,
            $monthlyPrice,
            $planType === 'annual' ? $annualPrice : null,
            $stripeCustomerId,
            $stripeSubscriptionId,
        ]);
    }

    /**
     * Récupère ou crée un client Stripe
     */
    private function getOrCreateStripeCustomer(\Subscription\Services\StripeService $stripeService, int $mysqlUserId, ?string $email, ?string $username): ?array
    {
        // Vérifier si un client existe déjà
        $sql = "SELECT stripe_customer_id FROM {$this->getTablePrefix()}subscriptions 
                WHERE user_id = ? AND stripe_customer_id IS NOT NULL 
                LIMIT 1";
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute([$mysqlUserId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && !empty($result['stripe_customer_id'])) {
            $customer = $stripeService->getCustomer($result['stripe_customer_id']);
            if ($customer) {
                return $customer;
            }
        }

        // Créer un nouveau client
        $name = $username ?? $email ?? 'Utilisateur';
        $customer = $stripeService->createCustomer($email ?? '', $name);
        return $customer;
    }

    /**
     * Page de succès après paiement Stripe
     */
    public function paymentSuccess()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('user:subscription:view'), $ctx)) {
            \Utils\Show::msg('Vous devez être connecté.', 'error');
            $this->core->redirect($this->router->generate('login'));
            return;
        }

        $sessionId = $_GET['session_id'] ?? '';
        
        if (empty($sessionId)) {
            \Utils\Show::msg('Paramètres invalides.', 'error');
            $this->core->redirect($this->router->generate('subscription-manage'));
            return;
        }

        $service = $this->getSubscriptionService();
        $stripeService = $service->getStripeService();
        
        if (!$stripeService) {
            \Utils\Show::msg('Configuration Stripe manquante.', 'error');
            $this->core->redirect($this->router->generate('subscription-manage'));
            return;
        }

        $session = $stripeService->getSession($sessionId);
        if (!$session) {
            \Utils\Show::msg('Session introuvable.', 'error');
            $this->core->redirect($this->router->generate('subscription-manage'));
            return;
        }

        if ($session['payment_status'] === 'paid') {
            \Utils\Show::msg('Paiement réussi ! Votre abonnement est maintenant actif.', 'success');
        } else {
            \Utils\Show::msg('Le paiement a échoué ou est en attente.', 'error');
        }

        $this->core->redirect($this->router->generate('subscription-manage'));
        return;
    }

    private function getDbConfig(): array
    {
        $configFile = DATA_PLUGIN . 'search' . DS . 'search_conf.json';
        if (!file_exists($configFile)) {
            throw new \RuntimeException('Configuration de base de données introuvable');
        }
        $cfg = json_decode(file_get_contents($configFile), true);
        if (!$cfg) {
            throw new \RuntimeException('Configuration de base de données invalide');
        }
        return $cfg;
    }

    private function getPdo(): PDO
    {
        $testMode = \Core\Auth\AdminTestMode::getInstance();
        $cfg = $this->getDbConfig();
        
        // Essayer d'abord avec les identifiants de la config
        $host = $cfg['sql_host'] ?? '127.0.0.1';
        $db = $cfg['sql_db'] ?? 'vivrebourges';
        $user = $cfg['sql_user'] ?? 'my_webapp';
        $pass = $cfg['sql_pass'] ?? 'Production18#';
        
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $db);
        
        try {
            return new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (\PDOException $e) {
            // Mode Test : fallback sur SQLite en mémoire si MySQL échoue
            if ($testMode->isActive()) {
                $this->core->getLogger()->warning('Subscription: Falling back to in-memory SQLite for Test Mode.');
                return new PDO('sqlite::memory:', null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            }

            // Si l'authentification échoue, essayer avec les identifiants par défaut
            if (strpos($e->getMessage(), 'Access denied') !== false) {
                $defaultUser = 'my_webapp';
                $defaultPass = 'Production18#';
                $defaultDb = 'vivrebourges';
                
                // Ne réessayer que si l'utilisateur est différent
                if ($user !== $defaultUser || $db !== $defaultDb) {
                    $defaultDsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $defaultDb);
                    try {
                        return new PDO($defaultDsn, $defaultUser, $defaultPass, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        ]);
                    } catch (\PDOException $e2) {
                        // Si ça échoue encore, relancer l'exception originale
                        throw $e;
                    }
                }
            }
            // Pour toute autre erreur, relancer l'exception
            throw $e;
        }
    }

    private function getTablePrefix(): string
    {
        return '';
    }

    private function getMySQLUserId(?string $email, ?string $username, int $cmsUserId): ?int
    {
        // Mode Test : retourner l'ID du test user directement
        $testMode = \Core\Auth\AdminTestMode::getInstance();
        if ($testMode->isActive()) {
            $fixture = $testMode->getTestUserFixture();
            if ($fixture) {
                return (int)($fixture['id'] ?? 0);
            }
        }

        try {
            $pdo = $this->getPdo();
            $sql = "SELECT id FROM {$this->getTablePrefix()}users WHERE email = ? OR username = ? LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email, $username]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['id'] : null;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('SubscriptionPublicController::getMySQLUserId - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Traite la commission de parrainage via Hook (Découplage Phase 9)
     */
    private function processReferralCommission(int $userId, float $amount, string $reason = '')
    {
        \Core\Core::getInstance()->callHook('parrainage:record-commission', [
            'user_id' => $userId,
            'amount' => $amount,
            'reason' => $reason
        ]);
    }

    private function getSubscriptionService(): SubscriptionService
    {
        if (!class_exists('\Subscription\Services\SubscriptionService')) {
            require_once PLUGINS . 'subscription/services/SubscriptionService.php';
        }
        return new SubscriptionService($this->getPdo(), '', $this->core);
    }
}
