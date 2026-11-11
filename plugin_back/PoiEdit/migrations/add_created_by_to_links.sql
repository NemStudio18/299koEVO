-- Migration : Ajouter la colonne created_by à poi_links pour suivre qui a ajouté chaque lien
-- Cela permet de ne compter que les liens ajoutés par le propriétaire actuel pour les limitations

ALTER TABLE poi_links
ADD COLUMN `created_by` BIGINT(20) NULL DEFAULT NULL AFTER `label`,
ADD INDEX `idx_poi_links_created_by` (`created_by`),
ADD CONSTRAINT `fk_poi_links_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;




