<?php
namespace BusinessDashboard;

use Core\Plugin\Plugin;
use Core\Core;
use Core\Database;

class BusinessDashboard extends Plugin {

    public static function install() {
        $db = Database::getInstance();
        
        // Create Analytics Table
        $sql = "CREATE TABLE IF NOT EXISTS poi_analytics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            poi_id INT NOT NULL,
            event_type VARCHAR(50) NOT NULL,
            user_id INT NULL,
            metadata TEXT NULL, -- JSON storage
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_poi_id (poi_id),
            INDEX idx_event_type (event_type),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        try {
            $db->query($sql);
            return true;
        } catch (\Exception $e) {
            error_log("BusinessDashboard Install Error: " . $e->getMessage());
            return false;
        }
    }

    public static function onBeforeRun() {
        // Routes are now in param/routes.php
    }

    public static function addToAvatarMenu($args) {
        $user = $args['user'] ?? null;
        if (!$user) {
            return $args;
        }

        // Check if user has validated claims (is a Pro)
        $currentUser = \Core\Auth\CurrentUser::get();
        $hasClaims = $currentUser->hasClaims();

        if ($hasClaims) {
            $args['items'][] = [
                'icon' => 'fa-solid fa-store',
                'label' => 'Espace Pro',
                'url' => '/BusinessDashboard',
                'order' => 70
            ];
        }
        
        return $args;
    }
}
?>
