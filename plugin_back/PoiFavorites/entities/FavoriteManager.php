<?php

namespace PoiFavorites\Entities;

defined('ROOT') or exit('Access denied!');

class FavoriteManager
{
    public function add(int $poiId, int $userId): bool
    {
        if ($this->isFavorite($poiId, $userId)) {
            return true;
        }
        
        $fav = new Favorite();
        $fav->setPoiId($poiId)
            ->setUserId($userId);
            
        return $fav->save();
    }

    public function remove(int $poiId, int $userId): bool
    {
        $favs = Favorite::query()
            ->where('poi_id', '=', $poiId)
            ->where('user_id', '=', $userId)
            ->get();
            
        foreach ($favs as $fav) {
            $fav->delete();
        }
        return true;
    }

    public function isFavorite(int $poiId, int $userId): bool
    {
        return Favorite::query()
            ->where('poi_id', '=', $poiId)
            ->where('user_id', '=', $userId)
            ->count() > 0;
    }

    public function getUserFavorites(int $userId): array
    {
        return Favorite::query()
            ->where('user_id', '=', $userId)
            ->sortBy('created_at', 'DESC')
            ->get();
    }

    public function getFavoriteCount(int $poiId): int
    {
        return Favorite::query()
            ->where('poi_id', '=', $poiId)
            ->count();
    }
}

