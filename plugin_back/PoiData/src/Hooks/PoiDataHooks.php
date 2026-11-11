<?php

namespace PoiData\Hooks;

use Core\Core;
use Core\Logger;

defined('ROOT') or exit('Access denied!');

class PoiDataHooks
{
    public static function registerHooks(): void
    {
        // Hooks are now declared in param/hooks.json
    }

    public static function handleActivity($data): void
    {
        if (!empty($data['poi_id'])) {
            try {
                $db = \Core\Database::getInstance()->getPdo();
                $stmt = $db->prepare('UPDATE poi_public SET last_activity_at = NOW() WHERE id = ? AND id > 0');
                $stmt->execute([(int)$data['poi_id']]);
            } catch (\Exception $e) {
                Logger::getInstance()->log(Logger::LEVEL_ERROR, "PoiData: Failed to update last_activity_at for POI " . $data['poi_id']);
            }
        }
    }

    public static function handleLoadAgglo(): array
    {
        $service = new \PoiData\Services\CommuneService();
        return $service->loadAggloCommunes();
    }

    public static function handleGetCentroids($data): array
    {
        $service = new \PoiData\Services\CommuneService();
        return $service->getCommuneCentroids($data['config'] ?? [], $data['commune_names'] ?? []);
    }

    public static function handleProviderRequest($data)
    {
        if (isset($data['entity']) && $data['entity'] === 'poi') {
            // Lazy loading of the service
            if (!class_exists('\PoiData\Services\PoiCapabilityProvider')) {
                require_once dirname(__DIR__, 2) . '/services/PoiCapabilityProvider.php';
            }
            return new \PoiData\Services\PoiCapabilityProvider();
        }
        return null;
    }

