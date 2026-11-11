<?php

namespace Offers\Entities;

defined('ROOT') or exit('Access denied!');

use Offers\Entities\Offer;
use Offers\Entities\OfferView;
use Offers\Entities\OfferUnlock;

class OfferManager
{

    public function __construct()
    {
        // No longer using legacy Database instance
    }

    public function create(array $data): ?Offer
    {
        $offer = new Offer([]);
        $offer->poi_id = $data['poi_id'];
        $offer->title = $data['title'];
        $offer->description = $data['description'] ?? null;
        $offer->advantage = $data['advantage'];
        $offer->condition_text = $data['condition_text'] ?? null;
        $offer->offer_type = $data['offer_type'] ?? 'date';
        $offer->start_date = $data['start_date'];
        $offer->end_date = $data['end_date'] ?? null;
        $offer->max_codes = isset($data['max_codes']) ? (int)$data['max_codes'] : null;
        $offer->status = $data['status'] ?? 'pending';
        $offer->created_by = $data['created_by'] ?? null;
        $offer->is_active = $data['is_active'] ?? 1;
        
        if ($offer->save()) {
            return $offer;
        }
        
        return null;
    }

    public function update(int $id, array $data): bool
    {
        $offer = $this->getById($id);
        if (!$offer) {
            return false;
        }

        $offer->poi_id = $data['poi_id'];
        $offer->title = $data['title'];
        $offer->description = $data['description'] ?? null;
        $offer->advantage = $data['advantage'];
        $offer->condition_text = $data['condition_text'] ?? null;
        $offer->offer_type = $data['offer_type'] ?? 'date';
        $offer->start_date = $data['start_date'];
        $offer->end_date = $data['end_date'] ?? null;
        $offer->max_codes = isset($data['max_codes']) ? (int)$data['max_codes'] : null;
        $offer->status = $data['status'] ?? 'pending';
        $offer->is_active = $data['is_active'] ?? 1;
        
        return $offer->save();
    }

    public function getById(int $id): ?Offer
    {
        return Offer::findPK($id);
    }

    public function getByPoi(int $poiId, bool $onlyActive = true): array
    {
        $query = Offer::query()->where('poi_id', '=', $poiId);
        if ($onlyActive) {
            $query->where('is_active', '=', 1)
                  ->where('status', '=', 'approved');
        }
        $query->orderBy('created_at', 'DESC');
        
        return $query->get();
    }

    public function getAll(?string $status = null, bool $onlyActive = false): array
    {
        $query = Offer::query();
        
        if ($status !== null) {
            $query->where('status', '=', $status);
        }
        
        if ($onlyActive) {
            $query->where('is_active', '=', 1);
        }
        
        $query->orderBy('created_at', 'DESC');
        
        return $query->get();
    }

    public function getByUser(int $userId): array
    {
        return Offer::query()
            ->where('created_by', '=', $userId)
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    public function getActiveOffersByPoi(int $poiId): array
    {
        $today = date('Y-m-d');
        
        $offersEntities = Offer::query()
            ->where('poi_id', '=', $poiId)
            ->where('is_active', '=', 1)
            ->where('status', '=', 'approved')
            ->where('start_date', '<=', $today)
            ->orderBy('created_at', 'DESC')
            ->get();
            
        $offers = [];
        foreach ($offersEntities as $offer) {
            // Filtrer par validité (date ou count)
            if ($offer->isCurrentlyValid()) {
                $offers[] = $offer;
            }
        }
        return $offers;
    }

    public function countActiveByPoi(int $poiId): int
    {
        return Offer::query()
            ->where('poi_id', '=', $poiId)
            ->where('is_active', '=', 1)
            ->whereIn('status', ['pending', 'approved'])
            ->count();
    }

    public function approve(int $id, int $approvedBy): bool
    {
        $offer = $this->getById($id);
        if (!$offer) {
            return false;
        }

        $offer->status = 'approved';
        $offer->approved_at = date('Y-m-d H:i:s');
        $offer->approved_by = $approvedBy;
        
        return $offer->save();
    }

    public function reject(int $id, int $rejectedBy): bool
    {
        $offer = $this->getById($id);
        if (!$offer) {
            return false;
        }

        $offer->status = 'rejected';
        $offer->approved_by = $rejectedBy; // Re-use approved_by column for rejected_by if needed, or follow legacy
        
        return $offer->save();
    }

    public function delete(int $id): bool
    {
        $offer = $this->getById($id);
        return $offer ? $offer->delete() : false;
    }

    public function incrementViews(int $id): void
    {
        $offer = $this->getById($id);
        if ($offer) {
            $offer->views_count = $offer->getViewsCount() + 1;
            $offer->save();
        }
    }

    public function incrementUnlocks(int $id): void
    {
        $offer = $this->getById($id);
        if ($offer) {
            $offer->unlocks_count = $offer->getUnlocksCount() + 1;
            $offer->save();
        }
    }

    public function recordView(int $offerId, ?int $userId = null, ?string $sessionId = null): void
    {
        $view = new OfferView();
        $view->offer_id = $offerId;
        $view->user_id = $userId;
        $view->session_id = $sessionId;
        $view->save();
    }

    public function recordUnlock(int $offerId, string $code, ?int $userId = null, ?string $sessionId = null): void
    {
        $unlock = new OfferUnlock();
        $unlock->offer_id = $offerId;
        $unlock->user_id = $userId;
        $unlock->session_id = $sessionId;
        $unlock->code = $code;
        $unlock->save();
    }

    public function getStats(int $offerId): array
    {
        $offer = $this->getById($offerId);
        if (!$offer) {
            return [];
        }

        $views = OfferView::query()->where('offer_id', '=', $offerId)->count();
        $unlocks = OfferUnlock::query()->where('offer_id', '=', $offerId)->count();
        
        // UNIQUE stats (using local grouping since JsonActiveRecord doesn't support complex DISTINCT/COALESCE)
        $allViews = OfferView::query()->where('offer_id', '=', $offerId)->get();
        $uniqueViewKeys = [];
        foreach ($allViews as $v) {
            $key = $v->getUserId() ?: $v->getSessionId();
            if ($key) $uniqueViewKeys[$key] = true;
        }
        $uniqueViews = count($uniqueViewKeys);

        $allUnlocks = OfferUnlock::query()->where('offer_id', '=', $offerId)->get();
        $uniqueUnlockKeys = [];
        foreach ($allUnlocks as $u) {
            $key = $u->getUserId() ?: $u->getSessionId();
            if ($key) $uniqueUnlockKeys[$key] = true;
        }
        $uniqueUnlocks = count($uniqueUnlockKeys);

        return [
            'views' => $views,
            'unlocks' => $unlocks,
            'unique_views' => $uniqueViews,
            'unique_unlocks' => $uniqueUnlocks,
        ];
    }
}

