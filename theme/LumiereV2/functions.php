<?php
defined('ROOT') or exit('Access denied!');

$core = \Core\Core::getInstance();
$pluginsManager = \Core\Plugin\PluginsManager::getInstance();

$db = \Core\Database::getInstance(); 
$pdo = $db->getPdo();

// 1. Inject Stats & Random POIs (Fix for Carousel)
// We try to replicate what logic Lumiere parent theme does, just in case it's not loaded
// or to be sure.
// 1. Inject Stats & Random POIs (Fix for Carousel) - OPTIMIZED
$randomPois = [];
try {
    if (!$pdo) throw new \Exception("Database connection not available");
    
    // Agglo filter
    $aggloFile = COMMON . 'data/agglo.json';
    $citiesCondition = "";
    if (file_exists($aggloFile)) {
        $json = json_decode(file_get_contents($aggloFile), true);
        $cities = $json['communes'] ?? [];
        if (!empty($cities)) {
            $quotedCities = array_map(function($c) use ($pdo) { return $pdo->quote($c); }, $cities);
            $citiesCondition = "AND addr_city IN (" . implode(',', $quotedCities) . ")";
        }
    }

    // Step 1: Get Candidate IDs (Fast)
    // We limit to 1000 candidates to avoid memory issues if agglo is huge, but usually it's fine.
    // ORDER BY RAND() here is still slow if we strictly want random from ALL.
    // Better: Get ALL IDs, then array_rand in PHP.
    $sqlIds = "SELECT id FROM poi_public WHERE status = 'public' $citiesCondition";
    $stmtIds = $pdo->query($sqlIds);
    $ids = $stmtIds->fetchAll(\PDO::FETCH_COLUMN);

    if (!empty($ids)) {
        // Step 2: Pick 12 random IDs
        $count = count($ids);
        $keys = ($count > 12) ? array_rand($ids, 12) : array_keys($ids);
        if (!is_array($keys)) $keys = [$keys];
        
        $selectedIds = [];
        foreach ($keys as $k) {
            $selectedIds[] = $ids[$k];
        }
        
        if (!empty($selectedIds)) {
            // Step 3: Fetch Details for selected IDs
            $inIds = implode(',', $selectedIds);
            $sqlDetails = "SELECT p.*, s.lat, s.lon, t.value_text as logo
                           FROM poi_public p
                           LEFT JOIN poi_search s ON s.poi_id = p.id
                           LEFT JOIN poi_tags t ON t.poi_id = p.id AND t.key_name = 'logo'
                           WHERE p.id IN ($inIds)";
            
            $rows = $pdo->query($sqlDetails)->fetchAll(\PDO::FETCH_ASSOC);
            
            // Translator for categories
            require_once PLUGINS . 'PoiData/services/CategoryLabelTranslator.php';
            $translator = new \PoiData\Services\CategoryLabelTranslator();

            // Step 4: Format
            $router = \Core\Router\Router::getInstance();
            foreach ($rows as $row) {
                  $poiId = (int)$row['id'];
                  $slug = $row['slug'] ?? \Utils\Util::strToUrl($row['name']);
                  // Format: /slug-id (matches the 'detail-short' route)
                  $url = '/' . $slug . '-' . $poiId;
                  
                  // 1. Fetch Social Links & Contact Info
                  $links = [];
                  $phone = null;
                  $stmtL = $pdo->prepare("SELECT label as platform, url FROM poi_links WHERE poi_id = ?");
                  $stmtL->execute([$poiId]);
                  foreach ($stmtL->fetchAll(\PDO::FETCH_ASSOC) as $lk) {
                      $platform = strtolower($lk['platform']);
                      $icon_class = 'fa-solid fa-link';
                      if (strpos($platform, 'facebook') !== false) $icon_class = 'fa-brands fa-facebook';
                      elseif (strpos($platform, 'instagram') !== false) $icon_class = 'fa-brands fa-instagram';
                      elseif (strpos($platform, 'linkedin') !== false) $icon_class = 'fa-brands fa-linkedin';
                      elseif (strpos($platform, 'twitter') !== false || $platform === 'x') $icon_class = 'fa-brands fa-x-twitter';
                      elseif (strpos($platform, 'youtube') !== false) $icon_class = 'fa-brands fa-youtube';
                      elseif (strpos($platform, 'tiktok') !== false) $icon_class = 'fa-brands fa-tiktok';
                      elseif ($platform === 'website' || strpos($platform, 'site') !== false) $icon_class = 'fa-solid fa-globe';
                      
                      $links[] = [
                          'platform' => $platform,
                          'url' => $lk['url'],
                          'icon_class' => $icon_class
                      ];
                  }

                  $stmtC = $pdo->prepare("SELECT type, value FROM poi_contacts WHERE poi_id = ?");
                  $stmtC->execute([$poiId]);
                  foreach ($stmtC->fetchAll(\PDO::FETCH_ASSOC) as $c) {
                      $cType = strtolower(trim($c['type'] ?? ''));
                      if ($cType === 'phone' || $cType === 'mobile') $phone = $c['value'];
                      if ($cType === 'website' && !empty($c['value'])) {
                          $exists = false;
                          foreach ($links as $lk) {
                              if ($lk['platform'] === 'website') { $exists = true; break; }
                          }
                          if (!$exists) {
                              $links[] = [
                                  'platform' => 'website',
                                  'url' => $c['value'],
                                  'icon_class' => 'fa-solid fa-globe'
                              ];
                          }
                      }
                  }

                  // 2. Fetch Cover Photo
                  $coverUrl = null;
                  $stmtP = $pdo->prepare("SELECT url FROM poi_photos WHERE poi_id = ? AND is_cover = 1 LIMIT 1");
                  $stmtP->execute([$poiId]);
                  $coverUrl = $stmtP->fetchColumn();
                  if (!$coverUrl) {
                      $stmtP = $pdo->prepare("SELECT url FROM poi_photos WHERE poi_id = ? ORDER BY created_at ASC LIMIT 1");
                      $stmtP->execute([$poiId]);
                      $coverUrl = $stmtP->fetchColumn();
                  }

                  // 3. Opening Status
                  $stmtOH = $pdo->prepare("SELECT * FROM poi_opening_hours WHERE poi_id = ? ORDER BY day_of_week ASC, open_time ASC");
                  $stmtOH->execute([$poiId]);
                  $ohRows = $stmtOH->fetchAll(\PDO::FETCH_ASSOC);
                  
                  $isOpenNowHtml = '';
                  if (!empty($ohRows)) {
                      $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
                      $currentDay = ((int)$now->format('w') + 6) % 7; 
                      $currentTime = $now->format('H:i:s');
                      
                      $isOpen = false;
                      foreach ($ohRows as $oh) {
                          if ((int)$oh['day_of_week'] === $currentDay && !$oh['is_closed']) {
                              if ($currentTime >= $oh['open_time'] && $currentTime <= $oh['close_time']) {
                                  $isOpen = true;
                                  break;
                              }
                          }
                      }
                      
                      if ($isOpen) {
                          $isOpenNowHtml = '<span class="status-open"><i class="fa-solid fa-clock"></i> Ouvert</span>';
                      } else {
                          $isOpenNowHtml = '<span class="status-closed"><i class="fa-solid fa-clock"></i> Fermé</span>';
                      }
                  }

                  // 4. Label Translation
                  $categoryKey = $row['category'] ?? '';
                  $subcategoryKey = $row['subcategory'] ?? '';
                  $typeLabel = $translator->translate($subcategoryKey ?: $categoryKey);
                  if ($typeLabel === 'Office') $typeLabel = 'Entreprise';

                  $randomPois[] = [
                     'id' => $poiId,
                     'name' => $row['name'],
                     'city' => $row['addr_city'] ?? '',
                     'logo' => \PoiData\Services\PoiDataFormatter::normalizeMediaPath($row['logo'] ?? null, $poiId),
                     'detail_url_final' => $url,
                     'type_label' => $typeLabel,
                     'category' => $categoryKey,
                     'cover_url' => \PoiData\Services\PoiDataFormatter::normalizeMediaPath($coverUrl, $poiId),
                     'has_cover' => !empty($coverUrl),
                     'lat' => $row['lat'] ?? null,
                     'lon' => $row['lon'] ?? null,
                     'links' => $links,
                     'phone' => $phone,
                      'is_open_now_html' => $isOpenNowHtml,
                      'full_address' => implode(' ', array_filter([$row['addr_housenumber'] ?? '', $row['addr_street'] ?? '', $row['addr_postcode'] ?? '', $row['addr_city'] ?? '']))
                   ];
            }
            // Shuffle again because IN clause might return defined order
            shuffle($randomPois);

            // Calculate slider logic for the mobile CSSgal
            $totalPoisCount = count($randomPois);
            foreach ($randomPois as $index => &$poiRef) {
                $poiRef['slide_id'] = $index + 1;
                $poiRef['slide_prev'] = ($index === 0) ? $totalPoisCount : $index;
                $poiRef['slide_next'] = ($index === $totalPoisCount - 1) ? 1 : ($index + 2);
            }
            unset($poiRef);
        }
    }
} catch (\Exception $e) {
     \Core\Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_ERROR, "LumiereV2/functions.php random_pois error: " . $e->getMessage());
}

