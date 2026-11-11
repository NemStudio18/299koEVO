<?php

namespace Agenda\Services;

use Core\Logger;

defined('ROOT') or exit('Access denied!');

/**
 * Service pour vérifier si un utilisateur est propriétaire d'un POI
 */
class ClaimService
{
    private Logger $logger;
    
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Vérifie si un utilisateur est propriétaire d'au moins un POI
     */
    public function isOwner(int $cmsUserId, ?string $email = null, ?string $username = null): bool
    {
        // Utilisation du hook entity:ownership-verify (géré par le plugin claim)
        // entity_id = 0 signifie "n'importe quel POI"
        $result = \Core\Core::getInstance()->callHook('entity:ownership-verify', [
            'entity_type' => 'poi',
            'entity_id' => 0,
            'user_id' => $cmsUserId,
            'email' => $email,
            'username' => $username
        ]);

        return ($result === true);
    }

    /**
     * Vérifie si un utilisateur est propriétaire d'un POI spécifique
     */
    public function isOwnerOfPoi(int $cmsUserId, int $poiId, ?string $email = null, ?string $username = null): bool
    {
        if ($poiId <= 0) return false;

        $result = \Core\Core::getInstance()->callHook('entity:ownership-verify', [
            'entity_type' => 'poi',
            'entity_id' => $poiId,
            'user_id' => $cmsUserId,
            'email' => $email,
            'username' => $username
        ]);

        return ($result === true);
    }
}


