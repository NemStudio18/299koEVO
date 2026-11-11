<?php

namespace Offers\Services;

use PDO;
use Core\Logger;

defined('ROOT') or exit('Access denied!');

/**
 * Service pour vérifier les abonnements des utilisateurs
 */
class SubscriptionService
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Vérifie si un utilisateur a un abonnement actif
     */
    public function hasActiveSubscription(int $cmsUserId, ?string $email = null, ?string $username = null): bool
    {
        $result = \Core\Core::getInstance()->callHook('user:subscription-verify', [
            'user_id' => $cmsUserId,
            'email' => $email,
            'username' => $username
        ]);

        return ($result === true);
    }

    /**
     * Récupère le nombre maximum d'offres autorisées pour un utilisateur
     * 4 si abonné, 1 sinon
     */
    public function getMaxOffersForUser(int $cmsUserId, ?string $email = null, ?string $username = null): int
    {
        if ($this->hasActiveSubscription($cmsUserId, $email, $username)) {
            return 2;
        }
        return 1;
    }
}