// DEBUG DATA
// DEBUG DATA REMOVED

use Template\Template;
Template::addGlobal('randomPois', $randomPois);
Template::addGlobal('hasRandomPois', !empty($randomPois));

// 2. Fetch Latest Blog & Forum (Fix for "Actualités")
$latestBlog = [];
if ($pluginsManager->isActivePlugin('blog')) {
    $blogData = $core->callHook('blog:get-latest-post', ['latest_post' => []]);
    $latestBlog = $blogData['latest_post'] ?? [];
    // Clean excerpt to avoid HTML tags in card
    if (!empty($latestBlog['excerpt'])) {
        $latestBlog['excerpt'] = strip_tags(html_entity_decode($latestBlog['excerpt'], ENT_QUOTES, 'UTF-8'));
    }
}

$latestForum = [];
if ($pluginsManager->isActivePlugin('forum') && class_exists('\Forum\Entities\Post')) {
    try {
        $posts = \Forum\Entities\Post::query()->orderBy('created_at', 'DESC')->limit(4)->get();
        foreach ($posts as $post) {
            $topic = \Forum\Entities\Topic::findPK($post->topic_id);
            $latestForum[] = [
                'topic_title' => $topic ? $topic->title : 'Sujet inconnu',
                'content' => substr(strip_tags($post->content), 0, 100) . '...',
                'date' => date('d/m/Y', strtotime($post->created_at)),
                'url' => $router->generate('forum-topic', ['id' => $post->topic_id, 'slug' => ($topic ? $topic->slug : 'topic')]) . '#p' . $post->id
            ];
        }
    } catch (\Exception $e) {}
}

