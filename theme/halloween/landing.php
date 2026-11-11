<?php
/**
 * Theme VivreBourges - landing.php
 * Préparation des données dynamiques pour landing.tpl
 */

defined('ROOT') or exit('Access denied!');

use Core\Core;
use Core\Router\Router;
use Core\Plugin\PluginsManager;
use Template\Template;

$core = Core::getInstance();
$router = Router::getInstance();
$pluginsManager = PluginsManager::getInstance();

Template::addGlobal('isLandingPage', true);

// Météo Header Dynamique (Plugin WeatherHeader)
$weatherHeaderHtml = $core->callHook('weatherHeroBanner', '');
if (!empty($weatherHeaderHtml)) {
    Template::addGlobal('hasWeatherHero', true);
    Template::addGlobal('weatherHeroHtml', $weatherHeaderHtml);
} else {
    Template::addGlobal('hasWeatherHero', false);
    Template::addGlobal('weatherHeroHtml', '');
}

// Connexion DB
$pdo = null;
try {
    $pdo = \Core\Database::getInstance()->getPdo();
} catch (\Exception $e) {
    $core->getLogger()->log(\Core\Logger::LEVEL_ERROR, "VivreBourges landing: " . $e->getMessage());
}

// LocalFeed : Aperçu de la vie locale sur la page d'accueil (accessible à TOUS les visiteurs)
// On extrait un échantillon représentatif et diversifié (Météo, Blog, Post commerçant, Bon Plan, 1 Événement)
$feedPreviewHtml = '';
if (class_exists('\\LocalFeed\\Services\\LocalFeedService')) {
    $currentUser = class_exists('\\Core\\Auth\\UsersManager') ? \Core\Auth\UsersManager::getCurrentUser() : null;
    $userId = $currentUser ? (int)($currentUser->id ?? 0) : null;
    $feedService = \LocalFeed\Services\LocalFeedService::getInstance();
    $previewTimeline = $feedService->getLandingDiversityItems($userId, 4);
    
    ob_start();
    include ROOT . 'theme/halloween/template/localfeed_landing_section.tpl';
    $feedPreviewHtml = ob_get_clean();
}
$tpl->set('feedPreviewHtml', $feedPreviewHtml);
Template::addGlobal('feedPreviewHtml', $feedPreviewHtml);

// 1. Agenda : Événements réels avec affiches officielles pour la landing page
$agendaCards = [];
$agendaEventsFile = ROOT . 'data/plugin/agenda/events.json';
$allAgendaEvents = [];

if (file_exists($agendaEventsFile)) {
    $allAgendaEvents = json_decode(file_get_contents($agendaEventsFile), true) ?: [];
}

$today = date('Y-m-d');
$imminent = [];
$ongoing = [];

foreach ($allAgendaEvents as $ev) {
    $sDate = $ev['start_date'] ?? null;
    $eDate = $ev['end_date'] ?? $sDate;
    if (!$sDate || ($eDate < $today && $sDate < $today)) {
        continue;
    }

    if ($sDate >= $today) {
        $imminent[] = $ev;
    } else {
        $ongoing[] = $ev;
    }
}

// Trier chronologiquement (événements imminents en tête)
usort($imminent, function($a, $b) {
    $c = strcmp($a['start_date'] ?? '', $b['start_date'] ?? '');
    if ($c !== 0) return $c;
    return !empty($b['image_url']) <=> !empty($a['image_url']);
});
usort($ongoing, function($a, $b) {
    return strcmp($a['end_date'] ?? '', $b['end_date'] ?? '');
});

