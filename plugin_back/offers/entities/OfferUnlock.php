<?php

namespace Offers\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

class OfferUnlock extends JsonActiveRecord
{
    protected static string $tableName = 'offer_unlocks';
    protected static string $filePath = DATA_PLUGIN . 'offers/offer_unlocks.json';

    public function getId(): int
    {
        return (int)($this->attributes['id'] ?? 0);
    }

    public function getOfferId(): int
    {
        return (int)($this->attributes['offer_id'] ?? 0);
    }

    public function getUserId(): ?int
    {
        return isset($this->attributes['user_id']) ? (int)$this->attributes['user_id'] : null;
    }

    public function getSessionId(): ?string
    {
        return $this->attributes['session_id'] ?? null;
    }

    public function getCode(): string
    {
        return (string)($this->attributes['code'] ?? '');
    }

    public function getUnlockedAt(): string
    {
        return (string)($this->attributes['unlocked_at'] ?? '');
    }
}
