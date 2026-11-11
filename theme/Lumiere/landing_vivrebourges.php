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

// Connexion DB
$pdo = null;
try {
    $pdo = \Core\Database::getInstance()->getPdo();
} catch (\Exception $e) {
    $core->getLogger()->log(\Core\Logger::LEVEL_ERROR, "VivreBourges landing: " . $e->getMessage());
}

// 1. Agenda : Événements réels à venir
$upcomingEvents = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT e.id, e.title, e.description, o.date_start, o.date_end, o.time_start, o.location_raw
            FROM event_public e
            JOIN event_occurrences o ON o.event_id = e.id
            WHERE e.status = 'PUBLISHED' AND o.status = 'SCHEDULED'
            ORDER BY o.date_start ASC, o.time_start ASC
            LIMIT 4
        ");
        if ($stmt) {
            $upcomingEvents = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($upcomingEvents as &$ev) {
                if (!empty($ev['date_start'])) {
                    $dt = new \DateTime($ev['date_start']);
                    $ev['formatted_date'] = $dt->format('d/m/Y');
                    $ev['day'] = $dt->format('d');
                    $ev['month'] = $dt->format('M');
                } else {
                    $ev['formatted_date'] = '';
                }
                $ev['url'] = 'agenda/' . $ev['id'];
            }
            unset($ev);
        }
    } catch (\Exception $e) {
        $core->getLogger()->log(\Core\Logger::LEVEL_WARNING, "Events query: " . $e->getMessage());
    }
}

// Cartes agenda / actualités pour la grille du bas
$agendaCards = [
    [
        'title' => 'Temps forts et festivités à Bourges',
        'subtitle' => 'Découvrez les grands rendez-vous culturels et animations du moment',
        'tag' => 'Culture',
        'image' => 'theme/VivreBourges/img/news_1.png',
        'url' => 'agenda'
    ],
    [
        'title' => 'Spectacles & Scène Nationale MCB',
        'subtitle' => 'Théâtre, musique live et créations contemporaines au cœur de la ville',
        'tag' => 'Spectacle',
        'image' => 'theme/VivreBourges/img/news_2.png',
        'url' => 'agenda'
    ],
    [
        'title' => 'Commerçants & Artisans Locaux',
        'subtitle' => 'Soutenez les savoir-faire et commerces de proximité berruyers',
        'tag' => 'Économie',
        'image' => 'theme/VivreBourges/img/news_3.png',
        'url' => 'SearchUi/result?category=commerce'
    ],
    [
        'title' => 'Associations, Clubs & Bénévolat',
        'subtitle' => 'Participez à la dynamique associative et sportive de Bourges',
        'tag' => 'Vie locale',
        'image' => 'theme/VivreBourges/img/news_4.png',
        'url' => 'SearchUi/result?q=association'
    ]
];

if (!empty($upcomingEvents)) {
    for ($i = 0; $i < min(count($upcomingEvents), 4); $i++) {
        $ev = $upcomingEvents[$i];
        $agendaCards[$i]['title'] = $ev['title'];
        if (!empty($ev['location_raw'])) {
            $agendaCards[$i]['subtitle'] = $ev['location_raw'] . ' • ' . $ev['formatted_date'];
        }
        $agendaCards[$i]['url'] = $ev['url'];
    }
}

// 2. POIs pour la carte interactive Leaflet
$mapPois = [];
$carouselPois = [];
$totalPoiCount = 0;

if ($pdo) {
    try {
        // Total count
        $countStmt = $pdo->query("SELECT COUNT(*) FROM poi_public");
        if ($countStmt) {
            $totalPoiCount = (int)$countStmt->fetchColumn();
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

        // Carousel POIs (échantillon de lieux emblématiques et commerces de Bourges)
        $cStmt = $pdo->query("
            SELECT id, name, slug, category, subcategory, addr_street, addr_city, stars, datascore, phone
            FROM poi_public
            WHERE name IS NOT NULL AND name != ''
            ORDER BY datascore DESC, stars DESC
            LIMIT 12
        ");
        if ($cStmt) {
            $carouselPois = $cStmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($carouselPois as &$cp) {
                $slug = !empty($cp['slug']) ? $cp['slug'] : \Utils\Util::strToUrl($cp['name']);
                $cp['detail_url'] = $slug . '-' . $cp['id'];
                
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

// 4. Statistiques de la communauté (reprise Lumière V2)
$communityStats = [
    'total_pois' => $totalPoiCount ?: 1339,
    'total_users' => 14,
    'weekly_visitors' => 850,
    'recent_updates' => 38,
    'claimed_pois' => 12
];

try {
    if ($pdo) {
        $uStmt = $pdo->query("SELECT COUNT(*) FROM user");
        if ($uStmt) $communityStats['total_users'] = max(14, (int)$uStmt->fetchColumn());
    }
} catch (\Exception $e) {}

$tpl->set('upcomingEvents', $upcomingEvents);
$tpl->set('agendaCards', $agendaCards);
$tpl->set('mapPoisJson', json_encode($mapPois, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP));
$tpl->set('mapPois', $mapPois);
$tpl->set('carouselPois', $carouselPois);
$tpl->set('hasCarouselPois', !empty($carouselPois));
$tpl->set('recentArticles', $recentArticles);
$tpl->set('communityStats', $communityStats);