// 8 événements (2 lignes de 4) : priorité aux sorties imminentes complétées par les expos en cours
$selectedEvents = [];
foreach ($imminent as $ev) {
    if (!empty($ev['image_url'])) $selectedEvents[] = $ev;
    if (count($selectedEvents) >= 6) break;
}
foreach ($ongoing as $ev) {
    if (!empty($ev['image_url'])) $selectedEvents[] = $ev;
    if (count($selectedEvents) >= 8) break;
}
if (count($selectedEvents) < 8) {
    foreach (array_merge($imminent, $ongoing) as $ev) {
        if (!in_array($ev, $selectedEvents, true)) {
            $selectedEvents[] = $ev;
            if (count($selectedEvents) >= 8) break;
        }
    }
}

// Catégories conviviales
$catLabels = [
    'spectacle'  => 'Spectacle',
    'concert'    => 'Concert',
    'cinema'     => 'Cinéma',
    'exposition' => 'Exposition',
    'animation'  => 'Animation',
    'conference' => 'Conférence',
    'autre'      => 'Culture'
];

$upcomingEvents = $selectedEvents;

foreach ($selectedEvents as $i => $ev) {
    $sDate = $ev['start_date'] ?? $today;
    $eDate = $ev['end_date'] ?? $sDate;
    $loc = !empty($ev['location']) ? $ev['location'] : 'Bourges';
    if (mb_strlen($loc) > 28) {
        $loc = mb_substr($loc, 0, 25) . '…';
    }

    if ($sDate === $today) {
        $dateLabel = "Aujourd'hui !";
    } elseif ($sDate < $today && $eDate >= $today) {
        $dateLabel = "Jusqu'au " . date('d/m', strtotime($eDate));
    } else {
        $diffDays = (int)((strtotime($sDate) - strtotime($today)) / 86400);
        if ($diffDays === 1) {
            $dateLabel = "Demain (" . date('d/m', strtotime($sDate)) . ")";
        } else {
            $dateLabel = "Le " . date('d/m/Y', strtotime($sDate));
        }
    }
    
    $catSlug = strtolower($ev['category'] ?? 'autre');
    $tagLabel = $catLabels[$catSlug] ?? ucfirst($catSlug);

    $img = !empty($ev['image_url']) ? $ev['image_url'] : 'theme/halloween/img/news_' . (($i % 4) + 1) . '.webp';
    if (str_starts_with($img, 'http')) {
        $hash = md5($img);
        $localWebp = 'data/upload/agenda/ev_' . $hash . '.webp';
        if (file_exists(ROOT . $localWebp)) {
            $img = '/' . $localWebp;
        }
    }

    $agendaCards[] = [
        'title'    => $ev['title'] ?? 'Événement à Bourges',
        'subtitle' => $loc . ' • ' . $dateLabel,
        'tag'      => $tagLabel,
        'image'    => $img,
        'url'      => 'agenda/event/' . ($ev['id'] ?? '')
    ];
}

// Fallback si aucun événement dans l'agenda
if (empty($agendaCards)) {
    $agendaCards = [
        [
            'title' => 'Temps forts et festivités à Bourges',
            'subtitle' => 'Découvrez les grands rendez-vous culturels et animations du moment',
            'tag' => 'Culture',
            'image' => 'theme/halloween/img/news_1.webp',
            'url' => 'agenda'
        ],
        [
            'title' => 'Spectacles & Scène Nationale MCB',
            'subtitle' => 'Théâtre, musique live et créations contemporaines au cœur de la ville',
            'tag' => 'Spectacle',
            'image' => 'theme/halloween/img/news_2.webp',
            'url' => 'agenda'
        ],
        [
            'title' => 'Commerçants & Artisans Locaux',
            'subtitle' => 'Soutenez les savoir-faire et commerces de proximité berruyers',
            'tag' => 'Économie',
            'image' => 'theme/halloween/img/news_3.webp',
            'url' => 'SearchUi?family=shops'
        ],
        [
            'title' => 'Associations, Clubs & Bénévolat',
            'subtitle' => 'Participez à la dynamique associative et sportive de Bourges',
            'tag' => 'Vie locale',
            'image' => 'theme/halloween/img/news_4.webp',
            'url' => 'SearchUi?query=association'
        ]
    ];
}

