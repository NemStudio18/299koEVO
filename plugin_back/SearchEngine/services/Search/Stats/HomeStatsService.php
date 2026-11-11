<?php

namespace SearchEngine\Services\Search\Stats;

use PDO;
use Search\Services\CacheJsonService;

defined('ROOT') or exit('Access denied!');

/**
 * Service de gestion des statistiques de la page d'accueil
 * 
 * Extrait de SimpleSearchService pour respecter le Single Responsibility Principle
 * Gère la mise à jour et la récupération des stats stockées dans home_stats
 */
class HomeStatsService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Met à jour les stats de la home dans la table home_stats
     * À appeler lors des imports ETL ou via un hook
     * 
     * @param callable $getAggregationsCallback Callback pour récupérer les agrégations
     * @param callable $getCategoriesCallback Callback pour récupérer les catégories
     * @param CacheJsonService|null $cacheService Service de cache optionnel
     * @return bool Succès de la mise à jour
     */
    public function updateHomeStats(
        callable $getAggregationsCallback,
        callable $getCategoriesCallback,
        ?CacheJsonService $cacheService = null
    ): bool {
        try {
            $dialect = \Core\Database::getInstance()->getDialect();
            // Vérifier si la table existe via le dialecte
            $stmt = $this->pdo->query($dialect->tableExists('home_stats'));
            if (!$stmt->fetch()) {
                error_log("search: Table home_stats n'existe pas. Exécutez la migration create_home_stats_table.php");
                return false;
            }

            $start = microtime(true);
            // ... (rest of logic)
            $aggloFile = PLUGINS . 'searchEVO' . DS . 'param' . DS . 'agglo.json';
            if (!file_exists($aggloFile))
                $aggloFile = PLUGINS . 'search' . DS . 'param' . DS . 'agglo.json';

            $aggloCities = [];
            if (is_file($aggloFile)) {
                $aggloData = json_decode(file_get_contents($aggloFile), true);
                if (is_array($aggloData) && isset($aggloData['communes']))
                    $aggloCities = $aggloData['communes'];
            }

            $categoriesData = $getCategoriesCallback();
            $categories = $categoriesData['categories'] ?? [];
            $aggregations = $getAggregationsCallback();

            $preparedCities = [];
            foreach ($aggregations['cities'] ?? [] as $city) {
                if (!empty($city['name'])) {
                    $preparedCities[] = [
                        'name' => $city['name'],
                        'count' => $city['count'],
                        'count_formatted' => number_format($city['count'], 0, ',', ' '),
                    ];
                }
            }

            $categoryIcons = [
                'amenity' => 'fa-briefcase',
                'shop' => 'fa-store',
                'tourism' => 'fa-map-marked-alt',
                'leisure' => 'fa-gamepad',
                'food' => 'fa-utensils',
                'healthcare' => 'fa-heart-pulse',
                'craft' => 'fa-hammer',
                'office' => 'fa-building',
                'natural' => 'fa-tree',
                'historic' => 'fa-landmark',
                'sport' => 'fa-futbol',
                'emergency' => 'fa-truck-medical',
                'education' => 'fa-graduation-cap',
                'transport' => 'fa-bus',
                'man_made' => 'fa-industry',
            ];

            $translatedCategories = [];
            $totalPoi = $aggregations['total'] ?? 0;

            foreach ($categories as $categoryKey => $categoryData) {
                $translatedSubcategories = [];
                foreach ($categoryData['subcategories'] ?? [] as $subcategoryKey => $subcategoryData) {
                    $translatedSubcategories[$subcategoryKey] = [
                        'key' => $subcategoryKey,
                        'label' => $subcategoryData['label'] ?? $subcategoryKey,
                        'count' => $subcategoryData['count'] ?? 0,
                        'count_formatted' => number_format($subcategoryData['count'] ?? 0, 0, ',', ' '),
                    ];
                }
                $translatedCategories[$categoryKey] = [
                    'key' => $categoryKey,
                    'label' => $categoryData['label'] ?? $categoryKey,
                    'icon' => $categoryIcons[$categoryKey] ?? 'fa-tag',
                    'count' => $categoryData['count'] ?? 0,
                    'count_formatted' => number_format($categoryData['count'] ?? 0, 0, ',', ' '),
                    'subcategories' => $translatedSubcategories,
                ];
            }

            $categoriesDataJson = [];
            foreach ($translatedCategories as $catKey => $catData) {
                $categoriesDataJson[$catKey] = htmlspecialchars(json_encode($catData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
            }

            $stats = [
                'total_poi' => $totalPoi,
                'total_poi_formatted' => number_format($totalPoi, 0, ',', ' '),
                'cities_json' => json_encode($preparedCities, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'categories_json' => json_encode($translatedCategories, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'categoryData_json' => json_encode($categoriesDataJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'last_update' => time(),
            ];

            $this->pdo->beginTransaction();
            try {
                foreach ($stats as $key => $value) {
                    $this->pdo->prepare("DELETE FROM home_stats WHERE `key` = ?")->execute([$key]);
                    $this->pdo->prepare("INSERT INTO home_stats (`key`, `value`) VALUES (?, ?)")->execute([$key, $value]);
                }
                $this->pdo->commit();
                if ($cacheService)
                    $cacheService->invalidateStats();
                return true;
            } catch (\Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            error_log("search: Erreur lors de la mise à jour de home_stats: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les stats de la home depuis la table home_stats
     * 
     * @return array|null Stats ou null si la table n'existe pas ou est vide
     */
    public function getHomeStats(): ?array
    {
        try {
            $dialect = \Core\Database::getInstance()->getDialect();
            // Vérifier si la table existe via le dialecte
            $stmt = $this->pdo->query($dialect->tableExists('home_stats'));
            if (!$stmt->fetch()) {
                return null;
            }

            // Récupérer toutes les stats (requête optimisée avec index sur `key`)
            $stmt = $this->pdo->query("SELECT `key`, `value` FROM home_stats ORDER BY `key`");
            $rows = $stmt->fetchAll();

            if (empty($rows)) {
                return null;
            }

            $stats = [];
            foreach ($rows as $row) {
                $key = $row['key'];
                $value = $row['value'];

                // Décoder les valeurs JSON pour les clés qui en contiennent
                if (in_array($key, ['cities', 'categories', 'categoryData_json', 'categories_json'])) {
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $stats[$key] = $decoded;
                    } else {
                        $stats[$key] = $value;
                    }
                } else {
                    $stats[$key] = $value;
                }
            }

            return $stats;
        } catch (\Exception $e) {
            error_log("search: Erreur lors de la récupération de home_stats: " . $e->getMessage());
            return null;
        }
    }
}
