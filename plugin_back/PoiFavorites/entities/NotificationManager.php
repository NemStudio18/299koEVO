<?php

namespace PoiFavorites\Entities;

defined('ROOT') or exit('Access denied!');

use Core\Database;

class NotificationManager
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(int $poiId, int $userId, string $type, ?string $data = null): bool
    {
        // userId = 0 signifie qu'on notifie tous les abonnés du POI
        // Sinon, on notifie un utilisateur spécifique
        if ($userId > 0) {
            $sql = "INSERT INTO poi_notifications (poi_id, user_id, notification_type, notification_data) 
                    VALUES (?, ?, ?, ?)";
            return $this->db->execute($sql, [$poiId, $userId, $type, $data]);
        } else {
            // Créer la notification pour tous les utilisateurs qui ont ce POI en favoris
            $sql = "INSERT INTO poi_notifications (poi_id, user_id, notification_type, notification_data) 
                    SELECT ?, user_id, ?, ? 
                    FROM poi_favorites 
                    WHERE poi_id = ?";
            return $this->db->execute($sql, [$poiId, $type, $data, $poiId]);
        }
    }

    public function getUserNotifications(int $userId, bool $onlyUnread = false): array
    {
        $sql = "SELECT * FROM poi_notifications WHERE user_id = ?";
        if ($onlyUnread) {
            $sql .= " AND is_read = 0";
        }
        $sql .= " ORDER BY created_at DESC";
        
        $notifications = $this->db->query($sql, [$userId]);
        
        // Log pour déboguer
        error_log("PoiFavorites\NotificationManager::getUserNotifications - User $userId, onlyUnread: " . ($onlyUnread ? 'OUI' : 'NON') . ", trouvé: " . count($notifications) . " notifications");
        foreach ($notifications as $notif) {
            error_log("PoiFavorites\NotificationManager::getUserNotifications - Notification ID: {$notif['id']}, POI: {$notif['poi_id']}, Type: {$notif['notification_type']}, is_read: {$notif['is_read']}, Created: {$notif['created_at']}");
        }
        
        return $notifications;
    }

    public function getUnreadCount(int $userId): int
    {
        $rows = $this->db->query(
            "SELECT COUNT(*) as count FROM poi_notifications WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
        $result = $rows[0] ?? [];
        $count = (int)($result['count'] ?? 0);
        // Log pour déboguer
        if ($count > 0) {
            error_log("PoiFavorites\NotificationManager::getUnreadCount - User $userId a $count notifications non lues");
        }
        return $count;
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        $sql = "UPDATE poi_notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
        return $this->db->execute($sql, [$notificationId, $userId]);
    }

    public function markAllAsRead(int $userId): bool
    {
        $sql = "UPDATE poi_notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
        return $this->db->execute($sql, [$userId]);
    }

    public function getSubscribers(int $poiId): array
    {
        $sql = "SELECT user_id FROM poi_favorites WHERE poi_id = ?";
        return $this->db->query($sql, [$poiId]);

    }
}

