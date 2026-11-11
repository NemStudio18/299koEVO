<?php

namespace Offers\Services;

use PDO;
use Core\Logger;

defined('ROOT') or exit('Access denied!');

/**
 * Service pour vérifier si un POI est réclamé par un utilisateur
 */
class ClaimService
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Vérifie si un utilisateur est propriétaire d'un POI
     */
    public function isOwner(int $userId, int $poiId): bool
    {
        if ($poiId <= 0) return false;

        $user = \Core\Auth\User::find('id', $userId);
        $email = $user->attributes['email'] ?? null;
        $username = $user->attributes['username'] ?? null;

        $result = \Core\Core::getInstance()->callHook('entity:ownership-verify', [
            'entity_type' => 'poi',
            'entity_id' => $poiId,
            'user_id' => $userId,
            'email' => $email,
            'username' => $username
        ]);

        return ($result === true);
    }

    /**
     * Vérifie si un POI est réclamé (par n'importe qui)
     */
    public function isClaimed(int $poiId): bool
    {
        if ($poiId <= 0) return false;

        $result = \Core\Core::getInstance()->callHook('entity:ownership-verify', [
            'entity_type' => 'poi',
            'entity_id' => $poiId,
            'user_id' => 0 // 0 means "any user"
        ]);

        return ($result === true);
    }

    /**
     * Vérifie si un utilisateur est propriétaire d'au moins un POI
     */
    public function isOwnerOfAnyPoi(int $cmsUserId, ?string $userEmail = null, ?string $username = null): bool
    {
        $result = \Core\Core::getInstance()->callHook('entity:ownership-verify', [
            'entity_type' => 'poi',
            'entity_id' => 0, // 0 means "any POI"
            'user_id' => $cmsUserId,
            'email' => $userEmail,
            'username' => $username
        ]);

        return ($result === true);
    }
}

