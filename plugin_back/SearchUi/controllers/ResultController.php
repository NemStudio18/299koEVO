<?php

namespace SearchUI\Controllers;

use Core\Responses\PublicResponse;
use Core\Plugin\PluginsManager;
use Core\Auth\UsersManager;
use Core\Core;
use PoiData\Services\PoiCategoryService;
use SearchEngine\Services\Search\SearchService;
use SearchEngine\Services\Search\Facets\FacetsService;
use PoiData\Services\CategoryLabelTranslator;
use PoiData\Services\PoiDataFormatter;
use PoiData\Services\CommuneService;
use Utils\Util;

defined('ROOT') or exit('Access denied!');

/**
 * Controller pour la page de résultats de recherche (result.tpl)
 */
class ResultController extends BaseSearchController
{

    /**
     * Page de résultats de recherche
     */
    public function search()
    {
        $cfg = $this->getConfig();

        // Utiliser SearchService (Architecture unifiée)
        $service = new SearchService($cfg);

        $translator = new CategoryLabelTranslator();
        $communeService = new CommuneService();

        // Récupération des paramètres de recherche
        $query = trim($_GET['q'] ?? '');
        $useGeo = !empty($_GET['use_geo']) && $_GET['use_geo'] === '1';
        $lat = ($useGeo && !empty($_GET['lat'])) ? (float) $_GET['lat'] : null;
        $lon = ($useGeo && !empty($_GET['lon'])) ? (float) $_GET['lon'] : null;
        $radius = !empty($_GET['radius']) ? (float) $_GET['radius'] : ($cfg['default_radius'] ?? 5);
        $filterType = !empty($_GET['type']) ? $_GET['type'] : null;
        $filterCategory = !empty($_GET['category']) ? $_GET['category'] : null;
        $filterCity = !empty($_GET['city']) ? $_GET['city'] : null;
        $filterStars = !empty($_GET['stars']) ? (int) $_GET['stars'] : null;
        $sort = !empty($_GET['sort']) ? $_GET['sort'] : 'relevance';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        // Gestion des villes sélectionnées (format JSON)
        $communeCentroids = null;
        if (!empty($_GET['cities'])) {
            $citiesJson = json_decode($_GET['cities'], true);
            if (is_array($citiesJson)) {
                $communeCentroids = $citiesJson;
            }
        }

        // PRIORITÉ 1 : Toujours filtrer sur les communes de l'agglo par défaut
        // PRIORITÉ 2 : Si GPS activé explicitement (use_geo=1), utiliser le filtre GPS
        // PRIORITÉ 3 : Si villes spécifiques sélectionnées, utiliser celles-ci
        $communeCentroidsForFilter = null;

        // PRIORITÉ 1 : Par défaut, TOUJOURS charger toutes les communes de l'agglo
        if (!isset($_GET['global']) || $_GET['global'] !== '1') {
            $communeCentroidsForFilter = $communeService->loadAggloCentroids($cfg);
        }

        // PRIORITÉ 2 : Si l'utilisateur a sélectionné des villes spécifiques, les utiliser
        if (!empty($communeCentroids)) {
            $communeCentroidsForFilter = $communeCentroids;
        }

        // PRIORITÉ 3 : GPS uniquement si explicitement activé (use_geo=1)
        $useGeoForQuery = false;
        $latForQuery = null;
        $lonForQuery = null;

        if ($useGeo && $lat !== null && $lon !== null && empty($communeCentroidsForFilter)) {
            $useGeoForQuery = true;
            $latForQuery = $lat;
            $lonForQuery = $lon;
            $communeCentroidsForFilter = null;
        }

        $communeCentroids = $communeCentroidsForFilter;

        // Effectuer la recherche
        $rows = [];
        $totalResults = 0;
        $facets = null;

        try {
            // Effectuer la recherche via SearchService
            $searchResponse = $service->search(
                $query,                         // text
                $latForQuery,                   // lat
                $lonForQuery,                   // lon
                $radius,                        // radius
                $filterType ?? '',             // category
                $filterCategory ?? '',         // subcategory
                $filterCity ?? '',              // city
                $limit,                         // limit
                $offset,                        // offset
                $sort,                          // sort
                null,                           // filterCity (alternatif)
                $filterStars,                   // filterStars
                $communeCentroids               // communeCentroids
            );

            $rows = $searchResponse['results'] ?? [];
            $totalResults = $searchResponse['total'] ?? 0;
        } catch (\Exception $e) {
            if (class_exists('\Core\Core')) {
                \Core\Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_ERROR, 'ResultController::search error: ' . $e->getMessage());
            } else {
                error_log('ResultController::search error: ' . $e->getMessage());
            }
        }

