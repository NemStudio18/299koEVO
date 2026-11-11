<?php

namespace PoiData\Services;

use PDO;

defined('ROOT') or exit('Access denied!');

/**
 * Service pour gérer les migrations de base de données automatiques
 */
class DatabaseMigrationService
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Exécute toutes les migrations nécessaires
     */
    public function runMigrations(): void
    {
        $this->addPoiClosedFlag();
        $this->createReportsAndContributionsTables();
    }

    /**
     * Ajoute les colonnes is_closed aux tables poi_public et poi_search
     */
    private function addPoiClosedFlag(): void
    {
        try {
            // Vérifier et ajouter les colonnes à poi_public
            if (!$this->columnExists('poi_public', 'is_closed')) {
                $this->pdo->exec("
                    ALTER TABLE `poi_public` 
                    ADD COLUMN `is_closed` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Flag pour indiquer si le POI est fermé définitivement' AFTER `status`,
                    ADD COLUMN `closed_at` datetime DEFAULT NULL COMMENT 'Date de fermeture du POI' AFTER `is_closed`,
                    ADD COLUMN `closed_reason` text DEFAULT NULL COMMENT 'Raison de la fermeture' AFTER `closed_at`
                ");
            }

            // Créer les index pour poi_public
            if (!$this->indexExists('poi_public', 'idx_is_closed')) {
                $this->pdo->exec("ALTER TABLE `poi_public` ADD INDEX `idx_is_closed` (`is_closed`)");
            }
            if (!$this->indexExists('poi_public', 'idx_status_closed')) {
                $this->pdo->exec("ALTER TABLE `poi_public` ADD INDEX `idx_status_closed` (`status`, `is_closed`)");
            }

            // Vérifier et ajouter les colonnes à poi_search
            if (!$this->columnExists('poi_search', 'is_closed')) {
                $this->pdo->exec("
                    ALTER TABLE `poi_search` 
                    ADD COLUMN `is_closed` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Flag pour indiquer si le POI est fermé définitivement' AFTER `status`
                ");
            }

            // Créer les index pour poi_search
            if (!$this->indexExists('poi_search', 'idx_is_closed')) {
                $this->pdo->exec("ALTER TABLE `poi_search` ADD INDEX `idx_is_closed` (`is_closed`)");
            }
            if (!$this->indexExists('poi_search', 'idx_status_closed')) {
                $this->pdo->exec("ALTER TABLE `poi_search` ADD INDEX `idx_status_closed` (`status`, `is_closed`)");
            }
        } catch (\Exception $e) {
            error_log('Erreur lors de l\'ajout du flag is_closed: ' . $e->getMessage());
        }
    }

    /**
     * Crée les tables pour les signalements et contributions
     */
    private function createReportsAndContributionsTables(): void
    {
        try {
            // Table poi_reports
            if (!$this->tableExists('poi_reports')) {
                $this->pdo->exec("
                    CREATE TABLE `poi_reports` (
                        `id` bigint(20) NOT NULL AUTO_INCREMENT,
                        `poi_id` bigint(20) NOT NULL,
                        `user_id` bigint(20) NOT NULL,
                        `report_type` enum('closed','deleted','duplicate','other') NOT NULL DEFAULT 'closed',
                        `reason` text DEFAULT NULL,
                        `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
                        `reviewed_by` bigint(20) DEFAULT NULL,
                        `reviewed_at` datetime DEFAULT NULL,
                        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        KEY `idx_poi_id` (`poi_id`),
                        KEY `idx_user_id` (`user_id`),
                        KEY `idx_status` (`status`),
                        KEY `idx_poi_status` (`poi_id`,`status`),
                        CONSTRAINT `fk_reports_poi` FOREIGN KEY (`poi_id`) REFERENCES `poi_public` (`id`) ON DELETE CASCADE,
                        CONSTRAINT `fk_reports_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                        CONSTRAINT `fk_reports_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
                ");
            }

            // Table poi_contributions
            if (!$this->tableExists('poi_contributions')) {
                $this->pdo->exec("
                    CREATE TABLE `poi_contributions` (
                        `id` bigint(20) NOT NULL AUTO_INCREMENT,
                        `poi_id` bigint(20) NOT NULL,
                        `user_id` bigint(20) NOT NULL,
                        `contribution_type` enum('edit','suggestion','report','photo','description') NOT NULL DEFAULT 'edit',
                        `fields_modified` text DEFAULT NULL COMMENT 'JSON array des champs modifiés',
                        `fields_count` tinyint(3) unsigned DEFAULT 0 COMMENT 'Nombre de champs modifiés',
                        `contribution_score` smallint(5) unsigned DEFAULT 0 COMMENT 'Score de contribution calculé',
                        `status` enum('pending','approved','rejected','auto_approved') NOT NULL DEFAULT 'auto_approved',
                        `reviewed_by` bigint(20) DEFAULT NULL,
                        `reviewed_at` datetime DEFAULT NULL,
                        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        KEY `idx_poi_id` (`poi_id`),
                        KEY `idx_user_id` (`user_id`),
                        KEY `idx_status` (`status`),
                        KEY `idx_contribution_type` (`contribution_type`),
                        KEY `idx_user_poi` (`user_id`,`poi_id`),
                        KEY `idx_created_at` (`created_at`),
                        CONSTRAINT `fk_contributions_poi` FOREIGN KEY (`poi_id`) REFERENCES `poi_public` (`id`) ON DELETE CASCADE,
                        CONSTRAINT `fk_contributions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                        CONSTRAINT `fk_contributions_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
                ");
            }

            // Table user_contribution_stats
            if (!$this->tableExists('user_contribution_stats')) {
                $this->pdo->exec("
                    CREATE TABLE `user_contribution_stats` (
                        `user_id` bigint(20) NOT NULL,
                        `total_contributions` int(10) unsigned DEFAULT 0,
                        `total_score` int(10) unsigned DEFAULT 0,
                        `fields_edited_count` int(10) unsigned DEFAULT 0,
                        `suggestions_count` int(10) unsigned DEFAULT 0,
                        `reports_count` int(10) unsigned DEFAULT 0,
                        `photos_count` int(10) unsigned DEFAULT 0,
                        `descriptions_count` int(10) unsigned DEFAULT 0,
                        `last_contribution_at` datetime DEFAULT NULL,
                        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (`user_id`),
                        KEY `idx_total_score` (`total_score`),
                        KEY `idx_total_contributions` (`total_contributions`),
                        CONSTRAINT `fk_contribution_stats_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
                ");
            }
        } catch (\Exception $e) {
            error_log('Erreur lors de la création des tables de signalements/contributions: ' . $e->getMessage());
        }
    }

    /**
     * Vérifie si une colonne existe dans une table
     */
    private function columnExists(string $table, string $column): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND COLUMN_NAME = ?
            ");
            $stmt->execute([$table, $column]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Vérifie si un index existe dans une table
     */
    private function indexExists(string $table, string $index): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) 
                FROM INFORMATION_SCHEMA.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND INDEX_NAME = ?
            ");
            $stmt->execute([$table, $index]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Vérifie si une table existe
     */
    private function tableExists(string $table): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ?
            ");
            $stmt->execute([$table]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}

