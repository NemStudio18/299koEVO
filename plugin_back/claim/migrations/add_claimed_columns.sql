-- Migration pour ajouter les colonnes de revendication à la table poi_public
-- Ces colonnes permettent de lier un POI à son propriétaire revendiqué
-- Exécuter cette requête sur la base de données vivrebourges
--
-- Note: Si la colonne existe déjà, cette requête échouera avec une erreur, c'est normal
-- Vous pouvez ignorer l'erreur ou vérifier d'abord avec: SHOW COLUMNS FROM poi_public LIKE 'claimed_by'

-- Ajouter les colonnes claimed_by, claimed_at et status_claim
-- status_claim est séparé de status car status gère la visibilité (public/privé)
ALTER TABLE poi_public 
ADD COLUMN `claimed_by` BIGINT NULL DEFAULT NULL AFTER `lon`,
ADD COLUMN `claimed_at` DATETIME NULL DEFAULT NULL AFTER `claimed_by`,
ADD COLUMN `status_claim` ENUM('unclaimed','claimed','rejected') DEFAULT 'unclaimed' AFTER `claimed_at`;

-- Ajouter des index pour améliorer les performances (si les index n'existent pas déjà)
ALTER TABLE poi_public 
ADD INDEX `idx_poi_public_claimed_by` (`claimed_by`),
ADD INDEX `idx_poi_public_status_claim` (`status_claim`);

