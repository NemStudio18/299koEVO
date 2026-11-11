<?php

namespace SearchUI\Controllers;

use Core\Responses\PublicResponse;
use Core\Responses\HtmxResponse;
use Core\Core;
use SearchEngine\Services\Search\SearchService;
use SearchEngine\Services\Search\Stats\HomeStatsService;
use SearchEngine\Services\Search\Category\CategoryAggregationService;
use Utils\Util;
use SearchEngine\Services\Search\CategoryService;
use ThemeServices\Services\LandingStatsService;
use SearchEngine\Services\CacheJsonService;
use PoiData\Services\CategoryLabelTranslator;
use PoiData\Services\PoiRepository;
use PoiData\Services\PoiCategoryService;
use PoiData\Services\CommuneService;
use PoiData\Services\PoiDataFormatter;
use PoiData\Services\PoiIconMapper; // Added for icon mapping
use Config\Config;
use PDO;

defined('ROOT') or exit('Access denied!');

/**
 * Controller pour la page d'accueil de recherche (home.tpl)
 */
class HomeController extends BaseSearchController
{
    /**
     * Page d'accueil de recherche
     * Affiche simplement les données de la BDD filtrées par agglo.json
     */
    /**
     * Handle pretty search URLs like /recherche/category/city
     * Maps route parameters to GET parameters and delegates to home()
     */
    public function prettySearch($p1 = null, $p2 = null, $p3 = null)
    {
        // On récupère les clés internes à partir des slugs traduits
        $key1 = $p1 ? $this->translateSlugToKey($p1) : null;
        $key2 = $p2 ? $this->translateSlugToKey($p2) : null;
        $key3 = $p3 ? $this->translateSlugToKey($p3) : null;

        // p1 est toujours la famille/catégorie (ou son slug)
        if ($key1 !== null) {
            $_GET['family'] = $key1;
            $_GET['category'] = $key1;
        }

        if ($p3 !== null) {
            // 3 paramètres : catégorie / sous-cat / ville
            // On utilise les clés si trouvées, sinon les slugs bruts (pour la ville par exemple)
            $_GET['subcategory'] = $key2 ?: str_replace('-', ' ', $p2);
            $_GET['city'] = str_replace('-', ' ', $p3);
        } elseif ($p2 !== null) {
            // 2 paramètres : catégorie / (sous-cat OU ville)
            // On vérifie si p2 est une ville de l'agglo
            $communeService = new \PoiData\Services\CommuneService();
            $cities = $communeService->loadAggloCommunes();
            
            $isCity = false;
            $searchCity = str_replace('-', ' ', strtolower($p2));
            foreach ($cities as $city) {
                if (strtolower($city) === $searchCity || strtolower($city) === strtolower($p2)) {
                    $isCity = true;
                    $p2 = $city; // Normaliser avec le vrai nom de la ville
                    break;
                }
            }
            
            if ($isCity) {
                $_GET['city'] = $p2;
            } else {
                // Si c'est pas une ville, c'est une sous-catégorie
                $_GET['subcategory'] = $key2 ?: str_replace('-', ' ', $p2);
            }
        }
        
        return $this->home();
    }

    /**
     * Handle pretty search URLs for city only like /recherche/ville/city
     */
    public function prettySearchCityOnly($city = null)
    {
        if ($city !== null) {
            $_GET['city'] = str_replace('-', ' ', $city);
        }
        return $this->home();
    }

