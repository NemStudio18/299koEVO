-- Migration pour ajouter les colonnes note et proof_path à poi_claims
-- Exécuter cette requête sur la base de données vivrebourges

ALTER TABLE `poi_claims`
ADD COLUMN `note` TEXT NULL DEFAULT NULL AFTER `status`,
ADD COLUMN `proof_path` TEXT NULL DEFAULT NULL AFTER `note`;