// 3. Fetch Latest Offers (Promotions)
$latestOffers = [];
if ($pluginsManager->isActivePlugin('offers') && class_exists('\Offers\Entities\Offer')) {
    try {
        // Fetch valid offers (not expired, active)
        $today = date('Y-m-d H:i:s');
        $rawOffers = \Offers\Entities\Offer::query()
            ->where('status', '=', 'active')
            ->where('end_date', '>=', $today)
            ->orderBy('created_at', 'DESC')
            ->limit(3)
            ->get();
            
        foreach ($rawOffers as $offer) {
             // We need the POI to get the name/slug
             $poi = \PoiData\Entities\Poi::findPK($offer->poi_id);
             $poiName = $poi ? $poi->name : 'Lieu inconnu';
             
             // Url generation
             $url = $router->generate('offer-detail', ['id' => $offer->id, 'slug' => \Utils\Util::strToUrl($offer->title)]);
             
             $latestOffers[] = [
                'title' => $offer->title,
                'poi_name' => $poiName,
                'discount' => $offer->discount_value . ($offer->discount_type === 'percent' ? '%' : '€'),
                'url' => $url,
                'image' => $offer->image ?? null
             ];
        }
    } catch (\Exception $e) {}
}

// 4. Fetch Latest Events (Agenda)
$latestEvents = [];
if ($pluginsManager->isActivePlugin('agenda') && class_exists('\Agenda\Entities\Event')) {
    try {
        $today = date('Y-m-d');
        $rawEvents = \Agenda\Entities\Event::query()
            ->where('start_date', '>=', $today)
            ->orderBy('start_date', 'ASC')
            ->limit(3)
            ->get();
            
        foreach ($rawEvents as $event) {
             $url = $router->generate('agenda-detail', ['id' => $event->id, 'slug' => \Utils\Util::strToUrl($event->title)]);
             $latestEvents[] = [
                'title' => $event->title,
                'date' => date('d/m', strtotime($event->start_date)),
                'location' => $event->location ?? 'Bourges',
                'url' => $url,
                'image' => (method_exists($event, 'getImageUrl') ? $event->getImageUrl() : null) ?: ($event->image_url ?? null)
             ];
        }
    } catch (\Exception $e) {}
}

