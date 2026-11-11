<?php

namespace Offers\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

class OfferView extends JsonActiveRecord
{
    protected static string $tableName = 'offer_views';
    protected static string $filePath = DATA_PLUGIN . 'offers/offer_views.json';

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

    public function getViewedAt(): string
    {
        return (string)($this->attributes['viewed_at'] ?? '');
    }
}
