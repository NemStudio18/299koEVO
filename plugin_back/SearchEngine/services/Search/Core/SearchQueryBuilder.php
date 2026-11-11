<?php

namespace SearchEngine\Services\Search\Core;

use PDO;

defined('ROOT') or exit('Access denied!');

/**
 * Builder pour construire des requêtes SQL optimisées de recherche
 * avec support FULLTEXT, scoring métier et filtres géographiques
 */
class SearchQueryBuilder
{
    /**
     * Construit la condition FULLTEXT avec boosts et LIKE pour category/subcategory
     * 
     * @param string $query Texte de recherche
     * @param \Search\Services\CategoryLabelTranslator|null $translator Traducteur pour convertir les termes français/anglais
     * @return array ['sql' => string, 'params' => array, 'like_conditions' => array]
     */
    public function buildFullTextCondition(string $query, ?\Search\Services\CategoryLabelTranslator $translator = null): array
    {
        $query = trim($query);

        // Si la query est vide ou trop courte, pas de condition FULLTEXT
        if (empty($query) || mb_strlen($query) < 3) {
            return [
                'sql' => '',
                'params' => [],
                'like_conditions' => []
            ];
        }

        // Préparer la query pour FULLTEXT BOOLEAN MODE
        $ftQuery = $this->prepareFullTextQuery($query);

        // Condition FULLTEXT sur name, description, brand via le dialecte
        $dialect = \Core\Database::getInstance()->getDialect();
        $sql = $dialect->fulltextMatch(['p.name'], ':ft_query');
        $scoreSql = $sql;

        // Conditions LIKE pour category et subcategory (car elles ne sont pas dans l'index FULLTEXT)
        // Utiliser le traducteur pour convertir les termes français/anglais vers les clés OSM
        $likeConditions = [];
        $likeParams = [];

        if ($translator) {
            // Chercher dans le dictionnaire du traducteur pour trouver les clés OSM correspondantes
            $queryLower = strtolower($query);
            $osmKeys = $this->findOsmKeysForQuery($queryLower, $translator);

            if (!empty($osmKeys)) {
                // Si on trouve des clés OSM correspondantes, chercher dans category et subcategory
                $categoryConditions = [];
                $subcategoryConditions = [];
                $idx = 0;

                $categoryExpression = "s.category";
                $subcategoryExpression = "s.subcategory";

                foreach ($osmKeys as $osmKey) {
                    $categoryConditions[] = "{$categoryExpression} = :like_category_{$idx}";
                    $subcategoryConditions[] = "{$subcategoryExpression} = :like_subcategory_{$idx}";
                    $likeParams[":like_category_{$idx}"] = $osmKey;
                    $likeParams[":like_subcategory_{$idx}"] = $osmKey;
                    $idx++;
                }

                if (!empty($categoryConditions)) {
                    $likeConditions[] = '(' . implode(' OR ', $categoryConditions) . ')';
                }
                if (!empty($subcategoryConditions)) {
                    $likeConditions[] = '(' . implode(' OR ', $subcategoryConditions) . ')';
                }
            }
        }

        $categoryExpression = "s.category";
        $subcategoryExpression = "s.subcategory";

        // Ajouter aussi une recherche LIKE directe sur category et subcategory avec le texte original
        $likeConditions[] = "{$categoryExpression} LIKE :like_query_category";
        $likeConditions[] = "{$subcategoryExpression} LIKE :like_query_subcategory";
        $likeParams[':like_query_category'] = "%{$query}%";
        $likeParams[':like_query_subcategory'] = "%{$query}%";

        // Ajouter aussi des conditions LIKE pour name et brand en complément de FULLTEXT
        $likeConditions[] = "p.name LIKE :like_query_name";
        $likeConditions[] = "s.brand LIKE :like_query_brand";
        $likeParams[':like_query_name'] = "%{$query}%";
        $likeParams[':like_query_brand'] = "%{$query}%";

        // Score Hérité (Parité EVOback) : Name=50, Brand=30, Subcategory=20
        $legacyScoreParts = [
            "(CASE WHEN p.name = :exact_query THEN 100 WHEN p.name LIKE :like_query_name THEN 50 ELSE 0 END)",
            "(CASE WHEN s.brand LIKE :like_query_brand THEN 30 ELSE 0 END)"
        ];
        $likeParams[':exact_query'] = $query;

        if (!empty($osmKeys)) {
            // subcategories from findOsmKeysForQuery
            $legacyScoreParts[] = "20";
        }

        $legacyScoreSql = "(" . implode(' + ', $legacyScoreParts) . ")";

        return [
            'sql' => $sql,
            'score_sql' => $scoreSql,
            'legacy_score_sql' => $legacyScoreSql,
            'params' => [
                ':ft_query' => $ftQuery
            ],
            'like_conditions' => $likeConditions,
            'like_params' => $likeParams
        ];
    }