// 5. Fetch Top 5 Active POIs (for Accordions)
$topPois = [];
try {
    if (!$pdo) throw new \Exception("Database connection not available");
    // Agglo filter for Top Pois
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

    $stmtTop = $pdo->query("SELECT p.id, p.name, t.value_text as logo, p.category, p.subcategory, 
                                 p.lat, p.lon,
                                 p.addr_housenumber, p.addr_street, p.addr_postcode, p.addr_city,
                                 p.last_activity_at
                          FROM poi_search s 
                          INNER JOIN poi_public p ON s.poi_id = p.id 
                          LEFT JOIN poi_tags t ON t.poi_id = p.id AND t.key_name = 'logo'
                          WHERE s.status = 'public' 
                          $citiesCondition
                          ORDER BY p.last_activity_at DESC, s.poi_id DESC 
                          LIMIT 5");
    $topRows = $stmtTop->fetchAll(\PDO::FETCH_ASSOC);
    
    // Reuse the same translator and formatter logic if possible, or manual format for speed
    foreach ($topRows as $index => $row) {
        $poiId = (int)$row['id'];
        $slug = $row['slug'] ?? \Utils\Util::strToUrl($row['name']);
        
        $logo = $row['logo'] ?? null;
        if ($logo && strpos($logo, 'http') !== 0 && strpos($logo, '/') !== 0) {
            $logo = '/' . $logo;
        }

        // 1. Fetch Social Links & Contact Info for this POI (Prevents foreach warning)
        $links = [];
        $phone = null;
        
        // Fetch from poi_links (socials)
        $stmtL = $pdo->prepare("SELECT label as platform, url FROM poi_links WHERE poi_id = ?");
        $stmtL->execute([$poiId]);
        foreach ($stmtL->fetchAll(\PDO::FETCH_ASSOC) as $lk) {
            $platform = strtolower($lk['platform']);
            $icon_class = 'fa-solid fa-link';
            if (strpos($platform, 'facebook') !== false) $icon_class = 'fa-brands fa-facebook';
            elseif (strpos($platform, 'instagram') !== false) $icon_class = 'fa-brands fa-instagram';
            elseif (strpos($platform, 'linkedin') !== false) $icon_class = 'fa-brands fa-linkedin';
            elseif (strpos($platform, 'twitter') !== false || $platform === 'x') $icon_class = 'fa-brands fa-x-twitter';
            elseif (strpos($platform, 'youtube') !== false) $icon_class = 'fa-brands fa-youtube';
            elseif (strpos($platform, 'tiktok') !== false) $icon_class = 'fa-brands fa-tiktok';
            elseif ($platform === 'website' || strpos($platform, 'site') !== false) $icon_class = 'fa-solid fa-globe';
            
            $links[] = [
                'platform' => $platform,
                'url' => $lk['url'],
                'icon_class' => $icon_class
            ];
        }

        // Fetch from poi_contacts (phone, email, website)
        $stmtC = $pdo->prepare("SELECT type, value FROM poi_contacts WHERE poi_id = ?");
        $stmtC->execute([$poiId]);
        foreach ($stmtC->fetchAll(\PDO::FETCH_ASSOC) as $c) {
            $cType = strtolower(trim($c['type'] ?? ''));
            if ($cType === 'phone' || $cType === 'mobile') $phone = $c['value'];
            if ($cType === 'website' && !empty($c['value'])) {
                // Check if already in links
                $exists = false;
                foreach ($links as $lk) {
                    if ($lk['platform'] === 'website') { $exists = true; break; }
                }
                if (!$exists) {
                    $links[] = [
                        'platform' => 'website',
                        'url' => $c['value'],
                        'icon_class' => 'fa-solid fa-globe'
                    ];
                }
            }
        }

        // 2. Fetch Cover Photo
        $coverUrl = null;
        $stmtP = $pdo->prepare("SELECT url FROM poi_photos WHERE poi_id = ? AND is_cover = 1 LIMIT 1");
        $stmtP->execute([$poiId]);
        $coverUrl = $stmtP->fetchColumn();
        if (!$coverUrl) {
            $stmtP = $pdo->prepare("SELECT url FROM poi_photos WHERE poi_id = ? ORDER BY created_at ASC LIMIT 1");
            $stmtP->execute([$poiId]);
            $coverUrl = $stmtP->fetchColumn();
        }

        // 3. Fetch Opening Hours and compute status
        $stmtOH = $pdo->prepare("SELECT * FROM poi_opening_hours WHERE poi_id = ? ORDER BY day_of_week ASC, open_time ASC");
        $stmtOH->execute([$poiId]);
        $ohRows = $stmtOH->fetchAll(\PDO::FETCH_ASSOC);
        
        $isOpenNowHtml = '';
        if (!empty($ohRows)) {
            $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
            $currentDay = ((int)$now->format('w') + 6) % 7; 
            $currentTime = $now->format('H:i:s');
            
            $isOpen = false;
            foreach ($ohRows as $oh) {
                if ((int)$oh['day_of_week'] === $currentDay && !$oh['is_closed']) {
                    if ($currentTime >= $oh['open_time'] && $currentTime <= $oh['close_time']) {
                        $isOpen = true;
                        break;
                    }
                }
            }
            
            if ($isOpen) {
                $isOpenNowHtml = '<span class="status-open"><i class="fa-solid fa-clock"></i> Ouvert</span>';
            } else {
                $isOpenNowHtml = '<span class="status-closed"><i class="fa-solid fa-clock"></i> Fermé</span>';
            }
        }

        // 4. Improved Type Label (OSM Translation)
        $categoryKey = $row['category'] ?? '';
        $subcategoryKey = $row['subcategory'] ?? '';
        $typeLabel = $translator->translate($subcategoryKey ?: $categoryKey);
        if ($typeLabel === 'Office') $typeLabel = 'Entreprise'; // Manual fallback as requested

        $topPois[] = [
            'id' => $poiId,
            'rank' => $index + 1,
            'name' => $row['name'],
            'city' => $row['addr_city'] ?? '',
            'logo' => \PoiData\Services\PoiDataFormatter::normalizeMediaPath($logo, $poiId),
            'lat' => $row['lat'],
            'lon' => $row['lon'],
            'full_address' => implode(' ', array_filter([$row['addr_housenumber'], $row['addr_street'], $row['addr_postcode'], $row['addr_city']])),
            'detail_url_final' => '/' . $slug . '-' . $poiId,
            'type_label' => $typeLabel,
            'last_activity_at' => $row['last_activity_at'],
            'links' => $links,
            'phone' => $phone,
            'has_cover' => !empty($coverUrl),
            'cover_url' => \PoiData\Services\PoiDataFormatter::normalizeMediaPath($coverUrl, $poiId),
            'is_open_now_html' => $isOpenNowHtml
        ];
    }
    
    // Fallback if less than 5
    if (count($topPois) < 5) {
        $needed = 5 - count($topPois);
        $existingIds = array_column($topPois, 'id');
        $idList = empty($existingIds) ? '0' : implode(',', $existingIds);
        
        $fallbackStmt = $pdo->query("SELECT s.poi_id as id, p.name, t.value_text as logo, p.category, p.subcategory, 
                                    p.addr_housenumber, p.addr_street, p.addr_postcode, p.addr_city, 
                                    COALESCE(s.lat, 0) as lat, COALESCE(s.lon, 0) as lon
                             FROM poi_search s 
                             INNER JOIN poi_public p ON s.poi_id = p.id 
                             LEFT JOIN poi_tags t ON t.poi_id = p.id AND t.key_name = 'logo'
                             WHERE s.status = 'public'
                             $citiesCondition
                             AND s.poi_id NOT IN ($idList)
                             ORDER BY RAND() 
                             LIMIT $needed");
        $fallbackRows = $fallbackStmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($fallbackRows as $index => $row) {
             $slug = $row['slug'] ?? \Utils\Util::strToUrl($row['name']);
             $logo = $row['logo'] ?? null;
             if ($logo && strpos($logo, 'http') !== 0 && strpos($logo, '/') !== 0) {
                 $logo = '/' . $logo;
             }
             $topPois[] = [
                'id' => $row['id'],
                'rank' => count($topPois) + 1,
                'name' => $row['name'],
                'city' => $row['addr_city'] ?? '',
                'logo' => \PoiData\Services\PoiDataFormatter::normalizeMediaPath($logo, $row['id']),
                'lat' => $row['lat'],
                'lon' => $row['lon'],
                'detail_url_final' => '/' . $slug . '-' . $row['id'],
                'type_label' => $translator->translate($row['category'] ?? '')
             ];
        }
    }
} catch (\Exception $e) {
    \Core\Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_ERROR, "LumiereV2/functions.php top_pois error: " . $e->getMessage());
}

Template::addGlobal('latestBlog', $latestBlog);
Template::addGlobal('latestForum', $latestForum);
Template::addGlobal('latestOffers', $latestOffers);
Template::addGlobal('latestEvents', $latestEvents);
Template::addGlobal('topPois', $topPois);
Template::addGlobal('hasTopPois', !empty($topPois));

// 5. Determine Home Page for Layout
$router = \Core\Router\Router::getInstance();
$match = $router->match();
$isHomePage = ($match && in_array($match['name'] ?? '', ['home', 'SearchUi-home']));
Template::addGlobal('IsHomePage', $isHomePage);

$core->addHook('endFrontHead', function($p) {
    // Check route inside the hook to ensure router has matched
    $router = \Core\Router\Router::getInstance();
    $match = $router->match();
    $isHome = ($match && in_array($match['name'] ?? '', ['home', 'SearchUi-home']));
    
    // Also check generic path if route names are tricky
    if (!$isHome) {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $isHome = ($path === '/' || $path === '/index.php');
    }

    // We rely on SearchMaps plugin for Leaflet assets
    // if ($isHome) { ... }


    $js = '
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var currentPath = window.location.pathname;
        var isHomePage = (currentPath === "/" || currentPath === "");
        var isSearchPage = (currentPath.indexOf("/SearchUi") === 0);
        var isRecherchePage = (currentPath.indexOf("/recherche") === 0);
        var isSpecialPage = isHomePage || isSearchPage || isRecherchePage;

        // Assign body classes (parent theme layout.tpl never sets these)
        if (isHomePage) document.body.classList.add("page-home");
        else if (isSearchPage) document.body.classList.add("page-SearchUi", "page-SearchUi-result");
        else if (isRecherchePage) document.body.classList.add("page-search");

        // Hide banner and remove all spacing on home/search pages
        if (isSpecialPage) {
            var banner = document.getElementById("banner");
            if (banner) banner.style.setProperty("display", "none", "important");

            var mainBody = document.getElementById("body");
            if (mainBody) {
                mainBody.style.setProperty("padding-top", "0", "important");
                mainBody.style.setProperty("padding-bottom", "0", "important");
                mainBody.style.setProperty("margin-top", "0", "important");
            }

            var bodyPage = document.getElementById("body-page");
            if (bodyPage) {
                bodyPage.style.setProperty("padding-top", "0", "important");
                bodyPage.style.setProperty("margin-top", "0", "important");
            }

            var content = document.getElementById("content");
            if (content) {
                content.style.setProperty("padding-top", "0", "important");
                content.style.setProperty("margin-top", "0", "important");
                content.style.setProperty("gap", "0", "important");
            }

            // Remove spacing from all content-panels
            var panels = document.querySelectorAll(".content-panel");
            for (var i = 0; i < panels.length; i++) {
                panels[i].style.setProperty("padding-top", "0", "important");
                panels[i].style.setProperty("margin-top", "0", "important");
                panels[i].style.setProperty("margin-bottom", "0", "important");
            }
        }

        // Scroll effect on header
        var header = document.getElementById("header");
        if (header) {
            var handleScroll = function() {
                if (window.scrollY > 50) {
                    header.classList.add("scrolled");
                } else {
                    header.classList.remove("scrolled");
                }
            };
            window.addEventListener("scroll", handleScroll);
            handleScroll();
        }

        // Mobile menu scroll lock
        var navToggle = document.getElementById("nav_toggle");
        if (navToggle) {
            navToggle.addEventListener("change", function() {
                if (this.checked) {
                    document.body.classList.add("menu-open");
                } else {
                    document.body.classList.remove("menu-open");
                }
            });
        }
    });
    </script>
    ';
    return $p . $js . '<script src="common/js/theme-toggle.js"></script>';
});

