-- Script pour corriger la table poi_category_override
-- Supprime la table si elle existe avec une mauvaise structure et la recrée

DROP TABLE IF EXISTS poi_category_override;

CREATE TABLE poi_category_override (
    poi_id BIGINT(20) NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    subcategory_id INT UNSIGNED DEFAULT NULL,
    source ENUM('admin','import','auto') NOT NULL DEFAULT 'admin',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (poi_id),
    KEY idx_category_id (category_id),
    KEY idx_subcategory_id (subcategory_id),
    CONSTRAINT fk_override_poi FOREIGN KEY (poi_id) REFERENCES poi_public(id) ON DELETE CASCADE,
    CONSTRAINT fk_override_category FOREIGN KEY (category_id) REFERENCES poi_categories(id),
    CONSTRAINT fk_override_subcategory FOREIGN KEY (subcategory_id) REFERENCES poi_subcategories_editorial(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