        // Vérifier si les favoris sont actifs et charger les favoris de l'utilisateur
        $user = UsersManager::getCurrentUser();
        $userFavIds = [];
        if ($user && PluginsManager::isActivePlugin('PoiFavorites')) {
            try {
                $favManager = new \PoiFavorites\Entities\FavoriteManager();
                $userFavs = $favManager->getUserFavorites($user->id);
                foreach ($userFavs as $fav) {
                    $userFavIds[] = (int)$fav->getPoiId();
                }
            } catch (\Exception $e) {
                // Silencieux
            }
        }

        // Préparer les données pour le template
        $preparedResults = [];
        $pdo = null;
        $categoryService = null;

        try {
            $pdo = \Core\Database::getInstance()->getPdo();
            $categoryService = new PoiCategoryService($pdo);
        } catch (\Exception $e) {
            error_log('Error creating PDO connection: ' . $e->getMessage());
        }

        foreach ($rows as $row) {
            if (empty($row['id'])) {
                continue;
            }

            $poiId = (int)$row['id'];
            $poi = PoiDataFormatter::format($row, $translator, true, $categoryService, null, true, $poiId);
            
            // Ajouter le flag favori
            $poi['is_favorite'] = in_array((int)$poi['id'], $userFavIds);

            // Ajouter le statut de claim (comme dans HomeController)
            $isClaimed = (int) ($row['is_claimed'] ?? 0) > 0;
            $poi['claimed'] = $isClaimed;
            $poi['claimed_validated'] = $isClaimed;

            // Générer les URLs de détail
            $sqlService = new SearchService($cfg);
            $poiType = $poi['type'] ?? 'poi';
            $poiId = (int) $poi['id'];
            $urlName = $sqlService->generateUrlName($poi['name'], $poiId);

            try {
                $poi['detail_url'] = $this->router->generate('SearchUi-detail', ['type' => $poiType, 'name' => $urlName]);
                $poi['detail_url_short'] = '/' . $urlName;
            } catch (\Exception $e) {
                $poi['detail_url'] = '/SearchUi/' . urlencode($poiType) . '/' . $urlName;
                $poi['detail_url_short'] = '/' . $urlName;
            }
            $poi['detail_url_final'] = !empty($poi['detail_url_short']) ? $poi['detail_url_short'] : $poi['detail_url'];

            // Calculer la distance si GPS disponible (même si useGeo n'est pas explicitement activé, utiliser lat/lon si disponibles)
            $userLat = $lat ?? null;
            $userLon = $lon ?? null;
            // Si lat/lon sont dans les paramètres GET même sans use_geo, les utiliser
            if ($userLat === null && !empty($_GET['lat'])) {
                $userLat = (float) $_GET['lat'];
            }
            if ($userLon === null && !empty($_GET['lon'])) {
                $userLon = (float) $_GET['lon'];
            }

            // Valider les coordonnées : doivent être dans des plages valides (lat: -90 à 90, lon: -180 à 180)
            // et ne pas être (0, 0) qui est dans le Golfe de Guinée (probablement une valeur par défaut invalide)
            $hasValidUserCoords = ($userLat !== null && $userLon !== null) &&
                ($userLat >= -90 && $userLat <= 90) &&
                ($userLon >= -180 && $userLon <= 180) &&
                !($userLat == 0 && $userLon == 0);

            $hasValidPoiCoords = isset($poi['lat']) && isset($poi['lon']) &&
                ($poi['lat'] >= -90 && $poi['lat'] <= 90) &&
                ($poi['lon'] >= -180 && $poi['lon'] <= 180);

            if ($hasValidUserCoords && $hasValidPoiCoords) {
                $poiLat = (float) $poi['lat'];
                $poiLon = (float) $poi['lon'];
                $distance = $this->calculateDistance($userLat, $userLon, $poiLat, $poiLon);
                // Ne pas afficher la distance si elle est supérieure à 1000 km (probablement une erreur)
                if ($distance <= 1000) {
                    $poi['distance'] = round($distance, 2);
                    $poi['has_distance'] = true;
                } else {
                    $poi['distance'] = null;
                    $poi['has_distance'] = false;
                }
            } else {
                $poi['distance'] = null;
                $poi['has_distance'] = false;
            }

            // Les URLs d'édition et de claim ne sont plus nécessaires ici
            // Elles sont gérées par les plugins via le hook poi:card-actions

            // Les URLs claim ne sont plus nécessaires
            // Le plugin claim les injectera via le hook poi-actions

            // Préparer les flags pour simplifier le template
            // Fallback Facebook : afficher seulement si pas de site web ET a Facebook
            $poi['show_facebook_fallback'] = !$poi['has_website'] && $poi['has_facebook'] && !empty($poi['facebook']);

            // CTA Contribution et Claim sont gérés via hooks


            $preparedResults[] = $poi;
        }

        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('SearchUi', 'result');

        $totalPages = $totalResults > 0 ? (int) ceil($totalResults / $limit) : 1;