// 4. Gateway Search Integration Logic
// Implements Smart Search Orchestration
$core->addHook('layout:header-search', function() use ($core) {
    $currentPlugin = $core->getPluginToCall();
    
    // Define the "POI Ecosystem" where legacy search should be HIDDEN
    // SearchUi is the new entry point (handled by explicit hook in layout), so we don't want the old search there.
    $poiPlugins = [
        'SearchUi', 
        'SearchMaps', 
        'PoiEdit', 
        'PoiData', 
        'QrPoi', 
        'PoiFavorites', 
        'PoiReviews',
        'claim',
        'offers', 
        'subscription'
    ];
    
    // Check if we are inside the POI ecosystem
    $isPoiContext = in_array($currentPlugin, $poiPlugins);
    
    if (!$isPoiContext) {
        // We are on a Content page (Blog, Agenda, Forum, etc.)
        // Display the Legacy Search discretely
        echo '<div class="smart-search-container">';
        // Trigger the legacy hook that the 'search' plugin listens to
        $core->callHook('headerSearch', 'header');
        echo '</div>';
    }
});

// 4. Remove default hooks if possible to avoid duplicates with ConversionOrchestrator
// Newsletter triggers on endFrontBody
// PWA triggers on endFrontHead

// Note: removeHook might not exist, so we check method existence or just let it fail gracefully if we wrap it?
// Actually if removeHook doesn't exist, this will crash.
// So we will rely on CSS hiding in home.tpl or global CSS if removeHook is not available.

// However, we can also try to "Override" the hooks if the system supports it.
// For now, we focus on providing the data.

/**
 * Determine if banner content should be shown for a specific route
 */
function shouldShowBanner(string $routeName): bool {
    // Hide content on home page and search pages
    return !preg_match('/SearchUi-home|SearchUi-result|search-pretty|home/i', $routeName);
}

// 4. Restore Gateway "Hero Dashboard" Synergy
// Inject greeting / guest CTAs inside #banner section (showBanner=true in config)
// On home/search pages, #banner is hidden by JS so this content won't show there;
// home.tpl handles hero content on the home page directly.
$core->addHook('banner', function() use ($core) {
    $router = \Core\Router\Router::getInstance();
    $match = $router->match();
    $currentRoute = is_array($match) ? ($match['name'] ?? '') : '';

    // Skip on home/search pages — banner is hidden there, home.tpl handles hero
    if (in_array($currentRoute, ['home', 'SearchUi-home', 'SearchUi-result', 'search-pretty'])) {
        return;
    }

    // Show dashboard greeting (logged) or CTAs (guests) inside #banner
    $core->callHook('layout:hero-content'); 
});
?>
