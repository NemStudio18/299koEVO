<?php

namespace SearchEngine\Services\Search\Category;

use PDO;
use SearchEngine\Services\SearchLogger;

defined('ROOT') or exit('Access denied!');

/**
 * Service d'agrégation des catégories (Simplifié)
 * 
 * Utilise poi_search pour obtenir les comptes.
 * Pas de traitement complexe des données.
 */
class CategoryAggregationService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère les agrégations (catégories, sous-catégories, villes)
     * Filtre géographiquement en utilisant les centroids de commune_geometry
     */
    public function getAggregations(): array
    {
        try {
            // Charger les centroids des communes de l'agglo
            $communeService = new \PoiData\Services\CommuneService();
            $centroids = $communeService->loadAggloCentroids([]);

            if (empty($centroids)) {
                \SearchEngine\Services\SearchLogger::warning('CategoryAggregationService: Aucun centroid chargé, impossible de filtrer l\'agglo');
                return ['categories' => [], 'cities' => [], 'total' => 0];
            }
            \SearchEngine\Services\SearchLogger::debug('CategoryAggregationService: ' . count($centroids) . ' centroids chargés.');

            $dialect = \Core\Database::getInstance()->getDialect();

            // 1. Préparer les conditions géographiques pour l'agglo totale
            $geoConditions = [];
            $allSqlParams = [];
            $i = 0;
            foreach ($centroids as $communeName => $data) {
                $latParam = "lat_{$i}";
                $lonParam = "lon_{$i}";
                $radParam = "rad_{$i}";
                $distSql = $dialect->proximitySql('s.lat', 's.lon', ":{$latParam}", ":{$lonParam}");
                $geoConditions[] = "({$distSql}) <= :{$radParam}";
                $allSqlParams[":{$latParam}"] = $data['lat'];
                $allSqlParams[":{$lonParam}"] = $data['lon'];
                $allSqlParams[":{$radParam}"] = $data['radius_km'];
                $i++;
            }

            $whereAgglo = "s.status = 'public' AND s.is_closed = 0 AND s.name IS NOT NULL AND s.name != '' AND s.subcategory IS NOT NULL AND s.subcategory != '' AND s.subcategory != 'poi'";
            if (!empty($geoConditions)) {
                $whereAgglo .= " AND (" . implode(' OR ', $geoConditions) . ")";
            }

            // 2. Récupérer tous les POIs de l'agglo pour attribution géographique
            $sqlAllArr = "SELECT s.poi_id, s.lat, s.lon 
                         FROM poi_search s 
                         WHERE $whereAgglo";
            $stmtAll = $this->pdo->prepare($sqlAllArr);
            $stmtAll->execute($allSqlParams);
            $allPois = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

            $cityCounts = [];
            foreach ($allPois as $poi) {
                $pLat = (float) $poi['lat'];
                $pLon = (float) $poi['lon'];
                $bestCity = null;
                $minDist = 999999;

                foreach ($centroids as $cityName => $c) {
                    $dist = $this->calculateDistance($pLat, $pLon, $c['lat'], $c['lon']);
                    if ($dist < $minDist) {
                        $minDist = $dist;
                        $bestCity = $cityName;
                    }
                }
                
                // Si on est à plus de 15km de toute ville de l'agglo, on n'attribue pas (sécurité)
                if ($bestCity && $minDist > 15) {
                    $bestCity = null;
                }

                if ($bestCity) {
                    $cityCounts[$bestCity] = ($cityCounts[$bestCity] ?? 0) + 1;
                }
            }

            $citiesFormatted = [];
            foreach ($cityCounts as $name => $count) {
                $citiesFormatted[] = [
                    'name' => $name,
                    'count' => $count,
                    'count_formatted' => number_format($count, 0, ',', ' ')
                ];
            }
            usort($citiesFormatted, fn($a, $b) => $b['count'] <=> $a['count']);

            // 3. Total Unique POIs (Calcul exact)
            $sqlTotal = "SELECT COUNT(DISTINCT s.poi_id) FROM poi_search s WHERE $whereAgglo";
            $stmtTotal = $this->pdo->prepare($sqlTotal);
            $stmtTotal->execute($allSqlParams);
            $total = (int) $stmtTotal->fetchColumn();

            // 4. Catégories (Regroupement)
            // Note: On utilise LEFT JOIN pour ne pas perdre les POIs qui n'ont pas de correspondance dans poi_public (même si s est dérivé de p)
            $sqlCats = "SELECT p.category, p.subcategory, COUNT(DISTINCT s.poi_id) as count 
                        FROM poi_search s 
                        LEFT JOIN poi_public p ON s.poi_id = p.id
                        WHERE $whereAgglo
                        GROUP BY p.category, p.subcategory";

            $stmtCats = $this->pdo->prepare($sqlCats);
            $stmtCats->execute($allSqlParams);
            $rawCats = $stmtCats->fetchAll(PDO::FETCH_ASSOC);

            // Normalisation par Familles
            $mappingService = new CategoryMappingService();
            $families = $mappingService->getFamilies();
            \Core\Lang::loadLanguageFile(PLUGINS . 'SearchUi' . DS . 'langs' . DS);

            $categories = [];
            foreach ($families as $key => $family) {
                $categories[$key] = array_merge($family, [
                    'label' => \Core\Lang::get($family['label']),
                    'count' => 0,
                    'subcategories' => []
                ]);
            }

            // S'assurer que 'other' existe si on doit y mettre des trucs
            if (!isset($categories['other'])) {
                $categories['other'] = [
                    'key' => 'other',
                    'label' => \Core\Lang::get('Other') ?: 'Autres',
                    'count' => 0,
                    'subcategories' => [],
                    'icon' => 'tag'
                ];
            }

            foreach ($rawCats as $row) {
                $osmCat = $row['category'] ?? '';
                $osmSub = $row['subcategory'] ?? '';
                $cnt = (int) $row['count'];

                $familyData = $mappingService->mapToFamily($osmCat, $osmSub);
                $familyKey = $familyData['key'];

                if (isset($categories[$familyKey])) {
                    $categories[$familyKey]['count'] += $cnt;
                } else {
                    $categories['other']['count'] += $cnt;
                }
            }

            // Filtrer les familles vides
            $categories = array_filter($categories, fn($cat) => $cat['count'] > 0);
            uasort($categories, fn($a, $b) => $b['count'] <=> $a['count']);

            \SearchEngine\Services\SearchLogger::debug("CategoryAggregationService: Fin getAggregations, total=$total, villes=" . count($citiesFormatted));

            return [
                'categories' => $categories,
                'cities' => $citiesFormatted,
                'total' => $total
            ];

        } catch (\Exception $e) {
            SearchLogger::error("CategoryAggregationService error: " . $e->getMessage());
            return ['categories' => [], 'cities' => [], 'total' => 0];
        }
    }

    /**
     * Récupère les catégories filtrées par une ville spécifique
     * Utilise STRICTEMENT le filtrage géographique via commune_geometry
     */
    public function getCategoriesByCity(string $cityName): array
    {
        if (empty($cityName)) {
            return $this->getAggregations();
        }

        try {
            // Récupérer les données géométriques de la commune
            $communeService = new \PoiData\Services\CommuneService();
            $centroids = $communeService->getCommuneCentroids([], [$cityName]);

            if (empty($centroids) || !isset($centroids[$cityName])) {
                \SearchEngine\Services\SearchLogger::warning("CategoryAggregationService: Pas de centroid pour {$cityName}");
                return ['categories' => []];
            }

            $centroid = $centroids[$cityName];
            $sqlParams = [];
            $where = "WHERE s.status = 'public' AND s.is_closed = 0 AND p.category IS NOT NULL AND p.category != '' AND s.subcategory IS NOT NULL AND s.subcategory != '' AND s.subcategory != 'poi'";

            // Filtrage géographique strict (on utilise impérativement les coordonnées de poi_search s)
            $dialect = \Core\Database::getInstance()->getDialect();
            $distSql = $dialect->proximitySql('s.lat', 's.lon', ':lat', ':lon');
            $where .= " AND ({$distSql}) <= :rad";
            $sqlParams[':lat'] = $centroid['lat'];
            $sqlParams[':lon'] = $centroid['lon'];
            $sqlParams[':rad'] = $centroid['radius_km'];

            $sql = "SELECT p.category, p.subcategory, COUNT(DISTINCT s.poi_id) as count 
                    FROM poi_search s
                    INNER JOIN poi_public p ON s.poi_id = p.id
                    $where
                    GROUP BY p.category, p.subcategory";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($sqlParams);
            $rawCats = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Normalisation par Familles via MappingService
            $mappingService = new CategoryMappingService();
            $families = $mappingService->getFamilies();

            // Traduction du label (s'assurer que les fichiers sont chargés)
            \Core\Lang::loadLanguageFile(PLUGINS . 'SearchUi' . DS . 'langs' . DS);

            $categories = [];
            foreach ($families as $key => $family) {
                $categories[$key] = array_merge($family, [
                    'label' => \Core\Lang::get($family['label']),
                    'count' => 0,
                    'subcategories' => []
                ]);
            }

            foreach ($rawCats as $row) {
                $osmCat = $row['category'] ?? '';
                $osmSub = $row['subcategory'] ?? '';
                $cnt = (int) $row['count'];

                if (empty($osmCat))
                    continue;

                $familyData = $mappingService->mapToFamily($osmCat, $osmSub);
                $familyKey = $familyData['key'];

                if (isset($categories[$familyKey])) {
                    $categories[$familyKey]['count'] += $cnt;
                } else {
                    if (!isset($categories['other'])) {
                        $categories['other'] = [
                            'key' => 'other',
                            'label' => \Core\Lang::get('Other') ?: 'Autres',
                            'count' => 0,
                            'subcategories' => [],
                            'icon' => 'tag'
                        ];
                    }
                    $categories['other']['count'] += $cnt;
                }
            }

            // Filtrer et trier
            $categories = array_filter($categories, fn($cat) => $cat['count'] > 0);
            uasort($categories, fn($a, $b) => $b['count'] <=> $a['count']);

            return ['categories' => $categories];

        } catch (\Exception $e) {
            SearchLogger::error("CategoryAggregationService::getCategoriesByCity error: " . $e->getMessage());
            return ['categories' => []];
        }
    }

    private function loadAggloCommunes(): array
    {
        // Délègue à CommuneService pour éviter duplication
        $communeService = new \PoiData\Services\CommuneService();
        return $communeService->loadAggloCommunes();
    }

    /**
     * Calcule la distance entre deux points (Haversine simple)
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
