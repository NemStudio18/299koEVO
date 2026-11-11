<?php

namespace SearchEngine\Services\Search\Stats;

use PDO;

defined('ROOT') or exit('Access denied!');

/**
 * Service d'agrégation de statistiques
 * 
 * Gère la récupération optimisée de tous les POIs de l'agglomération
 * Extrait de SimpleSearchService
 */
class StatsAggregator
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère tous les POIs de l'agglomération de manière optimisée avec une seule requête SQL
     * Utilise les index de la BDD pour de meilleures performances
     *
     * @param array $aggloCities Liste des communes de l'agglomération
     * @return array POIs groupés par catégorie/sous-catégorie
     */
    public function getAllPoisForAggloOptimized(array $aggloCities): array
    {
        if (empty($aggloCities)) {
            return [];
        }

        try {
            // Construire la clause IN pour les villes
            $placeholders = implode(',', array_fill(0, count($aggloCities), '?'));

            // OPTIMISATION : Requête unique avec GROUP BY pour agréger directement en SQL
            // Récupérer TOUS les POIs de l'agglomération avec leurs catégories/sous-catégories
            // On trie par catégorie pour optimiser le groupement PHP
            $sql = "
                SELECT 
                    ps.id,
                    ps.name,
                    ps.lat,
                    ps.lon,
                    ps.address,
                    ps.addr_city,
                    ps.phone,
                    ps.website,
                    ps.category,
                    ps.subcategory,
                    ps.is_premium,
                    ps.is_verified,
                    ps.rating,
                    ps.review_count
                FROM poi_public ps
                WHERE ps.status = 'public'
                  AND (ps.is_closed = 0 OR ps.is_closed IS NULL)
                  AND ps.addr_city IN ($placeholders)
                ORDER BY 
                    ps.category,
                    ps.subcategory
            ";

            error_log("SearchEVO: StatsAggregator::getAllPoisForAggloOptimized - SQL query completed");
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($aggloCities);
            $pois = $stmt->fetchAll();
            error_log("SearchEVO: StatsAggregator::getAllPoisForAggloOptimized - Found " . count($pois) . " POIs in total");

            if (empty($pois)) {
                return [];
            }

            // Grouper par catégorie et sous-catégorie
            $grouped = [];
            foreach ($pois as $poi) {
                // Utiliser category/subcategory qui sont déjà synchronisés (Editorial > OSM)
                $category = $poi['category'] ?: 'other';
                $subcategory = $poi['subcategory'] ?: 'other';

                if (!isset($grouped[$category])) {
                    $grouped[$category] = [];
                }
                if (!isset($grouped[$category][$subcategory])) {
                    $grouped[$category][$subcategory] = [
                        'results' => [],
                        'total' => 0,
                    ];
                }

                // Formater le POI
                $formattedPoi = [
                    'id' => (int) $poi['poi_id'],
                    'name' => $poi['name'],
                    'lat' => (float) $poi['lat'],
                    'lon' => (float) $poi['lon'],
                    'address' => $poi['address'],
                    'city' => $poi['city'],
                    'phone' => $poi['phone'],
                    'website' => $poi['website'],
                    'opening_hours' => $poi['opening_hours'],
                    'is_premium' => (bool) $poi['is_premium'],
                    'is_verified' => (bool) $poi['is_verified'],
                    'rating' => $poi['rating'] ? (float) $poi['rating'] : null,
                    'review_count' => $poi['review_count'] ? (int) $poi['review_count'] : 0,
                ];

                $grouped[$category][$subcategory]['results'][] = $formattedPoi;
                $grouped[$category][$subcategory]['total']++;
            }

            return [
                'categories_by_type' => $grouped,
                'total_pois' => count($pois),
            ];
        } catch (\Exception $e) {
            error_log("StatsAggregator: Erreur lors de la récupération des POIs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Compte le nombre total de POIs dans l'agglomération
     *
     * @param array $aggloCities Liste des communes de l'agglomération
     * @return int Nombre total de POIs
     */
    public function countTotalPois(array $aggloCities): int
    {
        if (empty($aggloCities)) {
            return 0;
        }

        try {
            $placeholders = implode(',', array_fill(0, count($aggloCities), '?'));

            $sql = "
                SELECT COUNT(DISTINCT ps.id) as total
                FROM poi_public ps
                WHERE ps.status = 'public'
                  AND (ps.is_closed = 0 OR ps.is_closed IS NULL)
                  AND ps.addr_city IN ($placeholders)
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($aggloCities);
            $result = $stmt->fetch();
            $total = (int) ($result['total'] ?? 0);
            error_log("SearchEVO: StatsAggregator::countTotalPois - Total POIs in agglo: $total");

            return $total;
        } catch (\Exception $e) {
            error_log("StatsAggregator: Erreur lors du comptage: " . $e->getMessage());
            return 0;
        }
    }
}
