<?php

namespace PoiReviews\Entities;

defined('ROOT') or exit('Access denied!');

class ReviewManager
{
    public function create(array $data): ?Review
    {
        $review = new Review([]);
        $review->setPoiId($data['poi_id'])
               ->setUserId($data['user_id'])
               ->setRating($data['rating'])
               ->setComment($data['comment'] ?? null)
               ->setStatus('active');
        
        $saved = $review->save();
        if ($saved) {
            $this->updatePoiStars($data['poi_id']);
        }
        return $saved ? $review : null;
    }

    public function getById(int $id): ?Review
    {
        return Review::get($id);
    }

    public function getByPoi(int $poiId, bool $onlyActive = true): array
    {
        $query = Review::query()->where('poi_id', '=', $poiId);
        if ($onlyActive) {
            $query->where('status', '=', 'active');
        }
        return $query->sortBy('created_at', 'DESC')->get();
    }

    public function getUserReviewForPoi(int $poiId, $userId): ?Review
    {
        $results = Review::query()
            ->where('poi_id', '=', $poiId)
            ->where('user_id', '=', $userId)
            ->where('status', '=', 'active')
            ->limit(1)
            ->get();
            
        return $results[0] ?? null;
    }

    public function getAverageRating(int $poiId): float
    {
        // On récupère tous les avis actifs pour calculer la moyenne
        // Note: une optimisation 'count' et 'avg' dans le queryBuilder serait idéale
        $reviews = Review::query()
            ->where('poi_id', '=', $poiId)
            ->where('status', '=', 'active')
            ->get();
            
        if (empty($reviews)) {
            return 0.0;
        }
        
        $sum = 0;
        foreach ($reviews as $review) {
            $sum += $review->getRating();
        }
        
        return round($sum / count($reviews), 1);
    }

    public function getRatingCount(int $poiId): int
    {
        return Review::query()
            ->where('poi_id', '=', $poiId)
            ->where('status', '=', 'active')
            ->count();
    }

    public function updatePoiStars(int $poiId): void
    {
        if ($poiId <= 0) return;

        // 1. Calculate new average from SQLite
        $avg = $this->getAverageRating($poiId);

        // 2. Update MySQL poi_public
        try {
            $pdo = \Core\Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("UPDATE poi_public SET stars = :stars WHERE id = :id");
            $stmt->bindValue(':stars', $avg);
            $stmt->bindValue(':id', $poiId);
            $stmt->execute();
        } catch (\Exception $e) {
            \Core\Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_ERROR, "PoiReviews: Failed to update stars for POI $poiId: " . $e->getMessage());
        }
    }

    public function moderate(int $id, int $moderatedBy, ?string $reason = null): bool
    {
        $review = $this->getById($id);
        if (!$review) return false;
        
        $review->setStatus('moderated')
               ->setModeratedBy($moderatedBy)
               ->setModeratedAt(date('Y-m-d H:i:s'))
               ->setModerationReason($reason);
               
        $saved = $review->save();
        if ($saved) {
            $this->updatePoiStars($review->getPoiId());
        }
        return $saved;
    }

    public function restore(int $id): bool
    {
        $review = $this->getById($id);
        if (!$review) return false;
        
        $review->setStatus('active')
               ->setModeratedBy(null)
               ->setModeratedAt(null)
               ->setModerationReason(null);
               
        $saved = $review->save();
        if ($saved) {
            $this->updatePoiStars($review->getPoiId());
        }
        return $saved;
    }

    public function delete(int $id): bool
    {
        $review = $this->getById($id);
        if (!$review) return false;
        
        $poiId = $review->getPoiId();
        $review->setStatus('deleted');
        $saved = $review->save();
        if ($saved) {
            $this->updatePoiStars($poiId);
        }
        return $saved;
    }

    /**
     * Recalculate stars for ALL POIs (Heavy operation, use with caution)
     */
    public function syncAllStars(): void
    {
        $logger = \Core\Core::getInstance()->getLogger();
        $logger->log(\Core\Logger::LEVEL_INFO, "PoiReviews: Starting full stars synchronization...");

        try {
            // Get all distinct poi_ids from reviews
            $db = \Core\Database::getInstance()->getPDO('PoiReviews'); 
            // Note: Since ReviewManager abstracts storage, we should use the model directly if possible.
            // But here we need distinct POIs.
             
            // If we are in SQL mode for reviews, we can use SQL. If JSON, we iterate all.
            // Let's assume Review entity usage.
            
            // To be safe and storage-agnostic (mostly), we can iterate if not too many. 
            // Better: use the current driver for Review model.
            
            // For now, let's assume we can query specific POIs or just update all POIs in poi_public that have reviews.
            // Actually, we can just fetch all reviews grouping by poi_id if SQL.
            
            // Simplified approach: Clear all stars in poi_public -> NO, that loses data if reviews are empty.
            // Iterate all POIs in poi_public? Too many.
            
            // Let's rely on the fact that this is triggered after migration.
            // If migrated to SQL:
            $reviews = \PoiReviews\Entities\Review::queryBuilder()->get(); // Might be heavy if thousands.
            
            // OPTIMIZATION: Get distinct POI IDs.
            $poiIds = [];
            foreach ($reviews as $r) {
                $poiIds[$r->getPoiId()] = true;
            }
            
            foreach (array_keys($poiIds) as $poiId) {
                $this->updatePoiStars($poiId);
            }
            
            $logger->log(\Core\Logger::LEVEL_INFO, "PoiReviews: Full stars synchronization completed for " . count($poiIds) . " POIs.");
            
        } catch (\Exception $e) {
            $logger->log(\Core\Logger::LEVEL_ERROR, "PoiReviews: Error during syncAllStars: " . $e->getMessage());
        }
    }

    public function getAll(?string $status = null, int $limit = 100, int $offset = 0): array
    {
        $query = Review::query();
        if ($status !== null) {
            $query->where('status', '=', $status);
        }
        
        return $query->sortBy('created_at', 'DESC')
                     ->limit($limit, $offset)
                     ->get();
    }
}

