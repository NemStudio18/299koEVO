-- Migration : Créer une table pour suivre les activités des propriétaires (changements de logo, uploads de photos)
-- Permet de limiter les changements de logo (2 par semaine) et les uploads de photos (6 par semaine)

CREATE TABLE IF NOT EXISTS `poi_owner_activity_log` (
    `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
    `poi_id` BIGINT(20) NOT NULL,
    `owner_id` BIGINT(20) NOT NULL,
    `activity_type` ENUM('logo_change', 'photo_upload') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_poi_owner` (`poi_id`, `owner_id`),
    INDEX `idx_activity_type` (`activity_type`),
    INDEX `idx_created_at` (`created_at`),
    CONSTRAINT `fk_activity_log_poi` FOREIGN KEY (`poi_id`) REFERENCES `poi_public` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_activity_log_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




