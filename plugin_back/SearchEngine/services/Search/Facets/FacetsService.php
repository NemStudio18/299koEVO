<?php

namespace SearchEngine\Services\Search\Facets;

use PDO;

defined('ROOT') or exit('Access denied!');

/**
 * Service de génération de facettes pour le filtrage
 * 
 * Génère les facettes (filtres) basées sur les POIs :
 * - Activités/Catégories
 * - Villes
 * - Ratings (étoiles)
 * 
 * Fusionne les implémentations de Simple et Enhanced SearchService
 */
class FacetsService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère les facettes pour une liste de POIs
     * 
     * @param array $poiIds Liste des IDs de POIs
     * @param string|null $filterType Filtre type optionnel (non utilisé actuellement)
     * @return array ['activities' => array, 'cities' => array, 'ratings' => array]
     */
    public function getFacets(array $poiIds, ?string $filterType = null): array
    {
        if (empty($poiIds)) {
            return [
                'activities' => [],
                'cities' => [],
                'ratings' => []
            ];
        }

        $facets = [
            'activities' => [],
            'cities' => [],
            'ratings' => []
        ];

        $placeholders = implode(',', array_fill(0, count($poiIds), '?'));

        try {
            // Facettes activités (uniquement par category, pas par subcategory)
            $sql = "SELECT 
                p.category as activity_key,
                COUNT(*) as count
            FROM poi_public p
            WHERE p.id IN ({$placeholders})
              AND p.category IS NOT NULL 
              AND p.category != ''
            GROUP BY p.category
            ORDER BY count DESC
            LIMIT 20";

            error_log("SearchEVO: FacetsService::getFacets - Fetching activities for " . count($poiIds) . " POIs");
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($poiIds);
            $activities = $stmt->fetchAll();
            error_log("SearchEVO: FacetsService::getFacets - Found " . count($activities) . " activity facets");

            foreach ($activities as $row) {
                $facets['activities'][] = [
                    'key' => $row['activity_key'],
                    'count' => (int) $row['count']
                ];
            }

            // Facettes villes
            $sql = "SELECT 
                p.addr_city as city_name,
                COUNT(*) as count
            FROM poi_public p
            WHERE p.id IN ({$placeholders}) 
              AND p.addr_city IS NOT NULL 
              AND p.addr_city != ''
            GROUP BY p.addr_city
            ORDER BY count DESC
            LIMIT 20";

            error_log("SearchEVO: FacetsService::getFacets - Fetching cities");
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($poiIds);
            $cities = $stmt->fetchAll();
            error_log("SearchEVO: FacetsService::getFacets - Found " . count($cities) . " city facets");

            foreach ($cities as $row) {
                $facets['cities'][] = [
                    'name' => $row['city_name'],
                    'count' => (int) $row['count']
                ];
            }

            // Facettes notes (stars)
            $sql = "SELECT 
                CAST(pt.value_text AS UNSIGNED) as stars,
                COUNT(*) as count
            FROM poi_public p
            JOIN poi_tags pt ON pt.poi_id = p.id
            WHERE p.id IN ({$placeholders}) 
              AND pt.key_name = 'stars'
              AND pt.value_text REGEXP '^[0-9]+$'
            GROUP BY stars
            ORDER BY stars DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($poiIds);
            $ratings = $stmt->fetchAll();

            foreach ($ratings as $row) {
                $facets['ratings'][] = [
                    'stars' => (int) $row['stars'],
                    'count' => (int) $row['count']
                ];
            }
        } catch (\PDOException $e) {
            error_log("FacetsService::getFacets - Erreur: " . $e->getMessage());
            // Retourner des facettes vides en cas d'erreur
        }

        return $facets;
    }

    /**
     * Récupère les facettes avec optimisations (bounding box, index)
     * Version Enhanced avec scoring
     * 
     * @param array $poiIds Liste des IDs de POIs
     * @return array Facettes optimisées
     */
    public function getFacetsOptimized(array $poiIds): array
    {
        // Pour l'instant, utiliser la même implémentation
        // TODO: Ajouter les optimisations de EnhancedSqlSearchService si nécessaire
        return $this->getFacets($poiIds);
    }
}
