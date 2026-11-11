<?php

namespace PoiReviews\Services;

use Core\Logger;
use Core\Auth\User;

defined('ROOT') or exit('Access denied!');

class ClaimService
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function isOwner(int $cmsUserId, int $poiId): bool
    {
        if ($poiId <= 0) return false;
        
        $user = User::find('id', $cmsUserId);
        $email = $user->attributes['email'] ?? null;
        $username = $user->attributes['username'] ?? null;

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

