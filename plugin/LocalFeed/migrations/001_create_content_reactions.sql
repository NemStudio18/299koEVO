-- ============================================================================
-- MIGRATION LOCALFEED : TABLE UNIVERSELLE DES RÉACTIONS (content_reactions)
-- ============================================================================
-- Remplace les micro-systèmes isolés (poi_post_likes, blog_likes) par un
-- référentiel unique multi-contenus (posts, events, offers, blog).
-- Source de vérité unique pour les réactions citoyennes.
-- ============================================================================

CREATE TABLE IF NOT EXISTS content_reactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,             -- Aligné rigoureusement sur users.id (BIGINT UNSIGNED)
    content_type VARCHAR(32) NOT NULL,            -- 'post', 'event', 'offer', 'blog'
    content_id BIGINT UNSIGNED NOT NULL,          -- Identifiant du contenu
    reaction_type VARCHAR(20) NOT NULL DEFAULT 'like', -- 'like', 'interest'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    -- Unicité stricte : une seule réaction d'un utilisateur par contenu donné
    UNIQUE KEY uk_user_content (user_id, content_type, content_id),

    -- Index de performance pour le calcul des compteurs par type de réaction
    INDEX idx_content_reactions (content_type, content_id, reaction_type),

    -- Index pour l'historique et le profil utilisateur
    INDEX idx_user_history (user_id, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
