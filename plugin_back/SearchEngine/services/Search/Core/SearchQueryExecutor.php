<?php

namespace SearchEngine\Services\Search\Core;

use PDO;
use SearchEngine\Services\SearchLogger;

defined('ROOT') or exit('Access denied!');

/**
 * Moteur de recherche unifié (Simplifié - Strict)
 * 
 * Utilise poi_search comme seule source de filtrage.
 * Résultat enrichi via poi_public.
 * Aucun post-traitement complexe en PHP.
 */
class SearchQueryExecutor
{
    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo, array $config = [])
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    public function execute(array $params): array
    {
        try {
            return $this->executeOptimizedSearch($params);
        } catch (\Throwable $e) {
            SearchLogger::error("SearchQueryExecutor::execute error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            return ['results' => [], 'total' => 0, 'mode' => 'error', 'error' => $e->getMessage()];
        }
    }

    /**
     * Recherche optimisée sur la table poi_search.
     * Pas de fallback, pas de logique complexe.
     */
    private function executeOptimizedSearch(array $params): array
    {
        $query = $params['text'] ?? '';
        $lat = $params['lat'] ?? null;
        $lon = $params['lon'] ?? null;
        $radiusKm = $params['radiusKm'] ?? (float)($this->config['radius_default'] ?? 5.0);
        $aroundMe = !empty($params['aroundMe']);
        $filterType = $params['category'] ?? null;
        $filterCategory = $params['subcategory'] ?? null;
        $filterCity = $params['city'] ?? $params['filterCity'] ?? '';
        $limit = $params['limit'] ?? (int)($this->config['items_per_page'] ?? 20);
        $offset = $params['offset'] ?? 0;

        $where = ["s.status = 'public'", "s.is_closed = 0", "s.subcategory IS NOT NULL", "s.subcategory != ''", "s.subcategory != 'poi'"];
        $sqlParams = [];

        // 1. Recherche texte FULLTEXT sur poi_public (name, description, brand)
        $hasTextQuery = !empty($query);
        
        if ($hasTextQuery) {
            error_log("SearchQueryExecutor: FULLTEXT Query received (BOOLEAN MODE): " . $query);
            $fulltextConditions = [];
            
            // On prépare une version pour BOOLEAN MODE (mots préfixés par +)
            $words = explode(' ', trim($query));
            $booleanQuery = '';
            foreach ($words as $word) {
                if (strlen($word) > 2) $booleanQuery .= '+' . $word . '* ';
                else $booleanQuery .= $word . ' ';
            }
            $booleanQuery = trim($booleanQuery);

            // 1. Recherche FULLTEXT en mode BOOLEAN (plus permissif que NATURAL LANGUAGE)
            // 2. Fallback LIKE sur le nom, la marque, la catégorie, la sous-catégorie et la description
            $fulltextConditions[] = "(MATCH(s.name) AGAINST (:q_bool IN BOOLEAN MODE) 
                                     OR s.name LIKE :qlike OR s.brand LIKE :qlike
                                     OR s.category LIKE :qlike OR s.subcategory LIKE :qlike
                                     OR p.description LIKE :qlike OR p.desc_list LIKE :qlike)";
            
            $sqlParams[':q_bool'] = $booleanQuery;
            $sqlParams[':qlike'] = '%' . $query . '%';
            
            // Reverse translation: rechercher aussi dans les catégories OSM traduites
            $normalizer = new \SearchEngine\Services\Search\Category\OsmCategoryNormalizer();
            try {
                $translatedMatches = $normalizer->reverseLookup($query);
                if (!empty($translatedMatches)) {
                    $categoryMatches = [];
                    $subcategoryMatches = [];
                    foreach ($translatedMatches as $match) {
                        if (isset($match['key'])) {
                            $categoryMatches[] = $match['key'];
                            $subcategoryMatches[] = $match['key'];
                        }
                    }
                    
                    if (!empty($categoryMatches)) {
                        $catIn = [];
                        foreach (array_unique($categoryMatches) as $k => $cat) {
                            $paramKey = ":trans_cat_$k";
                            $catIn[] = $paramKey;
                            $sqlParams[$paramKey] = $cat;
                        }
                        $fulltextConditions[] = "s.category IN (" . implode(',', $catIn) . ")";
                    }
                    if (!empty($subcategoryMatches)) {
                        $subIn = [];
                        foreach (array_unique($subcategoryMatches) as $k => $sub) {
                            $paramKey = ":trans_sub_$k";
                            $subIn[] = $paramKey;
                            $sqlParams[$paramKey] = $sub;
                        }
                        $fulltextConditions[] = "s.subcategory IN (" . implode(',', $subIn) . ")";
                    }
                }
            } catch (\Exception $e) {
                error_log("SearchQueryExecutor: Reverse translation error: " . $e->getMessage());
            }
            
            $where[] = "(" . implode(' OR ', $fulltextConditions) . ")";
        }

        $hasUserLocation = ($lat !== null && $lon !== null && $lat != 0 && $lon != 0);

        // 2. Filtre géographique & Distance "de base"
        // Si aucune coordonnée n'est fournie, on utilise le centre de Bourges par défaut
        // pour que distance_formatted soit toujours calculé "de base".
        if (!$hasUserLocation) {
            $lat = 47.0810;
            $lon = 2.3986;
        }

        $dialect = \Core\Database::getInstance()->getDialect();
        $proximitySql = $dialect->proximitySql('s.lat', 's.lon', ':lat', ':lon');

        // On ne filtre par rayon QUE si "Autour de moi" est coché
        // Modification: On permet le filtrage par rayon même avec une recherche texte
        if ($aroundMe) {
            $where[] = "($proximitySql) <= :radius_km";
            $sqlParams[':radius_km'] = $radiusKm;
        }

        $sqlParams[':lat'] = $lat;
        $sqlParams[':lon'] = $lon;
        $distanceSelect = "($proximitySql) AS distance_km";

        // 3. Filtres Catégories / Familles
        // Modification: On permet le filtrage explicite (Sous-catégorie ou Famille) même si une recherche texte est présente.
        // Cela permet de faire "Restaurant" + Filtre "Pizzeria".
        
        $hasExplicitFilter = !empty($filterType) || !empty($filterCategory) || !empty($params['familyCriteria']);
        
        // On applique les filtres si:
        // 1. C'est un filtre explicite (Category/Family/Subcategory) -> Toujours
        // 2. C'est le filtre global (GlobalMapped) -> Seulement si PAS de recherche texte (pour ne pas masquer des résultats pertinents hors categories)
        
        if ($hasExplicitFilter || (!$hasTextQuery && !empty($params['globalMappedCriteria']))) {
            // Filtrage par catégorie principale (type ou category)
            if (!empty($filterType)) {
                $cats = explode(',', $filterType);
                $inType = [];
                foreach ($cats as $k => $v) {
                    $paramKey = ":cat_type_filter_$k";
                    $inType[] = $paramKey;
                    $sqlParams[$paramKey] = trim($v);
                }
                $where[] = "(s.category IN (" . implode(',', $inType) . ") OR s.subcategory IN (" . implode(',', $inType) . "))";
            }

            if (!empty($filterCategory)) {
                // Filtrage par sous-catégorie spécifique ou catégorie (chips de spécialités)
                // On accepte une seule ou plusieurs séparées par virgule
                $subs = explode(',', $filterCategory);
                $in = [];
                foreach ($subs as $k => $v) {
                    $paramKey = ":sub_filter_$k";
                    $in[] = $paramKey;
                    $sqlParams[$paramKey] = trim($v);
                }
                $where[] = "(s.subcategory IN (" . implode(',', $in) . ") OR s.category IN (" . implode(',', $in) . "))";
            } elseif (empty($filterType) && !empty($params['familyCriteria'])) {
                // Sinon, filtrage global par la famille sélectionnée
                $c = $params['familyCriteria'];
                $familyConditions = [];
                if (!empty($c['categories'])) {
                    $in = [];
                    foreach ($c['categories'] as $k => $v) {
                        $paramKey = ":fm_cat_$k";
                        $in[] = $paramKey;
                        $sqlParams[$paramKey] = $v;
                    }
                    $familyConditions[] = "s.category IN (" . implode(',', $in) . ")";
                }
                if (!empty($c['subcategories'])) {
                    $in = [];
                    foreach ($c['subcategories'] as $k => $v) {
                        $paramKey = ":fm_sub_$k";
                        $in[] = $paramKey;
                        $sqlParams[$paramKey] = $v;
                    }
                    $familyConditions[] = "s.subcategory IN (" . implode(',', $in) . ")";
                }
                if (!empty($familyConditions)) {
                    $where[] = "(" . implode(' OR ', $familyConditions) . ")";
                }
            } elseif (!empty($params['globalMappedCriteria'])) {
                // Filtrage global : Uniquement les POIs mappés dans une famille
                // Note: Ce bloc ne s'exécute que si !$hasTextQuery grace au if principal
                $c = $params['globalMappedCriteria'];
                $globalConditions = [];
                if (!empty($c['categories'])) {
                    $in = [];
                    foreach ($c['categories'] as $k => $v) {
                        $paramKey = ":glob_cat_$k";
                        $in[] = $paramKey;
                        $sqlParams[$paramKey] = $v;
                    }
                    $globalConditions[] = "s.category IN (" . implode(',', $in) . ")";
                }
                if (!empty($c['subcategories'])) {
                    $in = [];
                    foreach ($c['subcategories'] as $k => $v) {
                        $paramKey = ":glob_sub_$k";
                        $in[] = $paramKey;
                        $sqlParams[$paramKey] = $v;
                    }
                    $globalConditions[] = "s.subcategory IN (" . implode(',', $in) . ")";
                }
                if (!empty($globalConditions)) {
                    $where[] = "(" . implode(' OR ', $globalConditions) . ")";
                }
            }
        } // End filters


        if (!empty($params['starsMin'])) {
            $where[] = "s.stars >= :stars_min";
            $sqlParams[':stars_min'] = (int) $params['starsMin'];
        }

        // 5. Calcul "Ouvert maintenant" (is_open_now)
        // IMPORTANT: normalize_hours.php stocke 0=Lu,1=Ma,2=Me,3=Je,4=Ve,5=Sa,6=Di (0-indexed)
        // date('N') retourne 1=Lu..7=Di (ISO 1-indexed) → on soustrait 1 pour aligner
        $currentDay = (int)date('N') - 1; // 0=Lu, 4=Ve, 5=Sa, 6=Di
        $currentTime = date('H:i:s');
        $sqlParams[':current_day'] = $currentDay;
        $sqlParams[':current_time'] = $currentTime;

        $isSqlite = ($this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite');
        $closingSoonCheck = $isSqlite 
            ? "time(:current_time, '+1 hour') >= oh.close_time"
            : "ADDTIME(:current_time, '01:00:00') >= oh.close_time";

        $hoursConcat = $isSqlite
            ? "(SELECT GROUP_CONCAT(oh.open_time || '-' || oh.close_time, ', ')
                FROM poi_opening_hours oh 
                WHERE oh.poi_id = s.poi_id 
                AND oh.day_of_week = :current_day 
                AND oh.is_closed = 0
               ) AS opening_hours_today"
            : "(SELECT GROUP_CONCAT(CONCAT(DATE_FORMAT(oh.open_time, '%H:%i'), '-', DATE_FORMAT(oh.close_time, '%H:%i')) SEPARATOR ', ')
                FROM poi_opening_hours oh 
                WHERE oh.poi_id = s.poi_id 
                AND oh.day_of_week = :current_day 
                AND oh.is_closed = 0
               ) AS opening_hours_today";

        $isOpenNowSql = "(CASE 
            WHEN NOT EXISTS (SELECT 1 FROM poi_opening_hours WHERE poi_id = s.poi_id) THEN NULL
            WHEN EXISTS (
                SELECT 1 FROM poi_opening_hours oh 
                WHERE oh.poi_id = s.poi_id 
                AND oh.day_of_week = :current_day 
                AND oh.is_closed = 0
                AND :current_time BETWEEN oh.open_time AND oh.close_time
            ) THEN 1 
            ELSE 0 
        END) AS is_open_now,
        (CASE 
            WHEN EXISTS (
                SELECT 1 FROM poi_opening_hours oh 
                WHERE oh.poi_id = s.poi_id 
                AND oh.day_of_week = :current_day 
                AND oh.is_closed = 0
                AND :current_time BETWEEN oh.open_time AND oh.close_time
                AND {$closingSoonCheck}
            ) THEN 1 
            ELSE 0 
        END) AS is_closing_soon,
        {$hoursConcat}";

        // Filtre "Ouvert Uniquement" en temps réel basé sur poi_opening_hours
        if (!empty($params['onlyOpen'])) {
            $where[] = "EXISTS (
                SELECT 1 FROM poi_opening_hours oh 
                WHERE oh.poi_id = s.poi_id 
                AND oh.day_of_week = :current_day 
                AND oh.is_closed = 0
                AND :current_time BETWEEN oh.open_time AND oh.close_time
            )";
        }

        if (isset($params['isClaimed']) && $params['isClaimed'] === true) {
            error_log("SearchQueryExecutor: Claimed filter active, checking p.claimed_by IS NOT NULL");
            $where[] = "p.claimed_by IS NOT NULL";
        }

        // 4. Filtre Ville (Strictement Spatial) (SKIP SI RECHERCHE TEXTE)
        if (!$hasTextQuery && !empty($filterCity)) {
            $centroids = \Core\Core::getInstance()->callHook('commune:get-centroids', [
                'config' => [],
                'commune_names' => [$filterCity]
            ]);
            $centroid = $centroids[$filterCity] ?? null;

            if ($centroid && isset($centroid['lat'], $centroid['lon'])) {
                $proxCity = $dialect->proximitySql('s.lat', 's.lon', ':c_lat', ':c_lon');
                $where[] = "($proxCity) <= :c_rad";
                $sqlParams[':c_lat'] = $centroid['lat'];
                $sqlParams[':c_lon'] = $centroid['lon'];
                $sqlParams[':c_rad'] = $centroid['radius_km'] ?? 5.0;
            }
        } elseif (!empty($params['communeCentroids'])) {
            // Filtrage global sur l'agglomération (plusieurs centroids)
            // Pour des raisons de performance sur 96k lignes, on applique d'abord un bounding box large
            $centroids = $params['communeCentroids'];
            $lats = array_column($centroids, 'lat');
            $lons = array_column($centroids, 'lon');
            
            if (!empty($lats) && !empty($lons)) {
                $minLat = min($lats) - 0.1; $maxLat = max($lats) + 0.1;
                $minLon = min($lons) - 0.1; $maxLon = max($lons) + 0.1;
                
                $where[] = "s.lat BETWEEN :min_lat AND :max_lat";
                $where[] = "s.lon BETWEEN :min_lon AND :max_lon";
                $sqlParams[':min_lat'] = $minLat;
                $sqlParams[':max_lat'] = $maxLat;
                $sqlParams[':min_lon'] = $minLon;
                $sqlParams[':max_lon'] = $maxLon;
            }
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        // Tri simple
        $sort = $params['sort'] ?? 'relevance';
        // Tri par défaut : Score composite (Qualité + Pro + Proximité)
        // Bonus Proximité calibré : +45 (<0.35km), +30 (<0.7km), +15 (<1.5km), +5 (<3km)
        $proximityBonus = "(CASE 
            WHEN distance_km < 0.35 THEN 45 
            WHEN distance_km < 0.7 THEN 30 
            WHEN distance_km < 1.5 THEN 15 
            WHEN distance_km < 3.0 THEN 5 
            ELSE 0 END)";

        // Activité récente (-30 jours)
        $thirtyDaysAgo = date('Y-m-d H:i:s', strtotime('-30 days'));
        $activityScore = "(CASE WHEN p.last_activity_at IS NOT NULL AND p.last_activity_at > :thirty_days_ago THEN 20 ELSE 0 END)";
        $sqlParams[':thirty_days_ago'] = $thirtyDaysAgo;

        // Bonus Pro (payant) : +30 pts | Bonus Revendiqué (gratuit) : +15 pts
        $proBonus = "(CASE WHEN u.subscription IN ('pro', 'pro_plus') THEN 30 ELSE 0 END)";
        $claimedBonus = "(CASE WHEN p.claimed_by IS NOT NULL THEN 15 ELSE 0 END)";
        
        // Score de Qualité (Datascore + Etoiles)
        $qualityScore = "(COALESCE(p.datascore, 0) + (COALESCE(p.stars, 0) * 5))";

        $compositeScore = "({$qualityScore} + {$proBonus} + {$claimedBonus} + {$proximityBonus} + {$activityScore})";
        
        $openScoreOrderBy = "(CASE WHEN is_open_now = 1 THEN 2 WHEN is_open_now IS NULL THEN 1 ELSE 0 END)";
        
        $orderBy = "{$openScoreOrderBy} DESC, {$compositeScore} DESC, s.name ASC";

        if ($sort === 'distance' && $hasUserLocation) {
            $orderBy = "{$openScoreOrderBy} DESC, distance_km ASC, {$compositeScore} DESC, s.name ASC";
        } elseif ($sort === 'relevance' && !empty($query)) {
            // Tri par pertinence texte avec poids configurables
            $wName = (int)($this->config['weight_name'] ?? 10);
            $wBrand = (int)($this->config['weight_brand'] ?? 2);

            // 1. Match exact dans le titre (Priorité absolue)
            // 2. Fallback LIKE
            $textScore = "(CASE 
                            WHEN p.name LIKE :exact_q THEN " . ($wName * 10) . "
                            WHEN p.name LIKE :qlike THEN {$wName}
                            WHEN s.brand LIKE :qlike THEN {$wBrand}
                            ELSE 0 
                         END)";
                         
            if ($hasUserLocation) {
                // Exact Match > Open > Text Match > Proximity/Pro Score
                $orderBy = "{$textScore} DESC, {$openScoreOrderBy} DESC, {$compositeScore} DESC, distance_km ASC, s.name ASC";
            } else {
                $orderBy = "{$textScore} DESC, {$openScoreOrderBy} DESC, {$compositeScore} DESC, s.name ASC";
            }
            $sqlParams[':exact_q'] = $query;
        } elseif ($sort === 'relevance' && empty($query) && $hasUserLocation) {
            // Vue vide (sans mot clé) avec coordonnées utilisateur
            $orderBy = "{$openScoreOrderBy} DESC, {$compositeScore} DESC, distance_km ASC, s.name ASC";
        }

        $limitClause = $dialect->limit((int) $limit, (int) $offset);

        // Requête principale
        // On récupère tout p.* et quelques champs de s pour l'affichage/debug
        $sql = "SELECT p.*, s.category as search_category, s.subcategory as search_subcategory, {$distanceSelect}, {$isOpenNowSql}
                FROM poi_search s 
                JOIN poi_public p ON p.id = s.poi_id 
                LEFT JOIN users u ON u.id = p.claimed_by
                {$whereClause} 
                ORDER BY {$orderBy} 
                {$limitClause}";

        $countSql = "SELECT COUNT(*) as total FROM poi_search s JOIN poi_public p ON p.id = s.poi_id LEFT JOIN users u ON u.id = p.claimed_by {$whereClause}";

        // Debug logging
        SearchLogger::debug("SearchQueryExecutor::execute - SQL: " . $sql);
        SearchLogger::debug("SearchQueryExecutor::execute - Params: " . json_encode($sqlParams));

        // Exécution
        $total = 0;
        $rows = [];

        // Fonction helper pour binder les paramètres réellement présents dans la requête
        $bindParams = function ($stmt, $sql, $params) {
            foreach ($params as $k => $v) {
                // Si le placeholder est présent dans la requête SQL, on le lie
                if (strpos($sql, $k) !== false) {
                    $stmt->bindValue($k, $v);
                }
            }
        };

        // Count
        $countStmt = $this->pdo->prepare($countSql);
        $bindParams($countStmt, $countSql, $sqlParams);
        if (!$countStmt->execute()) {
            $errorInfo = $countStmt->errorInfo();
            SearchLogger::error("SearchQueryExecutor: SQL Error in COUNT: " . ($errorInfo[2] ?? 'Unknown error'));
            return ['results' => [], 'total' => 0, 'mode' => 'error'];
        }
        $total = (int) $countStmt->fetchColumn();


        // Rows
        $stmt = $this->pdo->prepare($sql);
        $bindParams($stmt, $sql, $sqlParams);
        if (!$stmt->execute()) {
            $errorInfo = $stmt->errorInfo();
            SearchLogger::error("SearchQueryExecutor: SQL Error in SELECT: " . ($errorInfo[2] ?? 'Unknown error'));
            return ['results' => [], 'total' => 0, 'mode' => 'error'];
        }
        $rows = $stmt->fetchAll();


        SearchLogger::debug("SearchQueryExecutor::execute - Found " . count($rows) . " results (total: $total)");

        return ['results' => $rows, 'total' => $total, 'mode' => 'optimized'];
    }
}