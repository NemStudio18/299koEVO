<?php

namespace PoiReviews\Entities;

defined('ROOT') or exit('Access denied!');

/**
 * @method static \Core\Storage\QueryBuilder query()
 * @method static self get(int $id)
 */
class Review extends \Core\Storage\JsonActiveRecord
{
    protected static string $tableName = 'poi_reviews';
    protected static string $filePath = DATA_PLUGIN . 'PoiReviews/reviews.json';
    protected static string $storageDriver = self::DRIVER_SQL;

    public function getId(): int
    {
        return (int)($this->attributes['id'] ?? 0);
    }

    public function getPoiId(): int
    {
        return (int)($this->attributes['poi_id'] ?? 0);
    }

    public function setPoiId(int $poiId): self
    {
        $this->attributes['poi_id'] = $poiId;
        return $this;
    }

    public function getUserId()
    {
        return $this->attributes['user_id'] ?? null;
    }

    public function setUserId($userId): self
    {
        $this->attributes['user_id'] = $userId;
        return $this;
    }

    public function getRating(): int
    {
        return (int)($this->attributes['rating'] ?? 0);
    }

    public function setRating(int $rating): self
    {
        $this->attributes['rating'] = $rating;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->attributes['comment'] ?? null;
    }

    public function setComment(?string $comment): self
    {
        $this->attributes['comment'] = $comment;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->attributes['status'] ?? 'active';
    }

    public function setStatus(string $status): self
    {
        $this->attributes['status'] = $status;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->getStatus() === 'active';
    }

    public function isModerated(): bool
    {
        return $this->getStatus() === 'moderated';
    }

    public function getModeratedBy(): ?int
    {
        return isset($this->attributes['moderated_by']) ? (int)$this->attributes['moderated_by'] : null;
    }

    public function setModeratedBy(?int $userId): self
    {
        $this->attributes['moderated_by'] = $userId;
        return $this;
    }

    public function getModeratedAt(): ?string
    {
        return $this->attributes['moderated_at'] ?? null;
    }

    public function setModeratedAt(?string $date): self
    {
        $this->attributes['moderated_at'] = $date;
        return $this;
    }

    public function getModerationReason(): ?string
    {
        return $this->attributes['moderation_reason'] ?? null;
    }

    public function setModerationReason(?string $reason): self
    {
        $this->attributes['moderation_reason'] = $reason;
        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->attributes['created_at'] ?? null;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->attributes['updated_at'] ?? null;
    }
}

