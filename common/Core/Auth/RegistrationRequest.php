<?php

namespace Core\Auth;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

class RegistrationRequest extends JsonActiveRecord
{
    protected static string $filePath = DATA_CORE_AUTH . 'registrations.json';
    protected static string $primaryKey = 'token';

    public function isExpired(): bool
    {
        $ttl = (int) ($this->attributes['expires_at'] ?? 0);
        return $ttl > 0 && time() > $ttl;
    }
}

