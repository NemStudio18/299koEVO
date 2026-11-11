<?php

namespace PoiFavorites\Entities;

defined('ROOT') or exit('Access denied!');

/**
 * @method static \Core\Storage\QueryBuilder query()
 * @method static self get(int $id)
 */
class Favorite extends \Core\Storage\JsonActiveRecord
{
    protected static string $tableName = 'favorites';
    protected static string $filePath = DATA_PLUGIN . 'PoiFavorites/favorites.json';
    protected static string $primaryKey = 'id';

    public function getPoiId(): int
    {
        return (int)($this->attributes['poi_id'] ?? 0);
    }

    public function setPoiId(int $poiId): self
    {
        $this->attributes['poi_id'] = $poiId;
        return $this;
    }

    public function getUserId(): int
    {
        return (int)($this->attributes['user_id'] ?? 0);
    }

    public function setUserId(int $userId): self
    {
        $this->attributes['user_id'] = $userId;
        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->attributes['created_at'] ?? null;
    }
}