    public function home()
    {
        // Rate limiting de base
        require_once COMMON . 'Utils/RateLimiter.php';
        $limiter = new \Utils\RateLimiter();
        if (!$limiter->check('page_home', 120, 60)) {
            http_response_code(429);
            die('Too Many Requests');
        }

        // Initialiser LandingStatsService pour la configuration landing
        // $config = $this->getLandingConfig(); // Removed dependency
        $config = $this->getConfig();
        $title = $config['hero_title'] ?? 'Recherche';

        if ($this->runPlugin) {
            $this->runPlugin->setMainTitle($title);
            $this->runPlugin->setTitleTag($title);
        }

        // Charger les traductions
        \Core\Lang::loadLanguageFile(PLUGINS . 'SearchUi' . DS . 'langs' . DS);

        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('SearchUi', 'home');

        // Configuration du template
        // Configuration du template
        $heroTitle = $config['hero_title'] ?? 'Trouvez un lieu';
        if ($heroTitle === 'FALSE') $heroTitle = 'Trouvez un lieu';
        
        $heroSubtitle = $config['hero_subtitle'] ?? 'Restaurants, cafés, hôtels, services...';
        if ($heroSubtitle === 'FALSE') $heroSubtitle = 'Restaurants, cafés, hôtels, services...';

        $tpl->set('hero_title', \Core\Lang::get($heroTitle));
        $tpl->set('hero_subtitle', \Core\Lang::get($heroSubtitle));
        $tpl->set('default_radius', $config['default_radius'] ?? 5);

        // Traduction des CTAs
        if (isset($config['cta_primary_text'])) $config['cta_primary_text'] = \Core\Lang::get($config['cta_primary_text']);
        if (isset($config['cta_secondary_text'])) $config['cta_secondary_text'] = \Core\Lang::get($config['cta_secondary_text']);
        
        $tpl->set('landing', $config);

        // Stats et Carousel (vieilles pépites aléatoires)
        $randomPois = [];
        try {
            $pdo = $this->getPdo();
            $randFunc = ($pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite') ? 'RANDOM()' : 'RAND()';
            // On prend 12 POIs aléatoires avec images pour le carousel/grille de découverte
            $stmt = $pdo->query("SELECT s.poi_id as id, p.name, t.value_text as logo, p.category, p.subcategory, 
                                        p.addr_housenumber, p.addr_street, p.addr_postcode, p.addr_city, 
                                        COALESCE(s.lat, 0) as lat, COALESCE(s.lon, 0) as lon
                                 FROM poi_search s 
                                 INNER JOIN poi_public p ON s.poi_id = p.id 
                                 LEFT JOIN poi_tags t ON t.poi_id = p.id AND t.key_name = 'logo'
                                 WHERE s.status = 'public' 
                                 ORDER BY {$randFunc} LIMIT 12");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $translator = new CategoryLabelTranslator();
            $categoryService = new PoiCategoryService($pdo);
            $totalPois = count($rows);
            foreach ($rows as $index => $row) {
                $poi = PoiDataFormatter::format($row, $translator, true, $categoryService);
                
                // Add slider specific variables (0-indexed targets for CSSgal)
                $poi['slide_id'] = $index + 1;
                $poi['slide_prev'] = ($index === 0) ? $totalPois : $index;
                $poi['slide_next'] = ($index === $totalPois - 1) ? 1 : ($index + 2);
                
                $randomPois[] = $poi;
            }
        } catch (\Exception $e) {
            $this->logger->log(\Core\Logger::LEVEL_ERROR, "Error fetching random pois: " . $e->getMessage());
        }

        $tpl->set('randomPois', $randomPois);
        $tpl->set('hasRandomPois', !empty($randomPois));

        // Top 5 des Pépites les plus actives (basé sur last_activity_at et logo)
        $aggloFile = COMMON . 'data/agglo.json';
        $citiesCondition = "";
        if (file_exists($aggloFile)) {
            $json = json_decode(file_get_contents($aggloFile), true);
            $cities = $json['communes'] ?? [];
            if (!empty($cities)) {
                $quotedCities = array_map(function($c) use ($pdo) { return $pdo->quote($c); }, $cities);
                $citiesCondition = "AND p.addr_city IN (" . implode(',', $quotedCities) . ")";
            }
        }

        $topPois = [];
        try {
            $stmt = $pdo->query("SELECT s.poi_id as id, p.name, t.value_text as logo, p.category, p.subcategory, 
                                        p.addr_housenumber, p.addr_street, p.addr_postcode, p.addr_city, 
                                        COALESCE(s.lat, 0) as lat, COALESCE(s.lon, 0) as lon,
                                        p.last_activity_at
                                 FROM poi_search s 
                                 INNER JOIN poi_public p ON s.poi_id = p.id 
                                 LEFT JOIN poi_tags t ON t.poi_id = p.id AND t.key_name = 'logo'
                                 WHERE s.status = 'public' 
                                 $citiesCondition
                                 ORDER BY p.last_activity_at DESC, s.poi_id DESC 
                                 LIMIT 5");

            $topRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($topRows as $index => $row) {
                $item = PoiDataFormatter::format($row, $translator, true, $categoryService);
                $item['rank'] = (int)($index + 1);
                
                // Debug log to trace coordinate issues
                $this->logger->log(\Core\Logger::LEVEL_DEBUG, "[ACCORDION_DEBUG] POI " . $item['id'] . " Name: " . $item['name'] . " Lat: " . $item['lat'] . " Lon: " . $item['lon']);
                
                if (!empty($item['logo'])) {
                    if (strpos($item['logo'], 'http') !== 0 && strpos($item['logo'], 'common/') !== 0 && strpos($item['logo'], '/') !== 0) {
                        $item['logo'] = '/' . $item['logo'];
                    }
                } else {
                    $item['logo'] = '/theme/LumiereV2/header.jpg';
                }
                
                $topPois[] = $item;
            }
            // [MOD] Fallback : si on n'a pas 5 POIs actifs, on complète avec des aléatoires
            if (count($topPois) < 5) {
                $needed = 5 - count($topPois);
                $existingIds = array_column($topRows, 'id');
                $idList = empty($existingIds) ? '0' : implode(',', $existingIds);
                
                // Mêmes critères mais orderBy RAND() au lieu de last_activity
                $fallbackStmt = $pdo->query("SELECT s.poi_id as id, p.name, t.value_text as logo, p.category, p.subcategory, 
                                            p.addr_housenumber, p.addr_street, p.addr_postcode, p.addr_city, 
                                            COALESCE(s.lat, 0) as lat, COALESCE(s.lon, 0) as lon,
                                            p.last_activity_at
                                     FROM poi_search s 
                                     INNER JOIN poi_public p ON s.poi_id = p.id 
                                     LEFT JOIN poi_tags t ON t.poi_id = p.id AND t.key_name = 'logo'
                                     WHERE s.status = 'public' 
                                     AND s.is_closed = 0
                                     $citiesCondition
                                     AND s.poi_id NOT IN ($idList)
                                     ORDER BY {$randFunc} 
                                     LIMIT $needed");
                $fallbackRows = $fallbackStmt->fetchAll(\PDO::FETCH_ASSOC);
                $this->logger->log(\Core\Logger::LEVEL_WARNING, "[DIAG_POIS] Fallback found " . count($fallbackRows) . " POIs.");
                foreach ($fallbackRows as $index => $row) {
                    $item = PoiDataFormatter::format($row, $translator, true, $categoryService);
                    $item['rank'] = (int)(count($topPois) + 1);
                    
                    if (!empty($item['logo'])) {
                        if (strpos($item['logo'], 'http') !== 0 && strpos($item['logo'], '/') !== 0) {
                            $item['logo'] = '/' . $item['logo'];
                        }
                    } else {
                        $item['logo'] = '/theme/LumiereV2/header.jpg';
                    }
                    
                    $topPois[] = $item;
                }
            }
            $this->logger->log(\Core\Logger::LEVEL_WARNING, "[DIAG_POIS] Final topPois count: " . count($topPois) . ". First rank: " . ($topPois[0]['rank'] ?? 'N/A'));
        } catch (\Exception $e) {
            $this->logger->log(\Core\Logger::LEVEL_ERROR, "Error fetching top pois: " . $e->getMessage());
        }
        
        $tpl->set('topPois', $topPois);
        $tpl->set('hasTopPois', !empty($topPois));

        // Sections Dynamiques : Forum, Blog, Offres, Agenda

        // [FIX] Inject Extra Home Sections via Hook and set isLoggedIn
        // Check if user is logged in AND not a guest
        $isLoggedIn = ($this->user !== null && $this->user->getRole() !== 'guest');
        $tpl->set('isLoggedIn', $isLoggedIn);

        $hookResult = $this->core->callHook('SearchUi:home-sections', [
            'sections' => [],
            'isLoggedIn' => $isLoggedIn,
            'user' => $this->user
        ]);
        $tpl->set('extraHomeSections', $hookResult['sections'] ?? []);

        // Vérification si l'utilisateur possède au moins un POI (pour les CTAs marketing)
        $userHasPoi = false;
        if ($isLoggedIn && $this->user) {
            try {
                $pdo = $this->getPdo();
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM poi_public WHERE owner_id = ? AND status = 'published'");
                $stmt->execute([$this->user->getId()]);
                $userHasPoi = ($stmt->fetchColumn() > 0);
            } catch (\Exception $e) {}
        }
        $tpl->set('userHasPoi', $userHasPoi);


        $latestBlog = [];
        if (\Core\Plugin\PluginsManager::isActivePlugin('blog')) {
            $blogData = $this->core->callHook('blog:get-latest-post', ['latest_post' => []]);
            $latestBlog = $blogData['latest_post'] ?? [];
        }
        $tpl->set('latestBlog', $latestBlog);

        $latestForum = [];
        if (\Core\Plugin\PluginsManager::isActivePlugin('forum') && class_exists('\Forum\Entities\Post')) {
            try {
                $posts = \Forum\Entities\Post::query()->orderBy('created_at', 'DESC')->limit(5)->get();
                foreach ($posts as $post) {
                    $topic = \Forum\Entities\Topic::findPK($post->topic_id);
                    $latestForum[] = [
                        'topic_title' => $topic ? $topic->title : 'Sujet inconnu',
                        'content' => substr(strip_tags($post->content), 0, 100) . '...',
                        'date' => date('d/m/Y', strtotime($post->created_at)),
                        'url' => $this->router->generate('forum-topic', ['id' => $post->topic_id, 'slug' => ($topic ? $topic->slug : 'topic')]) . '#p' . $post->id
                    ];
                }
            } catch (\Exception $e) {}
        }
        $tpl->set('latestForum', $latestForum);

        $latestOffers = [];
        if (\Core\Plugin\PluginsManager::isActivePlugin('offers') && class_exists('\Offers\Entities\OfferManager')) {
            try {
                $offerManager = new \Offers\Entities\OfferManager();
                $offers = $offerManager->getAll('approved', true);
                $latestOffers = array_slice($offers, 0, 5);
                // Format for template
                foreach ($latestOffers as &$off) {
                    $off->poi = \PoiData\Entities\Poi::findPK($off->poi_id);
                }
            } catch (\Exception $e) {}
        }
        $tpl->set('latestOffers', $latestOffers);

        $latestEvents = [];
        if (\Core\Plugin\PluginsManager::isActivePlugin('agenda') && class_exists('\Agenda\Entities\EventManager')) {
            try {
                $eventManager = new \Agenda\Entities\EventManager();
                $events = $eventManager->getAll(false, true); // Future, Approved
                $latestEvents = array_slice($events, 0, 5);
            } catch (\Exception $e) {}
        }
        $tpl->set('latestEvents', $latestEvents);
           
     
        try {
            $pdo = $this->getPdo();
            
            // Charger les traductions du plugin (pour les familles/spécialités)
            \Core\Lang::loadLanguageFile(PLUGINS . 'SearchUi/langs/');

            $mappingService = new \SearchEngine\Services\Search\Category\CategoryMappingService();
            $families = $mappingService->getFamilies();
            $familyTranslations = [];
            foreach ($families as $key => $f) {
                $familyTranslations[$key] = \Core\Lang::get($f['label']);
            }
            $tpl->set('family_translations', $familyLabels ?? $familyTranslations); // Consistency Fix
            $tpl->set('family_translations_json', json_encode($familyTranslations));
            
            // 1. Charger les communes de l'agglo
            $communeService = new CommuneService();
            $aggloCommunes = $communeService->loadAggloCommunes();
            $centroids = $communeService->getCommuneCentroids($this->getConfig(), $aggloCommunes);
            
            // --- GESTION DES FILTRES ---
            $cityParam = $_GET['city'] ?? '';
            $familyParam = $_GET['family'] ?? '';
            $categoryParam = $_GET['category'] ?? '';
            $queryParam = trim($_GET['query'] ?? $_GET['q'] ?? '');
            
            if (!empty($familyParam) && ($translated = $this->translateSlugToKey($familyParam)) !== null) {
                $familyParam = $translated;
            }
            if (!empty($categoryParam) && ($translated = $this->translateSlugToKey($categoryParam)) !== null) {
                $categoryParam = $translated;
            }

            // Si category est spécifiée et correspond à une famille connue (ex: category=commerce, category=services...)
            if (empty($familyParam) && !empty($categoryParam)) {
                $families = (new \SearchEngine\Services\Search\Category\CategoryMappingService())->getFamilies();
                if (isset($families[$categoryParam])) {
                    $familyParam = $categoryParam;
                    $categoryParam = '';
                }
            }

            $subcategoryParam = $_GET['subcategory'] ?? '';
            $limitParam = (int)($_GET['limit'] ?? 20);
            $offsetParam = (int)($_GET['offset'] ?? 0);
            $userLat = (isset($_GET['user_lat']) && is_numeric($_GET['user_lat']) && (float)$_GET['user_lat'] !== 0.0) ? (float)$_GET['user_lat'] : null;
            $userLon = (isset($_GET['user_lon']) && is_numeric($_GET['user_lon']) && (float)$_GET['user_lon'] !== 0.0) ? (float)$_GET['user_lon'] : null;
            
            // Fallback cookie : si pas de coords dans l'URL, lire le cookie défini par le JS
            // Permet d'avoir les coords dès le premier rendu serveur (sans aller-retour HTMX)
            if ($userLat === null && $userLon === null && !empty($_COOKIE['user_geoloc'])) {
                $cookieParts = explode(',', (string)$_COOKIE['user_geoloc']);
                if (count($cookieParts) === 2 && is_numeric($cookieParts[0]) && is_numeric($cookieParts[1])) {
                    $cookieLat = (float)$cookieParts[0];
                    $cookieLon = (float)$cookieParts[1];
                    // Sanity check : coordonnées plausibles (France)
                    if ($cookieLat >= 41.0 && $cookieLat <= 51.5 && $cookieLon >= -5.5 && $cookieLon <= 10.0) {
                        $userLat = $cookieLat;
                        $userLon = $cookieLon;
                    }
                }
            }
            $aroundMeParam = !empty($_GET['around_me']);
            $onlyOpenParam = !empty($_GET['only_open']);
            $claimedParam = !empty($_GET['claimed']);
            $radiusParam = (isset($_GET['radius']) && is_numeric($_GET['radius'])) ? (float)$_GET['radius'] : 5.0;

            if ($limitParam <= 0) $limitParam = 20;
            if ($offsetParam < 0) $offsetParam = 0;
            if ($radiusParam <= 0) $radiusParam = 5.0;

            // OPTIMIZATION: Skip stats calculation for infinite scroll requests (HTMX + offset > 0)
            $isInfiniteScroll = isset($_SERVER['HTTP_HX_REQUEST']) && $offsetParam > 0;
            
            if (!$isInfiniteScroll) {
                // 2. Définir le bounding box de l'agglo (Bourges Plus)
                $minLat = 46.85; $maxLat = 47.25;
                $minLon = 2.15;  $maxLon = 2.65;
                
                // 3. Récupérer uniquement les POIs dans le secteur (Performance++)
                $sqlBase = "FROM poi_search s 
                            INNER JOIN poi_public p ON s.poi_id = p.id 
                            WHERE s.status = 'public' AND s.is_closed = 0
                            AND s.lat BETWEEN $minLat AND $maxLat
                            AND s.lon BETWEEN $minLon AND $maxLon";

                $stmt = $pdo->query("SELECT s.poi_id, s.lat, s.lon, s.category, s.subcategory $sqlBase");
                $allPoisRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $cityCounts = [];
                $familyCounts = [];
                $mappingService = new \SearchEngine\Services\Search\Category\CategoryMappingService();
                $familiesCount = $mappingService->getFamilies();

                foreach ($aggloCommunes as $city) $cityCounts[$city] = 0;
                foreach ($familiesCount as $key => $f) $familyCounts[$key] = 0;

                $totalPoiAgglo = 0;
                $specialtyCounts = [];

                foreach ($allPoisRaw as $poi) {
                    $pLat = (float)$poi['lat'];
                    $pLon = (float)$poi['lon'];
                    $bestCity = null;
                    $minDistSq = 999999;
                    
                    foreach ($centroids as $cityName => $c) {
                        $dLat = $pLat - (float)$c['lat'];
                        $dLon = $pLon - (float)$c['lon'];
                        $distSq = ($dLat * $dLat) + ($dLon * $dLon);
                        
                        if ($distSq < $minDistSq) {
                            $minDistSq = $distSq;
                            $bestCity = $cityName;
                        }
                    }
                    
                    if ($bestCity && $minDistSq < 0.02) { 
                        $familyData = $mappingService->mapToFamily($poi['category'] ?? '', $poi['subcategory'] ?? '');
                        
                        if (!empty($familyData) && isset($familyData['key'])) {
                            $familyKey = $familyData['key'];
                            $matchProximity = true;
                            if ($aroundMeParam && $userLat && $userLon) {
                                $dLat = $pLat - $userLat;
                                $dLon = $pLon - $userLon;
                                $distKmSq = pow($dLat * 111.12, 2) + pow($dLon * 75.77, 2);
                                $matchProximity = ($distKmSq <= pow($radiusParam, 2));
                            }

                            $totalPoiAgglo++;

                            if ($matchProximity && ($familyParam === '' || $familyParam === $familyKey)) {
                                 $cityCounts[$bestCity]++;
                            }
                            
                            if ($matchProximity && ($cityParam === '' || $cityParam === $bestCity)) {
                                if (isset($familyCounts[$familyKey])) {
                                    $familyCounts[$familyKey]++;
                                    if (!empty($poi['subcategory'])) {
                                        $subKey = $poi['subcategory'];
                                        if (!isset($specialtyCounts[$familyKey])) $specialtyCounts[$familyKey] = [];
                                        if (!isset($specialtyCounts[$familyKey][$subKey])) $specialtyCounts[$familyKey][$subKey] = 0;
                                        $specialtyCounts[$familyKey][$subKey]++;
                                    }
                                }
                            }
                        }
                    }
                }

                $tpl->set('total_poi_agglo', $totalPoiAgglo);
                $tpl->set('total_poi_agglo_formatted', number_format($totalPoiAgglo, 0, ',', ' '));

                $tplCities = [];
                foreach ($cityCounts as $name => $count) {
                    if ($count > 0) {
                        $tplCities[] = ['name' => $name, 'count' => $count, 'count_formatted' => number_format($count, 0, ',', ' ')];
                    }
                }
                usort($tplCities, fn($a, $b) => $b['count'] <=> $a['count']);
                $tpl->set('agglo_cities', $tplCities);
                $tpl->set('agglo_cities_count', count($tplCities));

                $translator = new CategoryLabelTranslator();
                $tplActivities = [];
                foreach ($familiesCount as $key => $f) {
                    $count = $familyCounts[$key] ?? 0;
                    $chipParams = [
                        'family' => $key,
                        'city' => $cityParam,
                        'user_lat' => $userLat,
                        'user_lon' => $userLon,
                        'radius' => $radiusParam,
                        'around_me' => $aroundMeParam ? '1' : '',
                    ];
                    
                    $tplActivities[] = [
                        'key' => $key,
                        'label' => \Core\Lang::get($f['label']),
                        'icon' => $f['icon'],
                        'count' => $count,
                        'count_formatted' => number_format($count, 0, ',', ' '),
                        'filter_url' => $this->buildPrettySearchUrl($chipParams)
                    ];
                }
                usort($tplActivities, fn($a, $b) => $b['count'] <=> $a['count']);
                $tpl->set('activities', $tplActivities);

                $tplSubcategories = [];
                if (!empty($familyParam)) {
                    $specialties = $mappingService->getFiltresForFamily($familyParam);
                    foreach ($specialties as $spec) {
                         $specKey = $spec['key'];
                         $specCount = $specialtyCounts[$familyParam][$specKey] ?? 0;
                         $subChipParams = [
                             'family' => $familyParam,
                             'subcategory' => $specKey,
                             'city' => $cityParam,
                             'user_lat' => $userLat,
                             'user_lon' => $userLon,
                             'radius' => $radiusParam,
                             'around_me' => $aroundMeParam ? '1' : '',
                         ];
                         
                         $tplSubcategories[] = [
                            'key' => $specKey,
                            'label' => $translator->translate($specKey),
                            'count' => $specCount,
                            'filter_url' => $this->buildPrettySearchUrl($subChipParams)
                         ];
                    }
                     usort($tplSubcategories, fn($a, $b) => $b['count'] <=> $a['count']);
                }
                $tpl->set('family_subcategories', $tplSubcategories);
            }

            // Charger les résultats (TOUJOURS, car c'est ce qu'on renvoie en pagination)
            $searchService = new SearchService($this->getConfig());
            $results = $searchService->search(
                $queryParam,        // query (query ou q)
                $userLat,           // lat
                $userLon,           // lon
                $radiusParam,       // radius
                $categoryParam,     // category
                $subcategoryParam,  // subcategory
                $cityParam,         // city
                $limitParam,        // limit
                $offsetParam,       // offset
                'relevance',        // sort
                null,               // filterCity
                null,               // filterStars
                $centroids,         // centroids (agglo only)
                $familyParam,       // family
                true,               // onlyCategorized (Strict)
                $aroundMeParam,      // aroundMe (New)
                $claimedParam       // isClaimed
            );

            // Formater pour poi_list.tpl
            $translator = new CategoryLabelTranslator();
            $categoryService = new PoiCategoryService($pdo);

            $formattedPois = [];
            $currentIndex = $offsetParam + 1;
            foreach (($results['results'] ?? []) as $row) {
                $poi = PoiDataFormatter::format($row, $translator, true, $categoryService);
                
                // [FIX] Inject family for map coloring
                $familyData = $mappingService->mapToFamily($row['category'] ?? '', $row['subcategory'] ?? '');
                $poi['family'] = $familyData['key'] ?? 'other';
                
                // Ajouter la distance si disponible
                if (isset($row['distance_km']) && $row['distance_km'] !== null) {
                    $poi = PoiDataFormatter::addDistance($poi, (float)$row['distance_km']);
                }
                
                $poi['global_index'] = $currentIndex++;
                $formattedPois[] = $poi;
            }

            $tpl->set('pois', $formattedPois);
            $tpl->set('pois_json', json_encode($formattedPois));
            $tpl->set('total_results', $results['total'] ?? 0);
            $tpl->set('total', $results['total'] ?? 0);
            $tpl->set('filtered_results_count', number_format($results['total'] ?? 0, 0, ',', ' '));
            $tpl->set('total_poi', $results['total'] ?? 0);
            $tpl->set('limit', $limitParam);
            $tpl->set('offset', $offsetParam); 
            $tpl->set('currentPage', floor($offsetParam / $limitParam) + 1);
            $tpl->set('totalPages', ceil(($results['total'] ?? 0) / $limitParam));
            $tpl->set('hasNext', ($offsetParam + $limitParam) < $results['total']);
            $tpl->set('hasPrev', $offsetParam > 0);
            $tpl->set('nextOffset', $offsetParam + $limitParam);
            $tpl->set('prevOffset', max(0, $offsetParam - $limitParam));

            // Passer les sélections au template pour les liens de pagination
            $tpl->set('selected_city', $cityParam);
            $tpl->set('selected_family', $familyParam);
            $tpl->set('selected_category', $categoryParam);
            $tpl->set('selected_subcategory', $subcategoryParam);
            $tpl->set('user_lat', $userLat);
            $tpl->set('user_lon', $userLon);
            $tpl->set('around_me', $aroundMeParam);
            $tpl->set('claimed', $claimedParam);
            $tpl->set('only_open', $onlyOpenParam);
            $tpl->set('radius', $radiusParam);
            $tpl->set('query', $queryParam);
            
            // [FIX] Persist filters open state
            $filtersOpenParam = !empty($_GET['filters_open']);
            $tpl->set('filters_open', $filtersOpenParam);

            // Traductions pour les badges
            if ($familyParam && isset($familyTranslations[$familyParam])) {
                $tpl->set('selected_family_label', $familyTranslations[$familyParam]);
            }
            if ($subcategoryParam) {
                $translator = new \PoiData\Services\CategoryLabelTranslator();
                $tpl->set('selected_subcategory_label', $translator->translate($subcategoryParam));
            }

            // Build pretty URLs for pagination and filters
            $currentParams = [
                'category' => $categoryParam,
                'family' => $familyParam,
                'city' => $cityParam,
                'subcategory' => $subcategoryParam,
                'user_lat' => $userLat,
                'user_lon' => $userLon,
                'around_me' => $aroundMeParam ? '1' : '',
                'radius' => $radiusParam,
                'only_open' => $onlyOpenParam ? '1' : '',
                'claimed' => $claimedParam ? '1' : '',
                'limit' => $limitParam,
                'query' => $_GET['query'] ?? ''
            ];
            
            // Previous page URL
            if ($offsetParam > 0) {
                $prevParams = $currentParams;
                $prevParams['offset'] = max(0, $offsetParam - $limitParam);
                $tpl->set('prevPageUrl', $this->buildPrettySearchUrl($prevParams));
            }
            
            // Next page URL
            if (($offsetParam + $limitParam) < $results['total']) {
                $nextParams = $currentParams;
                $nextParams['offset'] = $offsetParam + $limitParam;
                $tpl->set('nextPageUrl', $this->buildPrettySearchUrl($nextParams));
                $tpl->set('hasNext', true);
            } else {
                $tpl->set('hasNext', false);
            }
            
            // Base URL for HTMX filter changes (no offset)
            $baseParams = $currentParams;
            unset($baseParams['offset']); // Remove offset for filter changes
            $tpl->set('baseSearchUrl', $this->buildPrettySearchUrl($baseParams));

            // --- UNIVERSAL POLICY INTEGRATION (Phase 10) ---
            $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
            $ctx = new \Core\Navigation\ConditionContext(\Core\Auth\UsersManager::getCurrentUser());
            
            $tpl->set('IS_LOGGED', $evaluator->canAccess(new \Core\Navigation\GenericResource('search-ui:logged'), $ctx));
            $tpl->set('CAN_ADMIN_TOOLS', $evaluator->canAccess(new \Core\Navigation\GenericResource('system:admin-tools'), $ctx));
            $tpl->set('loginUrl', $this->router->generate('login'));

        } catch (\Exception $e) {
            $this->logger->log(\Core\Logger::LEVEL_ERROR, 'HomeController::home - Erreur: ' . $e->getMessage());
            $tpl->set('total_poi', 0);
            $tpl->set('pois', []);
        }

        // Auth status
        $user = \Core\Auth\UsersManager::getCurrentUser();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $isLoggedIn = $evaluator->canAccess(new \Core\Navigation\GenericResource('search-ui:logged'), new \Core\Navigation\ConditionContext($user));
        $tpl->set('isLoggedIn', $isLoggedIn);

        // Extra sections (ex: Blog, Promotions, etc.)
        $extraData = $this->core->callHook('SearchUi:home-sections', [
            'sections' => [],
            'isLoggedIn' => $isLoggedIn,
            'user' => $user,
            'evaluator' => \Core\Navigation\AccessPolicyEvaluator::getInstance() // Inject evaluator for hooks
        ]);
        $tpl->set('extraHomeSections', $extraData['sections'] ?? []);

        // Inscription link
        try {
            $tpl->set('registerLink', $this->router->generate('register'));
        } catch (\Exception $e) {
            $tpl->set('registerLink', '/users/register');
        }


        // Pass URL builder fonction to template for pretty URLs
        // [HTMX] Si c'est une requête infinite scroll, on ne renvoie que la liste
        if (isset($_SERVER['HTTP_HX_REQUEST']) && isset($_GET['offset']) && (int)$_GET['offset'] > 0) {
            $htmxResponse = new HtmxResponse();
            $listTpl = $htmxResponse->createPluginTemplate('SearchUi', 'partials/poi_list');
            // On réinitialise les variables pour le partial en utilisant getData()
            foreach ($tpl->getData() as $k => $v) {
                $listTpl->set($k, $v);
            }
            // On injecte un flag pour init les maps dans le partial si nécessaire
            $listTpl->set('hx_init_maps', true);

            $htmxResponse->addTemplate($listTpl);
            return $htmxResponse;
        }

        $response->setTitle($title);
        $response->setMetaDescription(\Core\Lang::get('search-meta-description') ?: "Découvrez les meilleurs lieux, commerces et services à Bourges et ses environs sur vivre-bourges.fr.");
        $response->addTemplate($tpl);

        return $response;
    }

    /**
     * Build a pretty search URL with main parameters in the path and secondary ones as query string
     * @param array $params Associative array of search parameters
     * @return string Pretty URL like /recherche/category/city?subcategory=xxx&radius=10
     */
    private function buildPrettySearchUrl(array $params = []): string
    {
        $siteUrl = Core::getInstance()->getConfigVal('siteUrl');
        $category = $params['category'] ?? ($params['family'] ?? null);
        $subcategory = $params['subcategory'] ?? null;
        $city = $params['city'] ?? null;
        
        // Convert internal keys to translated slugs
        $categorySlug = $this->translateKeyToSlug($category);
        $subcategorySlug = $this->translateKeyToSlug($subcategory);
        
        // Build the base path
        $path = '/recherche';
        if ($categorySlug) {
            $path .= '/' . urlencode($categorySlug);
            if ($subcategorySlug) {
                $path .= '/' . urlencode($subcategorySlug);
            }
            if ($city) {
                $path .= '/' . urlencode(str_replace(' ', '-', $city));
            }
        } elseif ($city) {
            $path .= '/ville/' . urlencode(str_replace(' ', '-', $city));
        }
        
        // Collect secondary parameters for query string
        $secondaryParams = [];
        $excludedKeys = ['category', 'family', 'city', 'subcategory']; // These go in the path
        
        foreach ($params as $key => $value) {
            if (!in_array($key, $excludedKeys) && !empty($value) && $value !== '' && $value !== 0 && $value !== '0') {
                // Skip zero coordinates (not set)
                if (($key === 'user_lat' || $key === 'user_lon') && (float)$value === 0.0) {
                    continue;
                }
                $secondaryParams[$key] = $value;
            }
        }
        
        // Build query string if there are secondary parameters
        if (!empty($secondaryParams)) {
            $path .= '?' . http_build_query($secondaryParams);
        }
        
        return $siteUrl . $path;
    }

    /**
     * Map an internal key (OSM/Database) to a translated slug for the URL
     */
    private function translateKeyToSlug(?string $key): ?string
    {
        if (empty($key)) return null;
        
        $translator = new CategoryLabelTranslator();
        $label = $translator->translate($key);
        
        // Si la traduction a échoué (retourne la clé elle-même capitalisée), on utilise la clé
        if (strtolower($label) === strtolower(str_replace('_', ' ', $key))) {
            return Util::strToUrl($key);
        }
        
        return Util::strToUrl($label);
    }

    /**
     * Map a translated slug from the URL back to an internal key
     */
    private function translateSlugToKey(?string $slug): ?string
    {
        if (empty($slug)) return null;
        
        // [FIX] Hardcoded mapping for landing page slugs
        $landingMapping = [
            'restauration-bar' => 'food',
            'restaurant' => 'food',
            'restaurants' => 'food',
            'commerces' => 'shops',
            'commerce' => 'shops',
            'shop' => 'shops',
            'shops' => 'shops',
            'sante' => 'health',
            'sante-soins' => 'health',
            'loisirs-sport' => 'leisure',
            'sport-loisirs' => 'leisure',
            'sports-loisirs' => 'leisure',
            'patrimoine' => 'heritage',
            'culture-patrimoine' => 'heritage',
            'nature' => 'nature',
            'nature-espaces-verts' => 'nature',
            'services-publics' => 'services',
            'services' => 'services',
            'administration-services-publics' => 'services',
        ];

        if (isset($landingMapping[$slug])) {
            return $landingMapping[$slug];
        }

        $allTranslations = CategoryLabelTranslator::getAllTranslations();
        $targetSlug = strtolower($slug);
        
        // 1. Essai direct (si le slug est déjà la clé)
        if (isset($allTranslations[$slug])) return $slug;
        $slugWithUnderscore = str_replace('-', '_', $targetSlug);
        if (isset($allTranslations[$slugWithUnderscore])) return $slugWithUnderscore;

        // 2. Recherche par slugification des labels français
        foreach ($allTranslations as $key => $label) {
            if (Util::strToUrl($label) === $targetSlug) {
                return $key;
            }
        }
        
        // 3. Fallback : clé brute
        return $slugWithUnderscore;
    }

    /**
     * Récupère la config landing via ThemeServices ou fallback
     */
   // private function getLandingConfig(): array
    //{
      //  try {
            //if (class_exists('\ThemeServices\Services\LandingStatsService')) {
                //$statsCache = class_exists('\ThemeServices\Services\StatsCache') ? new \ThemeServices\Services\StatsCache() : null;
              //  $landingService = new LandingStatsService($this->getPdo(), $statsCache, \Core\Plugin\PluginsManager::getInstance());
            //    return $landingService->getLandingConfig();
          //  }
        //} catch (\Exception $e) {
        //}
      //  return $this->getConfig();
    //}




    /**
     * Récupère le dernier article de blog (Déprécié - Utiliser le hook blog:get-latest-post)
     */
    public function getLatestBlogPost()
    {
        return null;
    }

    /**
     * HTMX : Retourne le HTML des POIs d'une catégorie (Famille) ou d'une sous-catégorie
     * NOTE: Pas de géolocalisation GPS utilisateur - uniquement filtrage par ville
     */
    public function getSubcategoryPoisHtml()
    {
        error_log("HTMX DEBUG: getSubcategoryPoisHtml params: " . json_encode($_GET));

        $family = $_GET['family'] ?? '';
        $category = $_GET['category'] ?? '';
        $subcategory = $_GET['subcategory'] ?? '';
        $subcategoriesList = !empty($_GET['subcategories']) ? (is_array($_GET['subcategories']) ? implode(',', $_GET['subcategories']) : $_GET['subcategories']) : $subcategory;

        $city = $_GET['city'] ?? '';
        $limit = (int) ($_GET['limit'] ?? 20); // 20 par défaut pour la pagination
        $offset = (int) ($_GET['offset'] ?? 0);
        $starsMin = (int) ($_GET['stars_min'] ?? 0);
        $onlyOpen = !empty($_GET['only_open']);

        if (empty($family) && empty($category) && empty($subcategory)) {
            return '';
        }

        $config = $this->getConfig();
        $service = new SearchService($config);

        // Charger centroids des communes de l'agglo
        $communeService = new CommuneService();
        $communeCentroids = null;

        if (empty($city)) {
            // Mode agglo : charger tous les centroids
            $communeCentroids = $communeService->loadAggloCentroids($config);
        } else {
            // Mode ville : charger uniquement le centroid de cette ville
            $communeCentroids = $communeService->getCommuneCentroids($config, [$city]);
        }

        // Utiliser le cache UNIQUEMENT si pas de filtres spécifiques
        $cacheService = new CacheJsonService($this->getPdo());
        $useCache = ($offset === 0 && $starsMin === 0 && !$onlyOpen && empty($_GET['query']) && empty($_GET['subcategories']));

        if ($useCache) {
            $cacheData = $cacheService->getSubcategoryPois($category, $subcategory, function () use ($service, $category, $subcategory, $city, $limit, $communeCentroids, $family, $subcategoriesList) {
                $results = $service->search(
                    '',               // text
                    null,             // lat
                    null,             // lon
                    null,             // radiusKm
                    $category,        // osm category
                    $subcategoriesList, // osm subcategory (filtered)
                    $city,            // city
                    $limit,           // limit
                    0,                // offset
                    'relevance',      // sort
                    null,             // filterCity
                    null,             // filterStars
                    $communeCentroids, // geographic filtering strict
                    $family           // NEW: Family UI filtering
                );
                return ['pois' => $results['results'] ?? [], 'total' => $results['total'] ?? 0];
            }, null, $city);
        } else {
            // Pas de cache pour les recherches filtrées ou paginées
            $results = $service->search(
                '',               // text
                null,             // lat
                null,             // lon
                null,             // radiusKm
                $category,        // osm category
                $subcategoriesList, // osm subcategory (filtered)
                $city,            // city
                $limit,           // limit
                $offset,          // offset
                'relevance',      // sort
                null,             // filterCity
                $starsMin > 0 ? $starsMin : null,
                $communeCentroids,
                $family
            );
            $cacheData = ['pois' => $results['results'] ?? [], 'total' => $results['total'] ?? 0];
        }

        $pois = $cacheData['pois'] ?? [];

        // Préparer les données pour le template
        $translator = new CategoryLabelTranslator();
        $pdo = $this->getPdo();
        $categoryService = new PoiCategoryService($pdo);
        $mappingService = new \SearchEngine\Services\Search\Category\CategoryMappingService();

        $formattedPois = [];
        foreach ($pois as $row) {
            $poi = PoiDataFormatter::format($row, $translator, true, $categoryService);

            // [FIX] Inject family for map coloring
            $familyData = $mappingService->mapToFamily($row['category'] ?? '', $row['subcategory'] ?? '');
            $poi['family'] = $familyData['key'] ?? 'other';

            // S'assurer que la distance est prise en compte si non présente dans PoiDataFormatter::format
            if (!isset($poi['distance_formatted']) || $poi['distance_formatted'] === null) {
                if (isset($row['distance_km']) || isset($row['distance'])) {
                    $dist = $row['distance_km'] ?? $row['distance'];
                    $poi = PoiDataFormatter::addDistance($poi, (float) $dist);
                }
            }

            $formattedPois[] = $poi;
        }

        // Rendre le template partiel
        $response = new \Core\Responses\HtmxResponse();
        $tpl = $response->createPluginTemplate('SearchUi', 'partials/poi_list');
        // Logic Pagination
        $prevOffset = ($offset - $limit) >= 0 ? ($offset - $limit) : null;
        $nextOffset = ($offset + $limit) < ($cacheData['total'] ?? 0) ? ($offset + $limit) : null;

        $tpl->set('prevOffset', $prevOffset);
        $tpl->set('nextOffset', $nextOffset);
        $tpl->set('hasPrev', $prevOffset !== null);
        $tpl->set('hasNext', $nextOffset !== null);

        $tpl->set('totalPages', $limit > 0 ? ceil(($cacheData['total'] ?? 0) / $limit) : 1);
        $tpl->set('currentPage', $limit > 0 ? floor($offset / $limit) + 1 : 1);

        $tpl->set('pois', $formattedPois);
        $tpl->set('total', $cacheData['total'] ?? 0);
        $tpl->set('offset', $offset);
        $tpl->set('limit', $limit);
        $tpl->set('only_open', $onlyOpen);
        $tpl->set('stars_min', $starsMin);

        // Charger les spécialités pour cette famille
        $mappingService = new \SearchEngine\Services\Search\Category\CategoryMappingService();
        $specialties = !empty($family) ? $mappingService->getFiltresForFamily($family) : [];
        $tpl->set('specialties', $specialties);
        $tpl->set('selected_subcategories', !empty($subcategoriesList) ? explode(',', $subcategoriesList) : []);
        $tpl->set('family', $family);
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext(\Core\Auth\UsersManager::getCurrentUser());

        // Ajouter les flags système via le moteur de politique
        $tpl->set('IS_LOGGED', $evaluator->canAccess(new \Core\Navigation\GenericResource('search-ui:logged'), $ctx));
        $tpl->set('CAN_EDIT_POIS', $evaluator->canAccess(new \Core\Navigation\GenericResource('poi:edit'), $ctx)); // Note: generic check here
        $tpl->set('isClaimActive', \Core\Plugin\PluginsManager::isActivePlugin('claim'));
        $tpl->set('isPoiDeditActive', \Core\Plugin\PluginsManager::isActivePlugin('poi-edit'));
        $tpl->set('loginUrl', $this->router->generate('login'));

        $response->addTemplate($tpl);

        return $response;
    }

    /**
     * HTMX : Retourne le HTML des catégories filtrées par ville
     */
    public function getCategoriesByCityHtml()
    {
        $cityName = $_GET['city'] ?? '';

        $config = $this->getConfig();
        $service = new SearchService($config);
        $result = $service->getCategoriesByCity($cityName);

        $categoriesRaw = $result['categories'] ?? [];
        $categories = $this->prepareCategories($categoriesRaw);

        $response = new \Core\Responses\HtmxResponse();
        $tpl = $response->createPluginTemplate('SearchUi', 'partials/category_chips');
        $tpl->set('categories', $categories);
        $tpl->set('city', $cityName);
        $response->addTemplate($tpl);

        return $response;
    }

    /**
     * HTMX : Retourne le HTML des sous-catégories d'une catégorie
     */
    public function getSubcategoriesHtml()
    {
        $category = $_GET['category'] ?? '';
        $cityName = $_GET['city'] ?? '';

        // Logger l'appel pour debug 404
        error_log("HTMX Call: getSubcategoriesHtml(category=$category, city=$cityName)");

        // Utiliser une HtmxResponse pour éviter le layout global
        $response = new \Core\Responses\HtmxResponse();

        if (empty($category)) {
            // Renvoie une réponse vide si pas de catégorie
            return $response;
        }

        $config = $this->getConfig();
        $service = new SearchService($config);
        $result = $service->getCategoriesByCity($cityName);

        $subcategories = [];
        if (isset($result['categories'][$category])) {
            $subcategories = $result['categories'][$category]['subcategories'] ?? [];
        }

        if (empty($subcategories)) {
            error_log("HTMX: Aucune sous-catégorie trouvée pour cat=$category dans city=$cityName");
        }

        $tpl = $response->createPluginTemplate('SearchUi', 'partials/subcategory_chips');
        $tpl->set('subcategories', $this->prepareSubcategories($subcategories));
        $tpl->set('category', $category);
        $tpl->set('category_encoded', urlencode($category));
        $tpl->set('city', $cityName);
        $response->addTemplate($tpl);

        return $response;
    }

    private function prepareCategories(array $categories): array
    {
        $prepared = [];
        foreach ($categories as $key => $data) {
            // Dans cette version simplifiée, les labels sont déjà traduits par le service
            $data['label_safe'] = htmlspecialchars($data['label'] ?? $key, ENT_QUOTES, 'UTF-8');
            $data['icon_safe'] = !empty($data['icon']) ? $data['icon'] : 'map-marker-alt';
            $data['encoded'] = urlencode($key);
            $data['count_formatted'] = isset($data['count']) ? number_format((int)$data['count'], 0, ',', ' ') : '0';
            
            if (!isset($data['label'])) {
                $data['label'] = $key;
            }

            if (!empty($data['subcategories'])) {
                $data['subcategories'] = $this->prepareSubcategories($data['subcategories'], (string)$key);
            }
            $prepared[(string)$key] = $data;
        }
        return $prepared;
    }

    private function prepareSubcategories(array $subcategories, string $parentCategoryKey = ''): array
    {
        foreach ($subcategories as $key => &$data) {
            $data['label_safe'] = htmlspecialchars($data['label'] ?? $key, ENT_QUOTES, 'UTF-8');
            $data['icon_safe'] = !empty($data['icon']) ? $data['icon'] : 'map-marker-alt';
            $data['encoded'] = urlencode($key);
            $data['count_formatted'] = isset($data['count']) ? number_format($data['count'], 0, ',', ' ') : '0';
        }
        return $subcategories;
    }

    private function translateCategory(string $label): string
    {
        return $label;
    }

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
    public function commentRevendiquer($args)
    {
        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('SearchUi', 'comment-revendiquer');
        $tpl->set('mainTitle', \Core\Lang::get('Comment revendiquer ?'));
        $response->addTemplate($tpl);
        return $response;
    }
}

