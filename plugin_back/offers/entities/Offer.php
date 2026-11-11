<?php

namespace Offers\Entities;

defined('ROOT') or exit('Access denied!');

use Core\Storage\JsonActiveRecord;

/**
 * @property int $id
 * @property int $poi_id
 * @property string $title
 * @property string $description
 * @property string $advantage
 * @property string $condition_text
 * @property string $offer_type
 * @property string $start_date
 * @property string $end_date
 * @property int $max_codes
 * @property string $status
 * @property int $created_by
 * @property string $created_at
 * @property string $approved_at
 * @property int $approved_by
 * @property int $views_count
 * @property int $unlocks_count
 * @property string $partner_poi_ids
 * @property int $event_id
 * @property int $is_active
 */
class Offer extends JsonActiveRecord
{
    protected static string $tableName = 'offers';
    protected static string $filePath = DATA_PLUGIN . 'offers/offers.json';

    public function getId(): int
    {
        return (int)($this->attributes['id'] ?? 0);
    }

    public function getPoiId(): int
    {
        return (int)($this->attributes['poi_id'] ?? 0);
    }

    public function getTitle(): string
    {
        return (string)($this->attributes['title'] ?? '');
    }

    public function getDescription(): ?string
    {
        return $this->attributes['description'] ?? null;
    }

    public function getAdvantage(): string
    {
        return (string)($this->attributes['advantage'] ?? '');
    }

    public function getConditionText(): ?string
    {
        return $this->attributes['condition_text'] ?? null;
    }

    public function getStartDate(): string
    {
        return (string)($this->attributes['start_date'] ?? '');
    }

    public function getEndDate(): ?string
    {
        return $this->attributes['end_date'] ?? null;
    }

    public function getOfferType(): string
    {
        return (string)($this->attributes['offer_type'] ?? 'date');
    }

    public function getMaxCodes(): ?int
    {
        return isset($this->attributes['max_codes']) ? (int)$this->attributes['max_codes'] : null;
    }

    public function getStatus(): string
    {
        return (string)($this->attributes['status'] ?? 'pending');
    }

    public function getCreatedBy(): ?int
    {
        return isset($this->attributes['created_by']) ? (int)$this->attributes['created_by'] : null;
    }

    public function getCreatedAt(): string
    {
        return (string)($this->attributes['created_at'] ?? '');
    }

    public function getApprovedAt(): ?string
    {
        return $this->attributes['approved_at'] ?? null;
    }

    public function getApprovedBy(): ?int
    {
        return isset($this->attributes['approved_by']) ? (int)$this->attributes['approved_by'] : null;
    }

    public function setStatus(string $status): self
    {
        $this->attributes['status'] = $status;
        return $this;
    }

    public function setApprovedAt(string $date): self
    {
        $this->attributes['approved_at'] = $date;
        return $this;
    }

    public function setApprovedBy(int $userId): self
    {
        $this->attributes['approved_by'] = $userId;
        return $this;
    }

    public function getViewsCount(): int
    {
        return (int)($this->attributes['views_count'] ?? 0);
    }

    public function getUnlocksCount(): int
    {
        return (int)($this->attributes['unlocks_count'] ?? 0);
    }

    public function isActive(): bool
    {
        return (bool)($this->attributes['is_active'] ?? true);
    }

    public function isApproved(): bool
    {
        return $this->getStatus() === 'approved';
    }

    public function isPending(): bool
    {
        return $this->getStatus() === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->getStatus() === 'rejected';
    }

    public function isCurrentlyValid(): bool
    {
        if (!$this->isActive() || !$this->isApproved()) {
            return false;
        }
        
        $today = date('Y-m-d');
        
        // Vérifier que la date de début est passée
        if ($today < $this->getStartDate()) {
            return false;
        }
        
        // Type "date" : vérifier la date de fin
        if ($this->getOfferType() === 'date') {
            $endDate = $this->getEndDate();
            if ($endDate === null) {
                return false;
            }
            return $today <= $endDate;
        }
        
        // Type "count" : vérifier le nombre de codes générés
        if ($this->getOfferType() === 'count') {
            $maxCodes = $this->getMaxCodes();
            if ($maxCodes === null || $maxCodes <= 0) {
                return false;
            }
            // Vérifier si le nombre de déblocages est inférieur au maximum
            return $this->getUnlocksCount() < $maxCodes;
        }
        
        return false;
    }

    public function getPartnerPoiIds(): array
    {
        $ids = $this->attributes['partner_poi_ids'] ?? '[]';
        if (is_string($ids)) {
            $decoded = json_decode($ids, true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_array($ids) ? $ids : [];
    }

    public function setPartnerPoiIds(array $ids): self
    {
        $this->attributes['partner_poi_ids'] = json_encode($ids);
        return $this;
    }

    public function getEventId(): ?int
    {
        return isset($this->attributes['event_id']) ? (int)$this->attributes['event_id'] : null;
    }

    public function setEventId(?int $eventId): self
    {
        $this->attributes['event_id'] = $eventId;
        return $this;
    }
}

