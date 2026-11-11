-- Migration: 20261102_add_event_reconciliation.sql
-- Description: Ajout des identifiants event_id, request_id et colonne générée pour certification unique
-- Compatible: MySQL 8.0+ et MariaDB 10.3+ (STORED est supporté par les deux moteurs)

-- 1. Ajout des colonnes sans impacter les lignes existantes
ALTER TABLE `ls_events`
    ADD COLUMN `event_id` VARCHAR(64) NULL AFTER `id`,
    ADD COLUMN `request_id` VARCHAR(64) NULL AFTER `event_id`,
    ADD COLUMN `certification_request_id` VARCHAR(64)
        GENERATED ALWAYS AS (CASE WHEN `event_type` = 'view_certified' THEN `request_id` ELSE NULL END) STORED AFTER `request_id`;

-- 2. Index unique pour chaque événement (idempotence / anti-doublon beacon)
CREATE UNIQUE INDEX `uniq_event_id` ON `ls_events` (`event_id`);

-- 3. Index unique garantissant une seule certification par réponse HTTP (request_id)
CREATE UNIQUE INDEX `uniq_page_certification` ON `ls_events` (`certification_request_id`);

-- 4. Index standard pour le rapprochement rapide par request_id
CREATE INDEX `idx_events_request_id` ON `ls_events` (`request_id`);
