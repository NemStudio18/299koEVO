<?php

namespace SearchEngine\Services\Search;

use PDO;
use Core\Core;
use SearchEngine\Services\CacheJsonService;
use SearchEngine\Services\Search\Core\SearchQueryExecutor;
use SearchEngine\Services\Search\Category\CategoryAggregationService;
use SearchEngine\Services\Search\Stats\HomeStatsService;
use SearchEngine\Services\Search\Stats\StatsAggregator;
use SearchEngine\Services\Search\Facets\FacetsService;
use SearchEngine\Services\Search\Core\SearchResultFormatter;
use SearchEngine\Services\SearchLogger;
use SearchEngine\Services\ConfigServiceInterface;
use Utils\Util;

defined('ROOT') or exit('Access denied!');

/**
 * Service de recherche unifié (Simplifié)
 * 
 * Orchestrateur principal.
 * Délègue strictement aux services spécialisés sans logique métier complexe additionnelle.
 */
class SearchService
{
    private PDO $pdo;
    private array $config;

    // Services délégués
    private HomeStatsService $homeStatsService;
    private StatsAggregator $statsAggregator;
    private CategoryAggregationService $categoryService;
    private FacetsService $facetsService;
    private SearchQueryExecutor $queryExecutor;
    private SearchResultFormatter $resultFormatter;
    private CacheJsonService $cacheService;