        $tpl->set('query', $query);
        $tpl->set('results', $preparedResults);
        $tpl->set('resultsCount', count($preparedResults));
        $tpl->set('totalResults', $totalResults);
        $tpl->set('page', $page);
        $tpl->set('limit', $limit);
        $tpl->set('totalPages', $totalPages);
        $tpl->set('hasMore', $page < $totalPages);
        $tpl->set('lat', $lat);
        $tpl->set('lon', $lon);
        $tpl->set('radius', $radius);
        $tpl->set('sort', $sort);

        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext(UsersManager::getCurrentUser());

        // Variables globales pour les boutons via politique
        $tpl->set('IS_LOGGED', $evaluator->canAccess(new \Core\Navigation\GenericResource('search-ui:logged'), $ctx));
        $tpl->set('CAN_FAVORITE', $evaluator->canAccess(new \Core\Navigation\GenericResource('poi:favorite'), $ctx));
        $tpl->set('isClaimActive', \Core\Plugin\PluginsManager::isActivePlugin('claim'));
        
        try {
            $loginUrl = $this->router->generate('login');
        } catch (\Exception $e) {
            $loginUrl = '/login';
        }
        $tpl->set('loginUrl', $loginUrl);
        $tpl->set('filterType', $filterType);
        $tpl->set('filterCategory', $filterCategory);
        $tpl->set('filterCity', $filterCity);
        $tpl->set('use_geo', $useGeo ? 1 : 0);
        $tpl->set('filterTypeLabel', $filterType ? ucfirst(str_replace(['editorial_', '_', '-'], ['', ' ', ' '], $filterType)) : null);
        $tpl->set('filterCategoryLabel', $filterCategory ? ucfirst(str_replace(['_', '-'], [' ', ' '], $filterCategory)) : null);
        $tpl->set('facets', $facets);

        // Préparer les URLs de pagination complètes (le moteur de template ne supporte pas les calculs)
        $paginationParams = [];
        if (!empty($query)) {
            $paginationParams['q'] = $query;
        }
        if ($lat !== null) {
            $paginationParams['lat'] = $lat;
        }
        if ($lon !== null) {
            $paginationParams['lon'] = $lon;
        }
        if ($radius !== null) {
            $paginationParams['radius'] = $radius;
        }
        if (!empty($sort)) {
            $paginationParams['sort'] = $sort;
        }
        if (!empty($filterType)) {
            $paginationParams['type'] = $filterType;
        }
        if (!empty($filterCategory)) {
            $paginationParams['category'] = $filterCategory;
        }
        if (!empty($filterCity)) {
            $paginationParams['city'] = $filterCity;
        }
        if ($useGeo) {
            $paginationParams['use_geo'] = '1';
        }

        // URL page précédente
        $prevPageUrl = null;
        if ($page > 1) {
            $prevParams = array_merge($paginationParams, ['page' => $page - 1]);
            try {
                $prevPageUrl = $this->router->generate('SearchUi-result') . '?' . http_build_query($prevParams);
            } catch (\Exception $e) {
                $prevPageUrl = '/SearchUi/result?' . http_build_query($prevParams);
            }
        }

        // URL page suivante
        $nextPageUrl = null;
        if ($page < $totalPages) {
            $nextParams = array_merge($paginationParams, ['page' => $page + 1]);
            try {
                $nextPageUrl = $this->router->generate('SearchUi-result') . '?' . http_build_query($nextParams);
            } catch (\Exception $e) {
                $nextPageUrl = '/SearchUi/result?' . http_build_query($nextParams);
            }
        }

        $tpl->set('prevPageUrl', $prevPageUrl);
        $tpl->set('nextPageUrl', $nextPageUrl);
        $tpl->set('translator', $translator);

        $siteUrl = \Core\Core::getInstance()->getConfigVal('siteUrl');
        $tpl->set('SITE_URL', $siteUrl);

        try {
            $loginUrl = $this->router->generate('login');
        } catch (\Exception $e) {
            $loginUrl = '/login';
        }
        $tpl->set('loginUrl', $loginUrl);

        $title = !empty($query) ? "Résultats pour \"$query\"" : 'Résultats de recherche';
        if ($this->runPlugin) {
            $this->runPlugin->setMainTitle($title);
            $this->runPlugin->setTitleTag($title);
        }

        // [HOOK] Analytics: Track Impressions
        $this->core->callHook('search:results-rendered', [
            'query' => $query,
            'total_results' => $totalResults,
            'results' => $preparedResults,
            'user' => UsersManager::getCurrentUser()
        ]);

        $response->setTitle($title);
        $response->addTemplate($tpl);

        return $response;
    }

    /**
     * Calcule la distance en km entre deux points GPS (formule de Haversine)
     * @param float $lat1 Latitude du point 1
     * @param float $lon1 Longitude du point 1
     * @param float $lat2 Latitude du point 2
     * @param float $lon2 Longitude du point 2
     * @return float Distance en kilomètres
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Rayon de la Terre en km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}

