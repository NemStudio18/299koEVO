<?php

namespace PoiFavorites\Services;

use PoiFavorites\Entities\NotificationManager;

defined('ROOT') or exit('Access denied!');

class NotificationService
{
    private NotificationManager $notificationManager;

    public function __construct()
    {
        $this->notificationManager = new NotificationManager();
    }

    public function notifyPoiSubscribers(int $poiId, string $type, ?array $meta = null): bool
    {
        try {
            $subscribers = $this->notificationManager->getSubscribers($poiId);
            if (empty($subscribers)) {
                return true;
            }

            $userIds = array_map(function($row) { return (int)$row['user_id']; }, $subscribers);
            
            // Préparer les données pour le hook centralisé
            // On délègue le chunking au NotificationService centralisé qui supporte déjà array de user_id
            
            \Core\Core::getInstance()->callHook('notifications:send', [
                'user_id' => $userIds,
                'type' => 'poi.' . $type,
                'source_plugin' => 'poi_favorites',
                'data' => [
                    'key' => 'poi.' . $type,
                    'params' => [
                        'poi_id' => $poiId,
                        'poi_name' => $meta['poi_name'] ?? 'POI #' . $poiId
                    ],
                    'url' => rtrim(\Core\Core::getInstance()->getConfigVal('siteUrl'), '/') . '/poi/' . $poiId, // Fallback URL
                    'entity' => [
                        'type' => 'poi',
                        'id' => $poiId
                    ]
                ],
                'external_id' => 'poi:' . $type . ':' . $poiId . ':' . date('Y-m-d-H') // Idempotent sur l'heure
            ]);

            return true;
        } catch (\Exception $e) {
            error_log('NotificationService::notifyPoiSubscribers - Erreur: ' . $e->getMessage());
            return false;
        }
    }
}



