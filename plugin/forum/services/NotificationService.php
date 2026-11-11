<?php

namespace Forum\Services;

defined('ROOT') or exit('Access denied!');

/**
 * Service pour gérer les notifications du forum
 */
class NotificationService
{
    /**
     * Compte les notifications non lues pour un utilisateur
     */
    public function getUnreadCount(int $userId): int
    {
        return \Forum\Entities\ForumNotification::query()
            ->where('user_id', '=', $userId)
            ->where('is_read', '=', 0)
            ->count();
    }
}