    public function __construct(array $config = [])
    {
        if (empty($config)) {
            $pluginManager = \Core\Plugin\PluginsManager::getInstance();
            $plugin = $pluginManager->getPlugin('SearchEngine');
            if ($plugin) {
                $config = $plugin->getConfig();
            }
        }
        $this->config = $config;

        if (class_exists('\\Core\\Database')) {
            $this->pdo = \Core\Database::getInstance()->getPdo('searchevo');
        } else {
            // Fallback (dev/test sans core)
            $dsn = "mysql:host={$config['sql_host']};dbname={$config['sql_db']};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $config['sql_user'] ?? '', $config['sql_pass'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        $this->homeStatsService = new HomeStatsService($this->pdo);
        $this->statsAggregator = new StatsAggregator($this->pdo);
        $this->categoryService = new CategoryAggregationService($this->pdo);
        $this->facetsService = new FacetsService($this->pdo);
        $this->queryExecutor = new SearchQueryExecutor($this->pdo, $this->config);
        $this->resultFormatter = new SearchResultFormatter();
        $this->cacheService = new CacheJsonService($this->pdo);
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    // =========================
    // API STATS
    // =========================

    public function updateHomeStats(?CacheJsonService $cacheService = null): bool
    {
        return $this->homeStatsService->updateHomeStats(
            fn() => $this->categoryService->getAggregations(),
            fn() => $this->categoryService->getCategoriesByCity(''),
            $cacheService
        );
    }

    public function getHomeStats(): ?array
    {
        return $this->homeStatsService->getHomeStats();
    }

    public function getAllPoisForAggloOptimized(array $aggloCities): array
    {
        return $this->statsAggregator->getAllPoisForAggloOptimized($aggloCities);
    }

    public function countTotalPois(array $aggloCities): int
    {
        return $this->statsAggregator->countTotalPois($aggloCities);
    }

    // =========================
    // API CATEGORIES
    // =========================

    public function getAggregations(): array
    {
        // Utilisation du cache, mais la logique sous-jacente a été simplifiée dans CategoryAggregationService
        return $this->cacheService->getStats(function () {
            $data = $this->categoryService->getAggregations();
            SearchLogger::debug("SearchService::getAggregations (miss) - cities: " . count($data['cities'] ?? []));
            // Mapping simple pré-cache
            return [
                'total' => $data['total'] ?? 0,
                'cities' => $data['cities'] ?? [],
                'categories' => $data['categories'] ?? [] // On utilise 'categories' direct
            ];
        });
    }

    public function getCategoriesByCity(string $cityName): array
    {
        // Le service CategoryAggregationService applique déjà le filtrage strict demandé
        return $this->cacheService->getChips($cityName ?: 'agglo', function () use ($cityName) {
            return $this->categoryService->getCategoriesByCity($cityName);
        });
    }

    // =========================
    // API FACETTES
    // =========================

    public function getFacets(array $cfg, array $poiIds, ?string $filterType = null): array
    {
        return $this->facetsService->getFacets($poiIds, $filterType);
    }

    // =========================
    // API RECHERCHE
    // =========================

    /**
     * Recherche unifiée simplifiée.
     * Passe les paramètres bruts au QueryExecutor.
     */
    public function search(
        string $text = '',
        ?float $lat = null,
        ?float $lon = null,
        ?float $radiusKm = null,
        string $category = '',
        $subcategory = '',
        string $city = '',
        int $limit = 20,
        int $offset = 0,
        string $sort = 'relevance',
        ?string $filterCity = null,
        ?int $filterStars = null,
        ?array $communeCentroids = null,
        string $family = '',
        bool $onlyCategorized = false,
        bool $aroundMe = false,
        bool $isClaimed = false
    ): array {
        SearchLogger::debug("SearchService::search - Received parameters: text='$text', lat='$lat', lon='$lon', radius='$radiusKm', category='$category', subcategory='$subcategory', city='$city', limit=$limit, offset=$offset, sort='$sort', filterCity='$filterCity', filterStars='$filterStars', family='$family', onlyCategorized=" . ($onlyCategorized ? 'true' : 'false'));
        // Résoudre la famille en critères OSM si nécessaire
        // Si des sous-catégories spécifiques sont demandées, on les utilise en priorité (via $subcategory)
        // et on ne charge PAS les critères génériques de la famille        // Nouveau système de filtrage par "Famille" (UI)
        $mappingService = new \SearchEngine\Services\Search\Category\CategoryMappingService();
        $familyCriteria = null;
        if (!empty($family)) {
            $familyCriteria = $mappingService->getOsmCriteriaForFamily($family);
        }

        $onlyOpen = (!empty($_GET['only_open'])); 

        $params = [
            'text' => $text,
            'lat' => $lat,
            'lon' => $lon,
            'radiusKm' => $radiusKm,
            'category' => $category,
            'subcategory' => $subcategory,
            'city' => $city ?: $filterCity,
            'limit' => $limit,
            'offset' => $offset,
            'sort' => $sort,
            'filterCity' => $filterCity,
            'communeCentroids' => $communeCentroids,
            'familyCriteria' => $familyCriteria,
            'starsMin' => $filterStars,
            'onlyOpen' => (!empty($_GET['only_open'])),
            'isClaimed' => $isClaimed,
            'onlyCategorized' => $onlyCategorized,
            'aroundMe' => $aroundMe
        ];

        // Charger tous les critères de mapping si le filtrage strict est actif et qu'aucune famille n'est sélectionnée
        if ($onlyCategorized && empty($family) && empty($category) && empty($subcategory)) {
            $params['globalMappedCriteria'] = $mappingService->getAllMappedCriteria();
        }

        // Exécution directe, sans logique conditionnelle complexe
        $rawResults = $this->queryExecutor->execute($params);

        // Formatage standard unique
        return $this->resultFormatter->format($rawResults, 'optimized');
    }

    // =========================
    // API POI
    // =========================

    public function fetchOne($id): ?array
    {
        try {
            $provider = Core::getInstance()->callHook('entity:provider-request', ['entity' => 'poi']);
            if ($provider) {
                return $provider->get((int)$id);
            }
        } catch (\Exception $e) {
            $this->log("SearchService::fetchOne - Provider error: " . $e->getMessage(), 'error');
        }
        return null;
    }

    public function fetchByName(string $name): ?array
    {
        try {
            $provider = Core::getInstance()->callHook('entity:provider-request', ['entity' => 'poi']);
            if ($provider && method_exists($provider, 'getIdBySlug')) {
                $id = $provider->getIdBySlug($name);
                if ($id) {
                    return $provider->get($id);
                }
            }
        } catch (\Exception $e) {
            $this->log("SearchService::fetchByName - Provider error: " . $e->getMessage(), 'error');
        }
        return null;
    }

    public function generateUrlName(string $name, $id, ?array $duplicateNamesMap = null): string
    {
        $slug = $this->createSlug($name);

        // IDs négatifs (utilisateur/admin) -> toujours ajouter l'ID car il n'est pas dans OSM
        if (is_numeric($id) && (int) $id < 0) {
            return $slug . '--' . abs((int)$id); // Double tiret pour les IDs négatifs
        }

        $hasDuplicate = $duplicateNamesMap !== null ? isset($duplicateNamesMap[$name]) : $this->hasDuplicateNames($name);

        return $hasDuplicate ? ($slug . '-' . (int)$id) : $slug;
    }

    public function hasDuplicateNames(string $name): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM poi_public WHERE name = :name");
        $stmt->execute([':name' => $name]);
        $result = $stmt->fetch();
        return ($result['count'] ?? 0) > 1;
    }

    public function getDuplicateNamesMap(array $pois): array
    {
        $names = [];
        foreach ($pois as $poi) {
            $n = $poi['name'] ?? '';
            if ($n) $names[$n] = true;
        }

        if (empty($names)) return [];

        $keys = array_keys($names);
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $this->pdo->prepare("SELECT name, COUNT(*) as count FROM poi_public WHERE name IN ($placeholders) GROUP BY name HAVING count > 1");
        $stmt->execute($keys);

        $map = [];
        while ($row = $stmt->fetch()) {
            $map[$row['name']] = true;
        }
        return $map;
    }

    public function createSlug(string $name): string
    {
        $replacements = [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'ÿ' => 'y', 'ç' => 'c', 'ñ' => 'n'
        ];
        $slug = strtr(mb_strtolower($name), $replacements);
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
        return trim($slug, '-');
    }

    // =========================
    // HELPERS
    // =========================

    public function log(string $message, string $level = 'DEBUG'): void
    {
        SearchLogger::log($message, (string) $level);
    }

    public function getNotClosedCondition(string $tableAlias = 'ps'): string
    {
        return "({$tableAlias}.is_closed = 0 OR {$tableAlias}.is_closed IS NULL)";
    }
}