// 2. POIs pour la carte interactive Leaflet
$mapPois = [];
$carouselPois = [];
$totalPoiCount = 0;

if ($pdo) {
    try {
        // Total count synchronisé exactement avec le moteur de recherche SearchUi (100% identique)
        if (class_exists('\SearchEngine\Services\Search\SearchService') && class_exists('\PoiData\Services\CommuneService')) {
            $communeService = new \PoiData\Services\CommuneService();
            $aggloCommunes = $communeService->loadAggloCommunes();
            $centroids = $communeService->getCommuneCentroids([], $aggloCommunes);

            $searchService = new \SearchEngine\Services\Search\SearchService();
            $sRes = $searchService->search('', null, null, 5.0, '', '', '', 1, 0, 'relevance', null, null, $centroids, '', true);
            $totalPoiCount = $sRes['total'] ?? 0;
        } else {
            $totalPoiCount = (int)$pdo->query("SELECT count(DISTINCT poi_id) FROM poi_search WHERE status = 'public' AND is_closed = 0")->fetchColumn();
        }

        // Map POIs
        $stmt = $pdo->query("
            SELECT id, name, slug, category, subcategory, lat, lon, addr_street, addr_city, stars, datascore, phone
            FROM poi_public
            WHERE lat IS NOT NULL AND lon IS NOT NULL 
              AND lat BETWEEN 47.04 AND 47.13 
              AND lon BETWEEN 2.34 AND 2.46
            ORDER BY datascore DESC
            LIMIT 25
        ");
        if ($stmt) {
            $mapPois = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        // Carousel POIs : Tirage aléatoire qualitatif parmi les 50 meilleures fiches complètes de Bourges
        $cStmt = $pdo->query("
            SELECT * FROM (
                SELECT id, name, slug, category, subcategory, addr_street, addr_city, stars, datascore, phone
                FROM poi_public
                WHERE name IS NOT NULL AND name != ''
                  AND (addr_street IS NOT NULL OR phone IS NOT NULL)
                ORDER BY datascore DESC, stars DESC
                LIMIT 50
            ) AS top_pois
            ORDER BY RAND()
            LIMIT 12
        ");
        if ($cStmt) {
            $carouselPois = $cStmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($carouselPois as &$cp) {
                $slug = !empty($cp['slug']) ? $cp['slug'] : \Utils\Util::strToUrl($cp['name']);
                $cp['detail_url'] = !empty($cp['slug']) ? ('/' . ltrim($cp['slug'], '/')) : ('/' . $slug . '-' . $cp['id']);
                
                // Icône FontAwesome par catégorie
                $cat = strtolower($cp['category'] ?? '');
                if (strpos($cat, 'sante') !== false) {
                    $cp['icon'] = 'fa-solid fa-notes-medical';
                    $cp['cat_label'] = 'Santé & Soins';
                } elseif (strpos($cat, 'admin') !== false) {
                    $cp['icon'] = 'fa-solid fa-landmark-flag';
                    $cp['cat_label'] = 'Service Public';
                } elseif (strpos($cat, 'commerce') !== false) {
                    $cp['icon'] = 'fa-solid fa-shop';
                    $cp['cat_label'] = 'Commerce Local';
                } elseif (strpos($cat, 'tourisme') !== false || strpos($cat, 'heberg') !== false) {
                    $cp['icon'] = 'fa-solid fa-hotel';
                    $cp['cat_label'] = 'Tourisme & Séjour';
                } elseif (strpos($cat, 'restaur') !== false) {
                    $cp['icon'] = 'fa-solid fa-utensils';
                    $cp['cat_label'] = 'Restauration';
                } else {
                    $cp['icon'] = 'fa-solid fa-location-dot';
                    $cp['cat_label'] = $cp['category'] ?: 'Lieu utile';
                }
            }
            unset($cp);
        }

        // Lieux Phares & Incontournables de Bourges (Maillage direct statique SEO)
        $featuredPois = [];
        $fStmt = $pdo->query("
            SELECT id, name, slug, category, subcategory, addr_street, addr_city
            FROM poi_public
            WHERE slug IN (
                'cathedrale-saint-etienne',
                'palais-jacques-coeur',
                'jardin-de-l-archeveche',
                'museum-d-histoire-naturelle',
                'musee-du-berry',
                'musee-esteve',
                'halle-au-bl-e',
                'musee-des-meilleurs-ouvriers-de-france'
            )
            AND is_closed = 0
            LIMIT 8
        ");
        if ($fStmt) {
            $featuredPois = $fStmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($featuredPois as &$fp) {
                $fp['url'] = '/' . ltrim($fp['slug'], '/');
            }
            unset($fp);
        }
    } catch (\Exception $e) {
        $core->getLogger()->log(\Core\Logger::LEVEL_WARNING, "POI queries: " . $e->getMessage());
    }
}

// 3. Articles de blog récents
$recentArticles = [];
try {
    if (file_exists(ROOT . 'data/plugin/blog/blog.json')) {
        $blogData = \Utils\Util::readJsonFile(ROOT . 'data/plugin/blog/blog.json');
        if (is_array($blogData)) {
            $blogData = \Utils\Util::sort2DimArray($blogData, 'date', 'desc');
            $recentArticles = array_slice($blogData, 0, 3);
            foreach ($recentArticles as &$ra) {
                $ra['url'] = 'blog/' . ($ra['id'] ?? '');
            }
            unset($ra);
        }
    }
} catch (\Exception $e) {
    // Graceful fallback
}

if (empty($recentArticles)) {
    $recentArticles = [
        ['id' => '1', 'name' => 'Bienvenue à Bourges : guide des nouveaux arrivants', 'date' => date('d/m/Y'), 'url' => 'blog'],
        ['id' => '2', 'name' => 'Les Marais de Bourges sous les couleurs d\'automne', 'date' => date('d/m/Y'), 'url' => 'blog'],
        ['id' => '3', 'name' => 'Commerces de proximité : nouvelle dynamique', 'date' => date('d/m/Y'), 'url' => 'blog']
    ];
}

// 4. Statistiques de la communauté (Données 100% réelles, dédoublonnées par email)
$uniqueUsers = [];
$cmsUsersFile = (defined('DATA_CORE_AUTH') ? DATA_CORE_AUTH : ROOT . '/data/core/auth/') . 'users.json';
if (file_exists($cmsUsersFile)) {
    $cmsUsers = json_decode(file_get_contents($cmsUsersFile), true);
    if (is_array($cmsUsers)) {
        foreach ($cmsUsers as $u) {
            $key = strtolower(trim($u['email'] ?? $u['username'] ?? ''));
            if (!empty($key)) {
                $uniqueUsers[$key] = true;
            }
        }
    }
}

// Compte des utilisateurs de la base MySQL (table `users`)
if ($pdo) {
    try {
        $uStmt = $pdo->query("SELECT email, username FROM users");
        if ($uStmt) {
            while ($row = $uStmt->fetch(\PDO::FETCH_ASSOC)) {
                $key = strtolower(trim($row['email'] ?? $row['username'] ?? ''));
                if (!empty($key)) {
                    $uniqueUsers[$key] = true;
                }
            }
        }
    } catch (\Exception $e) {}
}
$realUsersCount = count($uniqueUsers);

// 5. Compteurs réels des commodités citoyennes & plein air (poi_search)
$civicCounts = [
    'pharmacies' => 0,
    'atms' => 0,
    'defibrillators' => 0,
    'fountains' => 0,
    'water_fountains' => 0,
    'toilets' => 0,
    'bookcases' => 0,
    'community_centres' => 0,
    'playgrounds' => 0,
    'pitches' => 0,
    'tracks' => 0,
    'artworks' => 0,
    'dog_parks' => 0
];

if ($pdo) {
    try {
        $cStmt = $pdo->query("
            SELECT subcategory, COUNT(*) as cnt 
            FROM poi_search 
            WHERE subcategory IN (
                'urgences-defibrillateurs',
                'pharmacies-parapharmacies',
                'distributeurs-billets-dab',
                'fontaines-eau-potable',
                'toilettes-publiques',
                'boites-a-livres',
                'centres-sociaux-quartiers',
                'aires-jeux',
                'terrains-sport-city-stades',
                'pistes-glisse-skateparks',
                'oeuvres-art-statues',
                'parcs-canins'
            ) 
            GROUP BY subcategory
        ");
        if ($cStmt) {
            $rows = $cStmt->fetchAll(\PDO::FETCH_KEY_PAIR);
            if (isset($rows['pharmacies-parapharmacies'])) $civicCounts['pharmacies'] = (int)$rows['pharmacies-parapharmacies'];
            if (isset($rows['distributeurs-billets-dab'])) $civicCounts['atms'] = (int)$rows['distributeurs-billets-dab'];
            if (isset($rows['urgences-defibrillateurs'])) $civicCounts['defibrillators'] = (int)$rows['urgences-defibrillateurs'];
            if (isset($rows['fontaines-eau-potable'])) {
                $civicCounts['fountains'] = (int)$rows['fontaines-eau-potable'];
                $civicCounts['water_fountains'] = (int)$rows['fontaines-eau-potable'];
            }
            if (isset($rows['toilettes-publiques'])) $civicCounts['toilets'] = (int)$rows['toilettes-publiques'];
            if (isset($rows['boites-a-livres'])) $civicCounts['bookcases'] = (int)$rows['boites-a-livres'];
            if (isset($rows['centres-sociaux-quartiers'])) $civicCounts['community_centres'] = (int)$rows['centres-sociaux-quartiers'];
            if (isset($rows['aires-jeux'])) $civicCounts['playgrounds'] = (int)$rows['aires-jeux'];
            if (isset($rows['terrains-sport-city-stades'])) $civicCounts['pitches'] = (int)$rows['terrains-sport-city-stades'];
            if (isset($rows['pistes-glisse-skateparks'])) $civicCounts['tracks'] = (int)$rows['pistes-glisse-skateparks'];
            if (isset($rows['oeuvres-art-statues'])) $civicCounts['artworks'] = (int)$rows['oeuvres-art-statues'];
            if (isset($rows['parcs-canins'])) $civicCounts['dog_parks'] = (int)$rows['parcs-canins'];
        }
    } catch (\Exception $e) {}
}

// 6. Visiteurs réels des 7 derniers jours issus de Matomo (avec cache 30 min et fallback LightStats anti-bots)
$weeklyVisitorsCount = 0;
$cacheFile = (defined('ROOT') ? ROOT : '') . 'data/cache/landing_weekly_visitors.json';

if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 1800)) {
    $cachedData = json_decode(file_get_contents($cacheFile), true);
    if (isset($cachedData['weekly_visitors']) && is_numeric($cachedData['weekly_visitors'])) {
        $weeklyVisitorsCount = (int)$cachedData['weekly_visitors'];
    }
}

if ($weeklyVisitorsCount === 0) {
    // Tentative 1 : Récupération depuis Matomo (visiteurs uniques réels des 7 derniers jours)
    $matomoFile = (defined('PLUGINS') ? PLUGINS : (defined('ROOT') ? ROOT . 'plugin/' : 'plugin/')) . 'lightstats/lib/MatomoConnector.php';
    if (file_exists($matomoFile)) {
        require_once $matomoFile;
        try {
            $start = (new \DateTime("-7 days"))->format('Y-m-d');
            $end = (new \DateTime())->format('Y-m-d');
            $matomoSummary = \LightStats\Lib\MatomoConnector::getSummary($start, $end);
            if (!empty($matomoSummary['unique_visitors']) && (int)$matomoSummary['unique_visitors'] > 0) {
                $weeklyVisitorsCount = (int)$matomoSummary['unique_visitors'];
            }
        } catch (\Throwable $e) {}
    }

    // Tentative 2 : Fallback LightStats avec exclusion stricte des bots
    if ($weeklyVisitorsCount === 0) {
        $lightstatsDir = (defined('DATA_PLUGIN') ? DATA_PLUGIN : ROOT . 'data/plugin/') . 'lightstats/logs';
        $uniqueIps = [];
        for ($i = 0; $i < 7; $i++) {
            $d = new \DateTime("-$i days");
            $basePath = $lightstatsDir . '/' . $d->format('Y/m/d');
            foreach ([$basePath . '.jsonl', $basePath . '.json'] as $f) {
                if (file_exists($f)) {
                    $dayLogs = (substr($f, -6) === '.jsonl')
                        ? array_filter(array_map(fn($l) => json_decode(trim($l), true), file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []))
                        : json_decode(file_get_contents($f), true);
                    if (is_array($dayLogs)) {
                        foreach ($dayLogs as $entry) {
                            if (is_array($entry) && !empty($entry['ip']) && empty($entry['isBot'])) {
                                $uniqueIps[$entry['ip']] = true;
                            }
                        }
                    }
                }
            }
        }
        $weeklyVisitorsCount = count($uniqueIps);
    }

    // Sauvegarde en cache
    if ($weeklyVisitorsCount > 0 && is_dir(dirname($cacheFile))) {
        @file_put_contents($cacheFile, json_encode([
            'weekly_visitors' => $weeklyVisitorsCount,
            'source' => (!empty($matomoSummary['unique_visitors'])) ? 'matomo' : 'lightstats_human',
            'updated_at' => date('Y-m-d H:i:s')
        ]));
    }
}

// 7. Mises à jour récentes (dernières 24h), POIs revendiqués et Bons Plans actifs
$recentUpdatesCount = 0;
$claimedPoisCount = 0;
$activeDealsCount = 0;
if ($pdo) {
    try {
        $recentUpdatesCount = (int)$pdo->query("SELECT COUNT(*) FROM poi WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
        $claimedPoisCount = (int)$pdo->query("SELECT COUNT(*) FROM claims WHERE status = 'APPROVED'")->fetchColumn();
        $activeDealsCount = (int)$pdo->query("SELECT COUNT(*) FROM lbp_deals WHERE status = 'active' AND stock > 0")->fetchColumn();
    } catch (\Exception $e) {}
}

$communityStats = [
    'total_pois' => $totalPoiCount ?: 0,
    'total_users' => $realUsersCount,
    'weekly_visitors' => $weeklyVisitorsCount,
    'recent_updates' => $recentUpdatesCount,
    'claimed_pois' => $claimedPoisCount,
    'active_deals' => $activeDealsCount,
    'upcoming_events' => (function () use ($imminent, $ongoing) {
        try {
            return count((new \Agenda\Entities\EventManager())->getAll(false, true));
        } catch (\Throwable $e) {
            return count($imminent) + count($ongoing);
        }
    })()
];

$tpl->set('upcomingEvents', $upcomingEvents);
$tpl->set('agendaCards', $agendaCards);
$tpl->set('mapPoisJson', json_encode($mapPois, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP));
$tpl->set('mapPois', $mapPois);
$tpl->set('carouselPois', $carouselPois);
$tpl->set('hasCarouselPois', !empty($carouselPois));
$tpl->set('featuredPois', $featuredPois ?? []);
$tpl->set('hasFeaturedPois', !empty($featuredPois));
$tpl->set('recentArticles', $recentArticles);
$tpl->set('communityStats', $communityStats);
$tpl->set('civicCounts', $civicCounts);
