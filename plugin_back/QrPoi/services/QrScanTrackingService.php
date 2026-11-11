<?php

namespace QrPoi\Services;

use PDO;
use Core\Logger;

defined('ROOT') or exit('Access denied!');

/**
 * Service pour tracker les scans de QR codes
 */
class QrScanTrackingService
{
    private PDO $db;
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $dbPath = DATA_PLUGIN . 'QrPoi/qr_scans.sqlite3';
        $dbDir = dirname($dbPath);

        if (!is_dir($dbDir)) {
            @mkdir($dbDir, 0755, true);
        }

        $this->db = new PDO('sqlite:' . $dbPath);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->ensureTableExists();
    }

    private function ensureTableExists(): void
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS qr_scans (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                poi_id INTEGER NOT NULL,
                scanned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                ip_address TEXT,
                user_agent TEXT,
                referer TEXT
            )");
            $this->db->exec("CREATE INDEX IF NOT EXISTS idx_qr_scans_poi ON qr_scans(poi_id)");
            $this->db->exec("CREATE INDEX IF NOT EXISTS idx_qr_scans_date ON qr_scans(scanned_at)");
        } catch (\Exception $e) {
            $this->logger->error("QrScanTrackingService::ensureTableExists - Erreur: " . $e->getMessage());
        }
    }

    /**
     * Enregistre un scan de QR code
     */
    public function trackScan(int $poiId, ?string $ipAddress = null, ?string $userAgent = null, ?string $referer = null): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO qr_scans (poi_id, scanned_at, ip_address, user_agent, referer)
                VALUES (?, datetime('now'), ?, ?, ?)
            ");
            return $stmt->execute([
                $poiId,
                $ipAddress ?? $_SERVER['REMOTE_ADDR'] ?? null,
                $userAgent ?? $_SERVER['HTTP_USER_AGENT'] ?? null,
                $referer ?? $_SERVER['HTTP_REFERER'] ?? null
            ]);
        } catch (\Exception $e) {
            $this->logger->error("QrScanTrackingService::trackScan - Erreur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère le nombre total de scans pour un POI
     */
    public function getScanCountForPoi(int $poiId): int
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM qr_scans WHERE poi_id = ?");
            $stmt->execute([$poiId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0);
        } catch (\Exception $e) {
            $this->logger->error("QrScanTrackingService::getScanCountForPoi - Erreur: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère le nombre total de scans
     */
    public function getTotalScans(): int
    {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM qr_scans");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0);
        } catch (\Exception $e) {
            $this->logger->error("QrScanTrackingService::getTotalScans - Erreur: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère le nombre de POI avec QR codes
     */
    public function getPoisWithQrCodes(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT poi_id, COUNT(*) as scan_count, 
                       MIN(scanned_at) as first_scan,
                       MAX(scanned_at) as last_scan
                FROM qr_scans
                GROUP BY poi_id
                ORDER BY scan_count DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->logger->error("QrScanTrackingService::getPoisWithQrCodes - Erreur: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les scans récents (derniers N scans)
     */
    public function getRecentScans(int $limit = 50): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM qr_scans
                ORDER BY scanned_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->logger->error("QrScanTrackingService::getRecentScans - Erreur: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les statistiques par période
     */
    public function getStatsByPeriod(string $period = 'day'): array
    {
        try {
            $format = match($period) {
                'day' => '%Y-%m-%d',
                'week' => '%Y-W%W',
                'month' => '%Y-%m',
                default => '%Y-%m-%d'
            };
            
            $stmt = $this->db->query("
                SELECT 
                    strftime('{$format}', scanned_at) as period,
                    COUNT(*) as scan_count,
                    COUNT(DISTINCT poi_id) as unique_pois
                FROM qr_scans
                GROUP BY period
                ORDER BY period DESC
                LIMIT 30
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->logger->error("QrScanTrackingService::getStatsByPeriod - Erreur: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les scans pour un POI spécifique
     */
    public function getScansForPoi(int $poiId, int $limit = 100): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM qr_scans
                WHERE poi_id = ?
                ORDER BY scanned_at DESC
                LIMIT ?
            ");
            $stmt->execute([$poiId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->logger->error("QrScanTrackingService::getScansForPoi - Erreur: " . $e->getMessage());
            return [];
        }
    }
}