    public static function install(): void
    {
        try {
            $pdo = \Core\Database::getInstance()->getPdo();
            $pdo->exec("CREATE TABLE IF NOT EXISTS poi (id INTEGER PRIMARY KEY, status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE', publication_status VARCHAR(20) NOT NULL DEFAULT 'PUBLISHED');");
            $pdo->exec("CREATE TRIGGER IF NOT EXISTS trg_poi_public_sync AFTER INSERT ON poi_public BEGIN INSERT OR IGNORE INTO poi (id, status, publication_status) VALUES (new.id, CASE WHEN new.status = 'published' THEN 'ACTIVE' ELSE 'HIDDEN' END, CASE WHEN new.status = 'published' THEN 'PUBLISHED' ELSE 'DRAFT' END); END;");
            $pdo->exec("CREATE TABLE IF NOT EXISTS poi_public (
                id INTEGER PRIMARY KEY,
                name VARCHAR(255),
                type VARCHAR(50),
                category VARCHAR(50),
                subcategory VARCHAR(50),
                brand_id INTEGER,
                lat REAL,
                lon REAL,
                addr_street VARCHAR(255),
                addr_housenumber VARCHAR(20),
                addr_postcode VARCHAR(10),
                addr_city VARCHAR(100),
                datascore INTEGER DEFAULT 50,
                status VARCHAR(20) DEFAULT 'published',
                is_closed TINYINT(1) DEFAULT 0,
                closed_at DATETIME,
                closed_reason TEXT,
                source VARCHAR(50),
                claimed_by INTEGER,
                claimed_at DATETIME,
                status_claim VARCHAR(20),
                stars INTEGER DEFAULT 0,
                desc_list TEXT,
                last_activity_at DATETIME,
                opening_hours TEXT,
                created_at DATETIME,
                updated_at DATETIME
            )");
            $pdo->exec("CREATE TABLE IF NOT EXISTS poi_search (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                poi_id INTEGER,
                name VARCHAR(255),
                type VARCHAR(50),
                category VARCHAR(50),
                subcategory VARCHAR(50),
                lat REAL,
                lon REAL,
                addr_street VARCHAR(255),
                addr_postcode VARCHAR(10),
                addr_city VARCHAR(100),
                status VARCHAR(20) DEFAULT 'published',
                is_closed TINYINT(1) DEFAULT 0
            )");
            $pdo->exec("CREATE TABLE IF NOT EXISTS poi_tags (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                poi_id INTEGER,
                key_name VARCHAR(100),
                value_text TEXT,
                created_by INTEGER,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            $pdo->exec("CREATE TABLE IF NOT EXISTS poi_proposals (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255),
                category VARCHAR(50),
                street VARCHAR(255),
                housenumber VARCHAR(20),
                postcode VARCHAR(10),
                city VARCHAR(100),
                phone VARCHAR(30),
                website VARCHAR(255),
                opening_hours TEXT,
                wheelchair VARCHAR(20),
                lat REAL,
                lon REAL,
                is_owner TINYINT(1) DEFAULT 0,
                owner_proof TEXT,
                note TEXT,
                user_id INTEGER,
                reviewed_by INTEGER,
                status VARCHAR(20) DEFAULT 'pending',
                created_at DATETIME,
                updated_at DATETIME
            )");
            $pdo->exec("CREATE TABLE IF NOT EXISTS poi_suggestions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                poi_id INTEGER,
                user_id INTEGER,
                reviewed_by INTEGER,
                field_name VARCHAR(50),
                old_value TEXT,
                new_value TEXT,
                comment TEXT,
                status VARCHAR(20) DEFAULT 'pending',
                created_at DATETIME,
                updated_at DATETIME
            )");
            $pdo->exec("CREATE TABLE IF NOT EXISTS commune_geometry (
                name VARCHAR(100) PRIMARY KEY,
                centroid_lat REAL,
                centroid_lon REAL,
                bounding_radius_km REAL
            )");
            $pdo->exec("CREATE TABLE IF NOT EXISTS poi_photos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                poi_id INTEGER,
                url TEXT,
                caption TEXT,
                uploaded_by INTEGER,
                status VARCHAR(20) DEFAULT 'approved',
                is_verified TINYINT(1) DEFAULT 0,
                is_cover TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            $pdo->exec("CREATE TABLE IF NOT EXISTS poi_opening_hours (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                poi_id INTEGER,
                day_of_week INTEGER,
                slot_index INTEGER DEFAULT 0,
                open_time VARCHAR(20),
                close_time VARCHAR(20),
                is_closed TINYINT(1) DEFAULT 0
            )");
        } catch (\Exception $e) {
            Logger::getInstance()->log(Logger::LEVEL_ERROR, "PoiData: Install table creation failed: " . $e->getMessage());
        }
    }

    public static function uninstall(): void
    {
        // Nettoyage éventuel
        Core::getInstance()->getLogger()->log(Logger::LEVEL_INFO, "PoiData: Uninstalled");
    }

    /**
     * Hook: core:stats-collector
     */
    public static function collectStats(array $stats): array
    {
        try {
            // Optimized version: Use COUNT() instead of fetching all objects
            $totalPois = \PoiData\Entities\Poi::query()->count();
            
            // Approximation for cities (LIMITATION: count() doesn't support params in this ORM version)
            $totalCities = \PoiData\Entities\Poi::query()->where('addr_city', '!=', '')->count(); 

            // Recent updates (last 30 days)
            $thirtyDaysAgo = date('Y-m-d H:i:s', strtotime('-30 days'));
            $recent = \PoiData\Entities\Poi::query()->where('updated_at', '>', $thirtyDaysAgo)->count();

            $stats['poi'] = [
                'total_pois' => $totalPois,
                'total_cities' => $totalCities,
                'recent_updates' => $recent
            ];
            $stats['counts']['pois'] = $totalPois;
            $stats['counts']['cities'] = $totalCities;
            $stats['counts']['recent_updates'] = $recent;

        } catch (\Exception $e) {
            // Ignorer
        }
        return $stats;
    }
}
