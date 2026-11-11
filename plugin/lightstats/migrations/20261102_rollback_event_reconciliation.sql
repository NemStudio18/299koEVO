-- Rollback: 20261102_rollback_event_reconciliation.sql
-- Description: Annulation des index et colonnes ajoutés pour la réconciliation d'événements

ALTER TABLE `ls_events`
    DROP INDEX `idx_events_request_id`,
    DROP INDEX `uniq_page_certification`,
    DROP INDEX `uniq_event_id`;

ALTER TABLE `ls_events`
    DROP COLUMN `certification_request_id`,
    DROP COLUMN `request_id`,
    DROP COLUMN `event_id`;
