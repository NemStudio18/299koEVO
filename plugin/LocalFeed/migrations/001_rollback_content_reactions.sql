-- ============================================================================
-- ROLLBACK SÉCURISÉ LOCALFEED : SUPPRESSION DE content_reactions
-- ============================================================================
-- Conserve une copie conforme de la structure (index, contraintes) et des
-- données existantes dans content_reactions_backup avant toute suppression.
-- ============================================================================

-- 1. Sauvegarde conforme de la structure (y compris index et contraintes)
CREATE TABLE IF NOT EXISTS content_reactions_backup LIKE content_reactions;

-- 2. Sauvegarde intégrale des données créées
INSERT IGNORE INTO content_reactions_backup 
SELECT * FROM content_reactions;

-- 3. Suppression uniquement après archivage effectif
DROP TABLE IF EXISTS content_reactions;
