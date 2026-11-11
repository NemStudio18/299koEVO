<?php

namespace QrPoi\Services;

use PDO;
use Core\Logger;

defined('ROOT') or exit('Access denied!');

/**
 * Service pour gérer les préférences d'affichage des QR codes (table SQLite)
 */
class QrDisplayService
{
    private ?PDO $sqlitePdo = null;
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Obtient la connexion SQLite
     */
    private function getConnection(): PDO
    {
        if ($this->sqlitePdo === null) {
            $dbPath = DATA_PLUGIN . 'QrPoi' . DS . 'qr_display_settings.sqlite3';
            $dbDir = dirname($dbPath);
            
            if (!is_dir($dbDir)) {
                @mkdir($dbDir, 0755, true);
            }
            
            $this->sqlitePdo = new PDO('sqlite:' . $dbPath);
            $this->sqlitePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->ensureTableExists();
        }
        
        return $this->sqlitePdo;
    }

    /**
     * Crée la table si elle n'existe pas
     */
    private function ensureTableExists(): void
    {
        try {
            $pdo = $this->sqlitePdo;
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS qr_display_settings (
                    poi_id INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    show_qr_code INTEGER DEFAULT 0,
                    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (poi_id, user_id)
                )
            ");
            
            // Créer un index pour améliorer les performances
            $pdo->exec("
                CREATE INDEX IF NOT EXISTS idx_poi_id ON qr_display_settings(poi_id)
            ");
        } catch (\Exception $e) {
            $this->logger->log(Logger::LEVEL_ERROR, "QrDisplayService::ensureTableExists - Erreur: " . $e->getMessage());
        }
    }

    /**
     * Vérifie si l'affichage du QR code est activé pour un POI et un utilisateur
     */
    public function isQrCodeDisplayEnabled(int $poiId, int $userId): bool
    {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->prepare("SELECT show_qr_code FROM qr_display_settings WHERE poi_id = ? AND user_id = ?");
            $stmt->execute([$poiId, $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result !== false && (int)$result['show_qr_code'] === 1;
        } catch (\Exception $e) {
            $this->logger->log(Logger::LEVEL_WARNING, "QrDisplayService::isQrCodeDisplayEnabled - Erreur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si l'affichage du QR code est activé pour un POI (au moins un utilisateur l'a activé)
     */
    public function isAnyQrCodeDisplayEnabled(int $poiId): bool
    {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM qr_display_settings WHERE poi_id = ? AND show_qr_code = 1");
            $stmt->execute([$poiId]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (\Exception $e) {
            $this->logger->log(Logger::LEVEL_WARNING, "QrDisplayService::isAnyQrCodeDisplayEnabled - Erreur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Active ou désactive l'affichage du QR code pour un POI et un utilisateur
     */
    public function toggleQrCodeDisplay(int $poiId, int $userId, bool $enabled): bool
    {
        try {
            $pdo = $this->getConnection();
            $showQrCode = $enabled ? 1 : 0;
            
            $stmt = $pdo->prepare("
                INSERT OR REPLACE INTO qr_display_settings (poi_id, user_id, show_qr_code, updated_at)
                VALUES (?, ?, ?, datetime('now'))
            ");
            
            return $stmt->execute([$poiId, $userId, $showQrCode]);
        } catch (\Exception $e) {
            $this->logger->log(Logger::LEVEL_ERROR, "QrDisplayService::toggleQrCodeDisplay - Erreur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère l'état d'affichage du QR code pour un POI (pour un utilisateur spécifique)
     */
    public function getQrCodeDisplayStatus(int $poiId, int $userId): bool
    {
        return $this->isQrCodeDisplayEnabled($poiId, $userId);
    }

    /**
     * Récupère tous les POI avec leur statut d'affichage pour un utilisateur
     */
    public function getUserPoisWithDisplayStatus(int $userId): array
    {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->prepare("SELECT poi_id, show_qr_code FROM qr_display_settings WHERE user_id = ?");
            $stmt->execute([$userId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $statusMap = [];
            foreach ($results as $row) {
                $statusMap[(int)$row['poi_id']] = (int)$row['show_qr_code'] === 1;
            }
            
            return $statusMap;
        } catch (\Exception $e) {
            $this->logger->log(Logger::LEVEL_WARNING, "QrDisplayService::getUserPoisWithDisplayStatus - Erreur: " . $e->getMessage());
            return [];
        }
    }
}