    /**
     * Trouve les clés OSM correspondant à une requête en utilisant le traducteur
     * 
     * @param string $query Query en minuscules
     * @param \Search\Services\CategoryLabelTranslator $translator
     * @return array Liste des clés OSM trouvées
     */
    private function findOsmKeysForQuery(string $query, \Search\Services\CategoryLabelTranslator $translator): array
    {
        $osmKeys = [];

        // Utiliser la réflexion pour accéder au dictionnaire privé du traducteur
        $reflection = new \ReflectionClass($translator);
        $property = $reflection->getStaticPropertyValue('translations');

        if (is_array($property)) {
            // Parcourir le dictionnaire pour trouver les clés OSM dont le label correspond à la query
            foreach ($property as $osmKey => $label) {
                $labelLower = strtolower($label);
                // Si la query est contenue dans le label ou vice versa
                if (strpos($labelLower, $query) !== false || strpos($query, $labelLower) !== false) {
                    $osmKeys[] = $osmKey;
                }
            }
        }

        return $osmKeys;
    }

    /**
     * Prépare la query pour FULLTEXT BOOLEAN MODE
     * Ajoute des wildcards et gère les mots courts
     * 
     * @param string $query
     * @return string
     */
    private function prepareFullTextQuery(string $query): string
    {
        // Nettoyer la query
        $query = trim($query);

        // Si la query contient des opérateurs FULLTEXT, les préserver
        if (preg_match('/[+\-><()~*"]/', $query)) {
            // La query contient déjà des opérateurs FULLTEXT, la retourner telle quelle
            return $query;
        }

        // Pour BOOLEAN MODE, on utilise les mots tels quels avec un préfixe + pour forcer la présence
        // Cela évite les problèmes avec les wildcards qui peuvent ne pas fonctionner correctement
        // Diviser en mots
        $words = preg_split('/\s+/', $query);
        $preparedWords = [];

        foreach ($words as $word) {
            $word = trim($word);
            // Ignorer les mots vides
            if (empty($word)) {
                continue;
            }
            // MySQL/MariaDB ignore les mots < ft_min_word_len (généralement 4)
            // On doit donc filtrer les mots trop courts
            if (mb_strlen($word) >= 4) {
                // Ajouter + devant chaque mot pour forcer sa présence (AND)
                $preparedWords[] = '+' . $word;
            } elseif (mb_strlen($word) >= 2) {
                // Mots de 2-3 caractères : les inclure sans + (seront ignorés par FULLTEXT mais pas d'erreur)
                $preparedWords[] = $word;
            }
        }

        if (empty($preparedWords)) {
            // Si aucun mot valide, retourner la query telle quelle (sera ignorée par FULLTEXT)
            return $query;
        }

        // Joindre avec des espaces
        return implode(' ', $preparedWords);
    }

