<?php

namespace QrPoi\Services;

use PDO;
use Core\Logger;

defined('ROOT') or exit('Access denied!');

/**
 * Service pour gérer les styles personnalisés des QR codes
 */
class QrStyleService
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
            $dbPath = DATA_PLUGIN . 'qr-poi' . DS . 'qr_styles.sqlite3';
            $this->sqlitePdo = \Core\Database::getInstance()->getSqlitePdo($dbPath);
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
                CREATE TABLE IF NOT EXISTS qr_styles (
                    poi_id INTEGER PRIMARY KEY,
                    user_id INTEGER NOT NULL,
                    color TEXT DEFAULT '000000',
                    bgcolor TEXT DEFAULT 'FFFFFF',
                    size INTEGER DEFAULT 300,
                    format TEXT DEFAULT 'png',
                    qzone INTEGER DEFAULT 1,
                    logo_path TEXT DEFAULT NULL,
                    custom_text TEXT DEFAULT NULL,
                    text_color TEXT DEFAULT '000000',
                    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
                )
            ");

            $pdo->exec("
                CREATE INDEX IF NOT EXISTS idx_poi_id ON qr_styles(poi_id)
            ");
        } catch (\Exception $e) {
            $this->logger->log(Logger::LEVEL_ERROR, "QrStyleService::ensureTableExists - Erreur: " . $e->getMessage());
        }
    }

    /**
     * Récupère le style personnalisé pour un POI
     */
    public function getStyle(int $poiId): ?array
    {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->prepare("SELECT * FROM qr_styles WHERE poi_id = ?");
            $stmt->execute([$poiId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;
        } catch (\Exception $e) {
            $this->logger->log(Logger::LEVEL_WARNING, "QrStyleService::getStyle - Erreur: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Sauvegarde un style personnalisé
     */
    public function saveStyle(int $poiId, int $userId, array $style): bool
    {
        try {
            $pdo = $this->getConnection();

            $color = $style['color'] ?? '000000';
            $bgcolor = $style['bgcolor'] ?? 'FFFFFF';
            $size = (int) ($style['size'] ?? 300);
            $format = $style['format'] ?? 'png';
            $qzone = (int) ($style['qzone'] ?? 1);
            $logoPath = $style['logo_path'] ?? null;
            $customText = $style['custom_text'] ?? null;
            $textColor = $style['text_color'] ?? '000000';

            // Validation
            $color = preg_replace('/[^0-9A-Fa-f]/', '', $color);
            $bgcolor = preg_replace('/[^0-9A-Fa-f]/', '', $bgcolor);
            $size = max(100, min(1000, $size));
            $qzone = max(0, min(10, $qzone));
            $textColor = preg_replace('/[^0-9A-Fa-f]/', '', $textColor);
            $format = in_array($format, ['png', 'svg']) ? $format : 'png';

            $stmt = $pdo->prepare("
                INSERT OR REPLACE INTO qr_styles 
                (poi_id, user_id, color, bgcolor, size, format, qzone, logo_path, custom_text, text_color, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
            ");

            return $stmt->execute([$poiId, $userId, $color, $bgcolor, $size, $format, $qzone, $logoPath, $customText, $textColor]);
        } catch (\Exception $e) {
            $this->logger->log(Logger::LEVEL_ERROR, "QrStyleService::saveStyle - Erreur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime le logo d'un POI
     */
    public function removeLogo(int $poiId): bool
    {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->prepare("UPDATE qr_styles SET logo_path = NULL, updated_at = datetime('now') WHERE poi_id = ?");
            return $stmt->execute([$poiId]);
        } catch (\Exception $e) {
            $this->logger->log(Logger::LEVEL_ERROR, "QrStyleService::removeLogo - Erreur: " . $e->getMessage());
            return false;
        }
    }
}
