<?php
namespace LocalFeed;

use Core\Plugin\Plugin;
use Core\Database;

// Autoloader PSR-4 pour le plugin LocalFeed
spl_autoload_register(function ($class) {
    if (strpos($class, 'LocalFeed\\') === 0) {
        $relative = substr($class, 10);
        if (strpos($relative, 'Controllers\\') === 0) {
            $path = str_replace('\\', '/', substr($relative, 12));
            $file = __DIR__ . '/controllers/' . $path . '.php';
        } elseif (strpos($relative, 'Services\\') === 0) {
            $path = str_replace('\\', '/', substr($relative, 9));
            $file = __DIR__ . '/src/Services/' . $path . '.php';
        } else {
            $path = str_replace('\\', '/', $relative);
            $file = __DIR__ . '/src/' . $path . '.php';
        }
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

class LocalFeed extends Plugin {

    public static function install() {
        $db = Database::getInstance();
        
        $sqlPosts = "CREATE TABLE IF NOT EXISTS poi_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            poi_id BIGINT UNSIGNED NOT NULL,
            author_user_id INT NOT NULL,
            post_type ENUM('news', 'special_offer', 'photo', 'event', 'alert') DEFAULT 'news',
            title VARCHAR(255) NULL,
            content TEXT NOT NULL,
            media_url VARCHAR(500) NULL,
            pinned TINYINT(1) DEFAULT 0,
            likes_count INT DEFAULT 0,
            shares_count INT DEFAULT 0,
            comments_count INT DEFAULT 0,
            status ENUM('published', 'draft', 'archived') DEFAULT 'published',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_poi_date (poi_id, created_at DESC),
            INDEX idx_feed_status (status, created_at DESC),
            FOREIGN KEY (poi_id) REFERENCES poi(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $sqlLikes = "CREATE TABLE IF NOT EXISTS poi_post_likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_post_user (post_id, user_id),
            INDEX idx_user (user_id),
            FOREIGN KEY (post_id) REFERENCES poi_posts(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $sqlShares = "CREATE TABLE IF NOT EXISTS poi_post_shares (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NULL,
            channel VARCHAR(50) DEFAULT 'native',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_post (post_id),
            FOREIGN KEY (post_id) REFERENCES poi_posts(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $sqlComments = "CREATE TABLE IF NOT EXISTS poi_post_comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            author_name VARCHAR(150) NULL,
            content TEXT NOT NULL,
            status ENUM('published', 'pending', 'hidden') NOT NULL DEFAULT 'published',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            INDEX idx_post_id (post_id),
            INDEX idx_user_id (user_id),
            FOREIGN KEY (post_id) REFERENCES poi_posts(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        try {
            $db->query($sqlPosts);
            $db->query($sqlLikes);
            $db->query($sqlShares);
            $db->query($sqlComments);
            return true;
        } catch (\Exception $e) {
            error_log("LocalFeed Install Error: " . $e->getMessage());
            return false;
        }
    }

    public static function onBeforeRun() {
        // Enregistre les routes
    }
}