    /**
     * Construit la clause SQL pour le scoring métier
     * 
     * @return string Clause SQL pour calculer business_score
     */
    public function buildBusinessScore(): string
    {
        $dialect = \Core\Database::getInstance()->getDialect();

        $claimedScore = $dialect->conditional('p.claimed_by IS NOT NULL', '5', '0');
        
        $thirtyDaysAgo = date('Y-m-d H:i:s', strtotime('-30 days'));
        // Nouvelle logique : 20 points si actif dans les 30 derniers jours, sinon 2 points si créé dans les 30 derniers jours (fallback old freshness)
        $activityScore = "(CASE 
                            WHEN p.last_activity_at IS NOT NULL AND p.last_activity_at > '{$thirtyDaysAgo}' THEN 20 
                            WHEN p.created_at IS NOT NULL AND p.created_at > '{$thirtyDaysAgo}' THEN 2 
                            ELSE 0 
                         END)";

        return "(
            -- Premium/Claimed : +5 points
            $claimedScore +
            
            -- Datascore (0-100) converti en 0-5 points
            (COALESCE(p.attributes->>'datascore', p.datascore, 0) / 20) +
            
            -- Fraîcheur/Activité : +20 points (actif 30j) ou +2 points (créé recent)
            $activityScore
        )";
    }

    /**
     * Construit le filtre géographique avec bounding box optimisé
     * 
     * @param float|null $lat Latitude
     * @param float|null $lon Longitude
     * @param float $radiusKm Rayon en km
     * @param array|null $communeCentroids Centroids des communes [nom => ['lat' => ..., 'lon' => ..., 'radius_km' => ...]]
     * @return array ['where' => string, 'select' => string, 'params' => array]
     */
    /**
     * Construit le filtre de catégorie/sous-catégorie (supporte les catégories métier)
     * 
     * @param string $category Clé de la catégorie (métier ou OSM)
     * @param string $subcategory Clé de la sous-catégorie
     * @return array ['where' => string, 'params' => array]
     */
    public function buildCategoryFilter(string $category, string $subcategory = ''): array
    {
        if (empty($category)) {
            return ['where' => '', 'params' => []];
        }

        $where = ["s.category = :target_category"];
        $params = [':target_category' => $category];

        if (!empty($subcategory)) {
            $where[] = "s.subcategory = :target_subcategory";
            $params[':target_subcategory'] = $subcategory;
        }

        return [
            'where' => implode(' AND ', $where),
            'params' => $params
        ];
    }

    public function buildGeoFilter(?float $lat, ?float $lon, float $radiusKm, ?array $communeCentroids = null): array
    {
        $where = [];
        $select = '';
        $params = [];

        // Priorité aux centroids de communes (agglo)
        if (!empty($communeCentroids)) {
            $geoConditions = [];
            $cityNames = array_keys($communeCentroids);
            $idx = 0;

            foreach ($communeCentroids as $cityName => $centroid) {
                if (isset($centroid['lat'], $centroid['lon'])) {
                    $centroidLat = (float) $centroid['lat'];
                    $centroidLon = (float) $centroid['lon'];
                    $radiusCapKm = 2.5; // Parité EVOback
                    $centroidRadius = min((float) ($centroid['radius_km'] ?? $radiusCapKm), $radiusCapKm);

                    // Bounding box pour optimisation (avant Haversine)
                    $latDelta = $centroidRadius / 111.0;
                    $lonDelta = $centroidRadius / (111.0 * cos(deg2rad($centroidLat)));

                    // Condition géographique avec bounding box et dialecte
                    $dialect = \Core\Database::getInstance()->getDialect();
                    $proximitySql = $dialect->proximitySql('p.lat', 'p.lon', ':centroid_lat_' . $idx, ':centroid_lon_' . $idx);

                    $geoConditions[] = "(
                        p.lat BETWEEN :centroid_lat_{$idx}_min AND :centroid_lat_{$idx}_max
                        AND p.lon BETWEEN :centroid_lon_{$idx}_min AND :centroid_lon_{$idx}_max
                        AND ($proximitySql) <= :centroid_radius_{$idx}
                    )";

                    $params[":centroid_lat_{$idx}_min"] = $centroidLat - $latDelta;
                    $params[":centroid_lat_{$idx}_max"] = $centroidLat + $latDelta;
                    $params[":centroid_lon_{$idx}_min"] = $centroidLon - $lonDelta;
                    $params[":centroid_lon_{$idx}_max"] = $centroidLon + $lonDelta;
                    $params[":centroid_lat_{$idx}"] = $centroidLat;
                    $params[":centroid_lat_{$idx}_2"] = $centroidLat; // Duplicate for PDO
                    $params[":centroid_lon_{$idx}"] = $centroidLon;
                    $params[":centroid_radius_{$idx}"] = $centroidRadius;
                    $idx++;
                }
            }

            if (!empty($geoConditions)) {
                $geoFilter = '(' . implode(' OR ', $geoConditions) . ')';

                // Ajouter vérification du nom de ville si présent
                $cityNameConditions = [];
                $cityIdx = 0;
                foreach ($cityNames as $cityName) {
                    $cityNameConditions[] = "UPPER(TRIM(p.addr_city)) = UPPER(TRIM(:city_name_{$cityIdx}))";
                    $params[":city_name_{$cityIdx}"] = $cityName;
                    $cityIdx++;
                }

                if (!empty($cityNameConditions)) {
                    $geoFilter .= " AND (p.addr_city IS NULL OR p.addr_city = '' OR (" . implode(' OR ', $cityNameConditions) . "))";
                }

                $where[] = $geoFilter;
            }
        } elseif ($lat !== null && $lon !== null) {
            // Filtre géographique simple (lat/lon) avec bounding box
            $latDelta = $radiusKm / 111.0;
            $lonDelta = $radiusKm / (111.0 * cos(deg2rad($lat)));

            $dialect = \Core\Database::getInstance()->getDialect();
            $proximityWhere = $dialect->proximitySql('p.lat', 'p.lon', ':lat', ':lon');
            $proximitySelect = $dialect->proximitySql('p.lat', 'p.lon', ':lat_select', ':lon_select');

            // Bounding box pour optimisation
            $where[] = "(
                p.lat BETWEEN :lat_min AND :lat_max
                AND p.lon BETWEEN :lon_min AND :lon_max
                AND ($proximityWhere) <= :radius
            )";

            // Calcul de distance pour SELECT
            $select = "($proximitySelect) AS distance_km";

            $params[':lat'] = $lat;
            $params[':lon'] = $lon;
            $params[':lat_min'] = $lat - $latDelta;
            $params[':lat_max'] = $lat + $latDelta;
            $params[':lon_min'] = $lon - $lonDelta;
            $params[':lon_max'] = $lon + $lonDelta;
            $params[':radius'] = $radiusKm;
            $params[':lat_select'] = $lat;
            $params[':lon_select'] = $lon;
        }

        return [
            'where' => !empty($where) ? implode(' AND ', $where) : '',
            'select' => $select,
            'params' => $params
        ];
    }

    /**
     * Construit la clause ORDER BY selon le type de tri
     * 
     * @param string $sort Type de tri : 'relevance', 'distance', 'name'
     * @param bool $hasTextScore Si un score texte est calculé
     * @param bool $hasBusinessScore Si un score métier est calculé
     * @param bool $hasDistance Si une distance est calculée
     * @return string Clause ORDER BY
     */
    public function buildOrderBy(string $sort, bool $hasTextScore = false, bool $hasBusinessScore = false, bool $hasDistance = false): string
    {
        switch ($sort) {
            case 'relevance':
                // Tri par score Hérité (Parité EVOback) si disponible
                if ($hasTextScore) {
                    return "legacy_score DESC, (text_score * 2 + business_score) DESC";
                } elseif ($hasBusinessScore) {
                    return "business_score DESC";
                }
                // Fallback : tri par nom
                return "p.name ASC";

            case 'distance':
                // Tri par distance (si disponible)
                if ($hasDistance) {
                    return "distance_km ASC";
                }
                // Fallback : tri par nom
                return "p.name ASC";

            case 'name':
            default:
                return "p.name ASC";
        }
    }
}

