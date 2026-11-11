<?php

namespace Agglobus\Controllers;

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Utils\Util;

class AgglobusPublicController extends PublicController
{
    private string $rtUrl = 'https://proxy.transport.data.gouv.fr/resource/83307?format=json';
    private string $vpUrl = 'https://proxy.transport.data.gouv.fr/resource/83307?format=json'; // Fallback to same resource if VP missing
    private string $cachePath = ROOT . 'data/cache/agglobus_rt.json';
    private string $vpCachePath = ROOT . 'data/cache/agglobus_vp.json';

    public function getGtfsFile(string $filename): string
    {
        $file = defined('DATA') ? DATA . 'gtfs_data/' . $filename : '';
        if ($file && file_exists($file)) {
            return $file;
        }
        if (defined('ROOT') && file_exists(ROOT . 'data/gtfs_data/' . $filename)) {
            return ROOT . 'data/gtfs_data/' . $filename;
        }
        $projectPath = dirname(__DIR__, 3) . '/data/gtfs_data/' . $filename;
        if (file_exists($projectPath)) {
            return $projectPath;
        }
        return $file ?: (defined('ROOT') ? ROOT . 'data/gtfs_data/' . $filename : '');
    }

    public function home()
    {
        @error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); // Suppress notes/warnings on home too
        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('Agglobus', 'home');
        $response->setTitle('Agglobus Temps Réel');
        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Proxy to fetch GTFS-RT data with cache
     * If stop_id is provided, returns filtered arrivals
     */
    public function apiProxy()
    {
        ob_start();
        date_default_timezone_set('Europe/Paris');
        $type = $_GET['type'] ?? 'trip_update';
        $stopId = $_GET['stop_id'] ?? null;
        $cacheTTL = 30; // seconds
        
        $url = ($type === 'vehicle_position' || $type === 'vehicles') ? $this->vpUrl : $this->rtUrl;
        $cachePath = ($type === 'vehicle_position' || $type === 'vehicles') ? $this->vpCachePath : $this->cachePath;

        if ($type === 'vehicles') {
            return $this->getSimulatedVehicles();
        }

        $json = null;
        $useCache = false;

        // 1. Check if we have a fresh cache
        if (file_exists($cachePath) && (time() - filemtime($cachePath)) < $cacheTTL) {
            $json = file_get_contents($cachePath);
            $useCache = true;
        } else {
            // 2. Try to fetch fresh data
            $context = stream_context_create(['http' => [
                'timeout' => 2,
                'ignore_errors' => true // We want to handle errors ourselves
            ]]);
            $json = @file_get_contents($url, false, $context);
            
            if ($json && strpos($http_response_header[0], '200') !== false) {
                // Success: save to cache
                if (!is_dir(dirname($cachePath))) {
                    @mkdir(dirname($cachePath), 0755, true);
                }
                @file_put_contents($cachePath, $json);
            } else {
                // Failure: try to reuse old cache if available (Stale fallback)
                if (file_exists($cachePath)) {
                    $json = file_get_contents($cachePath);
                    $useCache = true;
                }
            }
        }

        if (!$json) {
            // Hard failure: No cache and fetch failed
            if ($stopId && $type === 'trip_update') {
                $sched = $this->getScheduledArrivals($stopId);
                $results = $sched['upcoming'] ?? $sched;
                $allDay = $sched['all_day'] ?? [];
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode([
                    'stop_id' => $stopId,
                    'stop_name' => $sched['stop_name'] ?? $stopId,
                    'lat' => $sched['lat'] ?? null,
                    'lon' => $sched['lon'] ?? null,
                    'lines' => $sched['lines'] ?? [],
                    'arrivals' => $results,
                    'next_buses' => $results,
                    'all_day' => $allDay,
                    'rt_error' => 'Timeout or unavailable'
                ]);
                exit;
            }
            ob_clean();
            header('Content-Type: application/json', true, 503);
            echo json_encode(['error' => 'Unable to fetch real-time data and no cache available', 'type' => $type]);
            exit;
        }

        // Add header to indicate if it's cached data
        header('X-Agglo-Cache: ' . ($useCache ? 'HIT' : 'MISS'));
        if ($useCache) header('X-Agglo-Cache-Age: ' . (time() - filemtime($cachePath)));

        $data = json_decode($json, true);
        
        // If stop_id is requested (only for trip_updates), prepare a simplified response
        if ($stopId && $type === 'trip_update') {
            $arrivals = [];
            $entities = $data['entity'] ?? [];
            
            foreach ($entities as $entity) {
                if (!isset($entity['trip_update']['stop_time_update'])) continue;
                
                $routeId = $entity['trip_update']['trip']['route_id'] ?? '??';
                
                foreach ($entity['trip_update']['stop_time_update'] as $update) {
                    if ($update['stop_id'] == $stopId) {
                        $time = $update['arrival']['time'] ?? $update['departure']['time'] ?? null;
                        if (!$time) continue;
                        
                        $diff = $time - time();
                        if ($diff < -60) continue; // Skip past buses (1 min buffer)
                        
                        $routeMap = $this->getRouteInfoMap();
                        $routeInfo = $routeMap[$routeId] ?? null;

                        $arrivals[] = [
                            'route_id' => $routeId,
                            'short_name' => $routeInfo ? $routeInfo['short_name'] : $routeId,
                            'time' => $time,
                            'minutes' => round($diff / 60),
                            'scheduled_time' => date('H:i', $time),
                            'schedule_relationship' => $update['schedule_relationship'] ?? 'SCHEDULED'
                        ];
                    }
                }
            }
            
            // Sort by time
            usort($arrivals, function($a, $b) { return $a['time'] - $b['time']; });
            
            $results = array_slice($arrivals, 0, 5);

            $sched = $this->getScheduledArrivals($stopId);
            $allDay = $sched['all_day'] ?? [];
            if (empty($results)) {
                $results = $sched['upcoming'] ?? $sched;
            }
            
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'stop_id' => $stopId,
                'stop_name' => $sched['stop_name'] ?? $stopId,
                'lat' => $sched['lat'] ?? null,
                'lon' => $sched['lon'] ?? null,
                'lines' => $sched['lines'] ?? [],
                'arrivals' => $results,
                'next_buses' => $results,
                'all_day' => $allDay
            ]);
            exit;
        }

        ob_clean();
        header('Content-Type: application/json');
        echo $json;
        exit;
    }

    /**
     * Search stops by name
     */
    public function searchStops()
    {
        ob_start();
        $query = $_GET['q'] ?? '';
        if (strlen($query) < 2) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }

        if (function_exists('agglobusEnsureStops')) {
            agglobusEnsureStops();
        }
        $db = \Core\Database::getInstance();
        $sql = "SELECT stop_id, stop_name, lat, lon 
                FROM agglobus_stops 
                WHERE stop_name LIKE :q 
                GROUP BY TRIM(stop_name)
                LIMIT 10";
        
        $results = $db->query($sql, [':q' => "%$query%"]);
        
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }

    public function getNearStops()
    {
        ob_start();
        $lat = (float)($_GET['lat'] ?? 0);
        $lon = (float)($_GET['lon'] ?? 0);

        if (!$lat || !$lon) {
            ob_clean();
            header('Content-Type: application/json', true, 400);
            echo json_encode(['error' => 'Invalid coordinates']);
            exit;
        }

        if (function_exists('agglobusEnsureStops')) {
            agglobusEnsureStops();
        }
        $pdo = \Core\Database::getInstance()->getPdo();
        // Haversine formula + grouping by name to avoid duplicates (e.g. opposite sides of street)
        // Using TRIM to avoid duplicates due to invisible spaces
        $sql = "SELECT stop_id, TRIM(stop_name) as stop_name, lat, lon, MIN(dist) as distance
                FROM (
                    SELECT stop_id, stop_name, lat, lon,
                    (6371 * acos(cos(radians(:lat)) * cos(radians(lat)) * cos(radians(lon) - radians(:lon)) + sin(radians(:lat)) * sin(radians(lat)))) AS dist
                    FROM agglobus_stops
                ) AS stops_with_dist
                GROUP BY TRIM(stop_name)
                ORDER BY distance
                LIMIT 5";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':lat' => $lat, ':lon' => $lon]);
        $stops = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        ob_clean();
        header('Content-Type: application/json');
        echo json_encode($stops);
        exit;
    }

    public function test()
    {
        echo "Agglobus API is alive!";
        exit;
    }

    /**
     * Get available routes from routes.txt
     */
    public function getRoutes()
    {
        ob_start();
        @error_reporting(0); // Prevent warnings from breaking JSON
        $routesFile = $this->getGtfsFile('routes.txt');
        if (!file_exists($routesFile)) {
            header('Content-Type: application/json', true, 503);
            echo json_encode(['error' => 'Base de données des lignes indisponible (Maintenance GTFS)']);
            exit;
        }

        $routes = [];
        $handle = @fopen($routesFile, 'r');
        if ($handle) {
            $header = fgetcsv($handle, 0, ',', '"', '');
            while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                if (count($row) < 9) continue;
                $routes[] = [
                    'id' => $row[0],
                    'short_name' => $row[2],
                    'long_name' => $row[3],
                    'color' => '#' . ($row[7] ?: '00bfa5'),
                    'text_color' => '#' . ($row[8] ?: 'ffffff')
                ];
            }
            fclose($handle);
        }

        // Sort by short name (numeric if possible)
        usort($routes, function($a, $b) {
            return strnatcmp($a['short_name'] ?? '', $b['short_name'] ?? '');
        });

        ob_clean();
        header('Content-Type: application/json');
        echo json_encode($routes);
        exit;
    }

    /**
     * Get all stops for a specific route
     */
    public function getRouteStops()
    {
        ob_start();
        $routeId = $_GET['route_id'] ?? null;
        if (!$routeId) {
            ob_clean();
            header('Content-Type: application/json', true, 400);
            echo json_encode(['error' => 'Route ID required']);
            exit;
        }

        @error_reporting(0);
        $cacheFile = ROOT . "data/cache/agglobus_route_stops_{$routeId}.json";
        if (file_exists($cacheFile)) {
            $content = file_get_contents($cacheFile);
            if ($content) {
                ob_clean();
                header('Content-Type: application/json');
                echo $content;
                exit;
            }
        }

        $tripsFile = $this->getGtfsFile('trips.txt');
        $stopTimesFile = $this->getGtfsFile('stop_times.txt');

        if (!file_exists($tripsFile) || !file_exists($stopTimesFile)) {
            header('Content-Type: application/json', true, 503);
            echo json_encode(['error' => 'Données horaires indisponibles (Maintenance GTFS)']);
            exit;
        }

        // 1. Find a trip for each direction
        $directionTrips = []; // direction_id => trip_id
        $handle = @fopen($tripsFile, 'r');
        if ($handle) {
            $header = fgetcsv($handle, 0, ',', '"', '');
            $rIdx = array_search('route_id', $header);
            $tIdx = array_search('trip_id', $header);
            $dIdx = array_search('direction_id', $header);
            while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                if (($row[$rIdx] ?? '') == $routeId) {
                    $dir = $row[$dIdx] ?? '0';
                    if (!isset($directionTrips[$dir])) {
                        $directionTrips[$dir] = $row[$tIdx];
                    }
                }
            }
            fclose($handle);
        }

        if (empty($directionTrips)) {
            header('Content-Type: application/json', true, 404);
            echo json_encode(['error' => 'No trips found for this route']);
            exit;
        }

        // 2. Find stops for these trips
        $stopLists = []; // direction_id => [stop_id, ...]
        $stopsToFetch = [];
        $handle = @fopen($stopTimesFile, 'r');
        if ($handle) {
            $header = fgetcsv($handle, 0, ',', '"', '');
            $tIdx = array_search('trip_id', $header);
            $sIdx = array_search('stop_id', $header);
            $seqIdx = array_search('stop_sequence', $header);
            
            $resultsByTrip = []; // trip_id => [[stop_id, seq], ...]
            while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                if (in_array($row[$tIdx], $directionTrips)) {
                    $resultsByTrip[$row[$tIdx]][] = [
                        'stop_id' => $row[$sIdx],
                        'seq' => (int)$row[$seqIdx]
                    ];
                    $stopsToFetch[] = $row[$sIdx];
                }
            }
            fclose($handle);

            foreach ($directionTrips as $dir => $tripId) {
                if (isset($resultsByTrip[$tripId])) {
                    $tripStops = $resultsByTrip[$tripId];
                    usort($tripStops, function($a, $b) { return $a['seq'] - $b['seq']; });
                    $stopLists[$dir] = array_column($tripStops, 'stop_id');
                }
            }
        }

        // 3. Enrich stops with names and coords from DB
        $enrichedStops = [];
        if (!empty($stopsToFetch)) {
            if (function_exists('agglobusEnsureStops')) {
                agglobusEnsureStops();
            }
            $uniqueStops = array_values(array_unique($stopsToFetch));
            $db = \Core\Database::getInstance();
            $placeholders = str_repeat('?,', count($uniqueStops) - 1) . '?';
            $sql = "SELECT stop_id, stop_name, lat, lon FROM agglobus_stops WHERE stop_id IN ($placeholders)";
            try {
                $dbResults = $db->query($sql, $uniqueStops);
            } catch (\Throwable $e) {
                if (function_exists('agglobusEnsureStops')) {
                    agglobusEnsureStops();
                }
                $dbResults = $db->query($sql, $uniqueStops);
            }
            
            $stopMap = [];
            if (is_array($dbResults)) {
                foreach ($dbResults as $r) {
                    $stopMap[$r['stop_id']] = $r;
                }
            }

            // Fallback to stop_coords_cache.json if some stops are not yet in DB
            $coordCacheFile = $this->getGtfsFile('stop_coords_cache.json');
            $fallbackCache = [];
            if (file_exists($coordCacheFile)) {
                $fallbackCache = json_decode(file_get_contents($coordCacheFile), true) ?: [];
            }

            foreach ($stopLists as $dir => $ids) {
                $enrichedStops[$dir] = [];
                foreach ($ids as $id) {
                    if (isset($stopMap[$id])) {
                        $enrichedStops[$dir][] = $stopMap[$id];
                    } elseif (isset($fallbackCache[$id])) {
                        $enrichedStops[$dir][] = $fallbackCache[$id];
                    } else {
                        $enrichedStops[$dir][] = [
                            'stop_id' => $id,
                            'stop_name' => $id,
                            'lat' => 47.081,
                            'lon' => 2.398
                        ];
                    }
                }
            }
        }

        if (!is_dir(dirname($cacheFile))) @mkdir(dirname($cacheFile), 0755, true);
        @file_put_contents($cacheFile, json_encode($enrichedStops));

        ob_clean();
        header('Content-Type: application/json');
        echo json_encode($enrichedStops);
        exit;
    }

    /**
     * Get shape coordinates for a route
     */
    public function getShape()
    {
        ob_start();
        $routeId = $_GET['route_id'] ?? null;
        if (!$routeId) {
            ob_clean();
            header('Content-Type: application/json', true, 400);
            echo json_encode(['error' => 'Route ID required']);
            exit;
        }

        $tripsFile = $this->getGtfsFile('trips.txt');
        $shapesFile = $this->getGtfsFile('shapes.txt');
        
        if (!file_exists($tripsFile) || !file_exists($shapesFile)) {
            header('Content-Type: application/json', true, 503);
            echo json_encode(['error' => 'Données de tracé indisponibles (Maintenance GTFS)']);
            exit;
        }

        $shapeId = null;
        $handle = @fopen($tripsFile, 'r');
        if ($handle) {
            $header = fgetcsv($handle, 0, ',', '"', '');
            $routeIdx = array_search('route_id', $header);
            $shapeIdx = array_search('shape_id', $header);
            
            if ($routeIdx !== false && $shapeIdx !== false) {
                while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                    if (isset($row[$routeIdx]) && $row[$routeIdx] == $routeId) {
                        $shapeId = $row[$shapeIdx] ?? null;
                        break; 
                    }
                }
            }
            fclose($handle);
        }

        if (!$shapeId) {
            header('Content-Type: application/json', true, 404);
            echo json_encode(['error' => 'No shape found for this route']);
            exit;
        }

        $coords = [];
        $handle = @fopen($shapesFile, 'r');
        if ($handle) {
            $header = fgetcsv($handle, 0, ',', '"', '');
            $sIdIdx = array_search('shape_id', $header);
            $latIdx = array_search('shape_pt_lat', $header);
            $lonIdx = array_search('shape_pt_lon', $header);
            $seqIdx = array_search('shape_pt_sequence', $header);

            if ($sIdIdx !== false && $latIdx !== false && $lonIdx !== false) {
                while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                    if (isset($row[$sIdIdx]) && $row[$sIdIdx] == $shapeId) {
                        $coords[] = [
                            'lat' => (float)($row[$latIdx] ?? 0),
                            'lon' => (float)($row[$lonIdx] ?? 0),
                            'seq' => (int)($row[$seqIdx] ?? 0)
                        ];
                    }
                }
            }
            fclose($handle);
        }

        usort($coords, function($a, $b) { return $a['seq'] - $b['seq']; });

        ob_clean();
        header('Content-Type: application/json');
        echo json_encode($coords);
        exit;
    }

    /**
     * Internal helper to get route info map
     */
    private function getRouteInfoMap()
    {
        $routesFile = $this->getGtfsFile('routes.txt');
        if (!file_exists($routesFile)) return [];

        $map = [];
        $handle = @fopen($routesFile, 'r');
        if ($handle) {
            fgetcsv($handle, 0, ',', '"', '');
            while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                if (count($row) < 9) continue;
                $map[$row[0]] = [
                    'short_name' => $row[2],
                    'color' => '#' . ($row[7] ?: '00bfa5'),
                    'text_color' => '#' . ($row[8] ?: 'ffffff')
                ];
            }
            fclose($handle);
        }
        return $map;
    }

    /**
     * Get active service IDs for the current day.
     * Uses calendar_dates.txt (with fallback to the latest matching day of week if dataset is expired),
     * and calendar.txt if present.
     */
    public function getActiveServices(): array
    {
        static $cachedServices = null;
        if ($cachedServices !== null) {
            return $cachedServices;
        }

        $calendarDatesFile = $this->getGtfsFile('calendar_dates.txt');
        $calendarBaseFile = $this->getGtfsFile('calendar.txt');
        $today = date('Ymd');
        $dayOfWeek = (int)date('w'); // 0=Sunday, 1=Monday, ..., 6=Saturday
        $dayName = strtolower(date('l'));

        $activeServices = [];

        // 1. Regular schedule (calendar.txt)
        if (file_exists($calendarBaseFile)) {
            $handle = @fopen($calendarBaseFile, 'r');
            if ($handle) {
                $h = fgetcsv($handle, 0, ',', '"', '');
                $sIdx = array_search('service_id', $h);
                $dayIdx = array_search($dayName, $h);
                $startIdx = array_search('start_date', $h);
                $endIdx = array_search('end_date', $h);
                while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                    if (($row[$dayIdx] ?? '0') == '1' && $today >= ($row[$startIdx] ?? '') && $today <= ($row[$endIdx] ?? '')) {
                        if (!in_array($row[$sIdx], $activeServices)) {
                            $activeServices[] = $row[$sIdx];
                        }
                    }
                }
                fclose($handle);
            }
        }

        // 2. Exceptions & dates (calendar_dates.txt)
        if (file_exists($calendarDatesFile)) {
            $datesByDow = [];
            $servicesForToday = [];
            $handle = @fopen($calendarDatesFile, 'r');
            if ($handle) {
                $h = fgetcsv($handle, 0, ',', '"', '');
                $sIdx = array_search('service_id', $h);
                $dIdx = array_search('date', $h);
                $eIdx = array_search('exception_type', $h);
                if ($sIdx === false) $sIdx = 0;
                if ($dIdx === false) $dIdx = 1;
                if ($eIdx === false) $eIdx = 2;

                while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                    $d = $row[$dIdx] ?? '';
                    $sid = $row[$sIdx] ?? '';
                    $ex = $row[$eIdx] ?? '';
                    if ($ex === '1' && $d && $sid) {
                        if ($d === $today) {
                            $servicesForToday[] = $sid;
                        }
                        if (strlen($d) === 8) {
                            $dow = (int)date('w', strtotime($d));
                            $datesByDow[$dow][$d][] = $sid;
                        }
                    } elseif ($ex === '2' && $d === $today && $sid) {
                        $activeServices = array_diff($activeServices, [$sid]);
                    }
                }
                fclose($handle);
            }

            if (!empty($servicesForToday)) {
                $activeServices = array_merge($activeServices, $servicesForToday);
            } elseif (empty($activeServices) && !empty($datesByDow[$dayOfWeek])) {
                // FALLBACK: When dataset calendar has ended, match by current day of week
                krsort($datesByDow[$dayOfWeek]);
                $latestMatchingDate = array_key_first($datesByDow[$dayOfWeek]);
                $fallbackServices = $datesByDow[$dayOfWeek][$latestMatchingDate] ?? [];
                $activeServices = array_merge($activeServices, $fallbackServices);
            }
        }

        $cachedServices = array_values(array_unique($activeServices));
        return $cachedServices;
    }

    /**
     * Map active trips to route IDs
     */
    public function getActiveTrips(): array
    {
        static $cachedTrips = null;
        if ($cachedTrips !== null) return $cachedTrips;

        $details = $this->getActiveTripDetails();
        $activeTrips = [];
        foreach ($details as $tid => $d) {
            $activeTrips[$tid] = $d['route_id'];
        }

        $cachedTrips = $activeTrips;
        return $cachedTrips;
    }

    /**
     * Map active trips to route IDs and headsigns (destinations)
     */
    public function getActiveTripDetails(): array
    {
        static $cachedDetails = null;
        if ($cachedDetails !== null) return $cachedDetails;

        $activeServices = $this->getActiveServices();
        if (empty($activeServices)) {
            $cachedDetails = [];
            return [];
        }

        $tripsFile = $this->getGtfsFile('trips.txt');
        if (!file_exists($tripsFile)) {
            $cachedDetails = [];
            return [];
        }

        $activeDetails = [];
        $handle = @fopen($tripsFile, 'r');
        if ($handle) {
            $h = fgetcsv($handle, 0, ',', '"', '');
            $rIdx = array_search('route_id', $h);
            $sIdx = array_search('service_id', $h);
            $tIdx = array_search('trip_id', $h);
            $headIdx = array_search('trip_headsign', $h);
            $serviceLookup = array_flip($activeServices);
            while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                if (isset($row[$sIdx]) && isset($serviceLookup[$row[$sIdx]])) {
                    $activeDetails[$row[$tIdx]] = [
                        'route_id' => $row[$rIdx] ?? '',
                        'headsign' => ($headIdx !== false) ? ($row[$headIdx] ?? '') : ''
                    ];
                }
            }
            fclose($handle);
        }

        $cachedDetails = $activeDetails;
        return $cachedDetails;
    }

    /**
     * Get stop coordinates map
     */
    private function getStopCoordinatesMap(): array
    {
        static $coordsMap = null;
        if ($coordsMap !== null) return $coordsMap;

        $coordCacheFile = $this->getGtfsFile('stop_coords_cache.json');
        if (file_exists($coordCacheFile)) {
            $content = @file_get_contents($coordCacheFile);
            if ($content) {
                $decoded = json_decode($content, true);
                if (!empty($decoded)) {
                    $coordsMap = $decoded;
                    return $coordsMap;
                }
            }
        }

        // Fallback to database
        $map = [];
        try {
            if (function_exists('agglobusEnsureStops')) {
                agglobusEnsureStops();
            }
            $db = \Core\Database::getInstance();
            $rows = $db->query("SELECT stop_id, stop_name, lat, lon FROM agglobus_stops");
            foreach ($rows as $r) {
                $map[$r['stop_id']] = [
                    'stop_id' => $r['stop_id'],
                    'stop_name' => $r['stop_name'],
                    'lat' => (float)$r['lat'],
                    'lon' => (float)$r['lon']
                ];
            }
            if (!empty($map)) {
                @file_put_contents($coordCacheFile, json_encode($map));
            }
        } catch (\Throwable $e) {}

        $coordsMap = $map;
        return $coordsMap;
    }

    /**
     * Get today's simulated trip segments with precomputed times and coordinates
     */
    private function getTodaySimulatedTripSegments(): array
    {
        $dow = (int)date('w');
        $cacheFile = ROOT . "data/cache/agglobus_trips_sim_{$dow}.json";
        
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 86400) {
            $content = @file_get_contents($cacheFile);
            if ($content) {
                $data = json_decode($content, true);
                if (!empty($data)) return $data;
            }
        }

        $activeTrips = $this->getActiveTrips();
        $stopTimesFile = $this->getGtfsFile('stop_times.txt');
        $stopMap = $this->getStopCoordinatesMap();

        if (empty($activeTrips) || !file_exists($stopTimesFile)) {
            return [];
        }

        $tripSegments = [];
        $handle = @fopen($stopTimesFile, 'r');
        if ($handle) {
            $h = fgetcsv($handle, 0, ',', '"', '');
            $tIdx = array_search('trip_id', $h);
            $aIdx = array_search('arrival_time', $h);
            $sIdx = array_search('stop_id', $h);
            $seqIdx = array_search('stop_sequence', $h);

            while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                $tid = $row[$tIdx] ?? '';
                if (isset($activeTrips[$tid])) {
                    $timeStr = $row[$aIdx] ?? '00:00:00';
                    $parts = explode(':', $timeStr);
                    $sec = (int)($parts[0] ?? 0) * 3600 + (int)($parts[1] ?? 0) * 60 + (int)($parts[2] ?? 0);
                    $sid = $row[$sIdx] ?? '';
                    $sInfo = $stopMap[$sid] ?? null;

                    $tripSegments[$tid][] = [
                        'stop_id' => $sid,
                        'stop_name' => $sInfo['stop_name'] ?? $sid,
                        'lat' => $sInfo['lat'] ?? 0,
                        'lon' => $sInfo['lon'] ?? 0,
                        'seq' => (int)($row[$seqIdx] ?? 0),
                        'sec' => $sec
                    ];
                }
            }
            fclose($handle);
        }

        foreach ($tripSegments as $tid => &$segs) {
            usort($segs, function($a, $b) { return $a['seq'] - $b['seq']; });
        }
        unset($segs);

        if (!is_dir(dirname($cacheFile))) @mkdir(dirname($cacheFile), 0755, true);
        @file_put_contents($cacheFile, json_encode($tripSegments));

        return $tripSegments;
    }

    /**
     * Get scheduled arrivals from GTFS static data
     * Returns both upcoming passages (first 5) and the full daily timetable for this stop
     */
    public function getScheduledArrivals($stopId)
    {
        @error_reporting(0);
        date_default_timezone_set('Europe/Paris');
        $today = date('Ymd');
        $dow = (int)date('w');
        $now = date('H:i:s');
        $nowTs = time();
        
        $cacheFile = ROOT . "data/cache/agglobus_sched_{$stopId}_{$dow}_v2.json";
        $data = null;

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 86400) {
            $content = @file_get_contents($cacheFile);
            if ($content) {
                $data = json_decode($content, true);
            }
        }

        if ($data === null) {
            $validTrips = $this->getActiveTripDetails();
            $stopTimesFile = $this->getGtfsFile('stop_times.txt');
            $stopMap = $this->getStopCoordinatesMap();
            $stopInfo = $stopMap[$stopId] ?? null;

            if (empty($validTrips) || !file_exists($stopTimesFile)) {
                return ['stop_id' => $stopId, 'stop_name' => $stopId, 'lines' => [], 'upcoming' => [], 'all_day' => []];
            }

            $allDayArrivals = [];
            $connectedLines = [];
            $routeMap = $this->getRouteInfoMap();

            $handle = @fopen($stopTimesFile, 'r');
            if ($handle) {
                $header = fgetcsv($handle, 0, ',', '"', '');
                $tIdx = array_search('trip_id', $header);
                $sIdx = array_search('stop_id', $header);
                $aIdx = array_search('arrival_time', $header);
                while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                    if (($row[$sIdx] ?? '') == $stopId && isset($validTrips[$row[$tIdx]])) {
                        $trip = $validTrips[$row[$tIdx]];
                        $rid = $trip['route_id'];
                        $headsign = $trip['headsign'];
                        $rInfo = $routeMap[$rid] ?? null;

                        $timeStr = $row[$aIdx];
                        $allDayArrivals[] = [
                            'route_id' => $rid,
                            'short_name' => $rInfo ? $rInfo['short_name'] : $rid,
                            'color' => $rInfo ? $rInfo['color'] : '#00bfa5',
                            'text_color' => $rInfo ? $rInfo['text_color'] : '#ffffff',
                            'headsign' => $headsign ?: ($rInfo ? ('Ligne ' . $rInfo['short_name']) : $rid),
                            'arrival_time' => $timeStr,
                            'scheduled_time' => substr($timeStr, 0, 5),
                            'is_fallback' => true
                        ];

                        if (!isset($connectedLines[$rid])) {
                            $connectedLines[$rid] = [
                                'route_id' => $rid,
                                'short_name' => $rInfo ? $rInfo['short_name'] : $rid,
                                'color' => $rInfo ? $rInfo['color'] : '#00bfa5',
                                'text_color' => $rInfo ? $rInfo['text_color'] : '#ffffff',
                                'passages_count' => 0,
                                'destinations' => []
                            ];
                        }
                        $connectedLines[$rid]['passages_count']++;
                        if ($headsign && !in_array($headsign, $connectedLines[$rid]['destinations'])) {
                            $connectedLines[$rid]['destinations'][] = $headsign;
                        }
                    }
                }
                fclose($handle);
            }

            usort($allDayArrivals, function($a, $b) { return strcmp($a['arrival_time'], $b['arrival_time']); });
            uasort($connectedLines, function($a, $b) {
                return strnatcmp($a['short_name'] ?? '', $b['short_name'] ?? '');
            });

            $data = [
                'stop_id' => $stopId,
                'stop_name' => $stopInfo['stop_name'] ?? $stopId,
                'lat' => $stopInfo['lat'] ?? null,
                'lon' => $stopInfo['lon'] ?? null,
                'lines' => array_values($connectedLines),
                'all_day' => $allDayArrivals
            ];

            if (!is_dir(dirname($cacheFile))) @mkdir(dirname($cacheFile), 0755, true);
            @file_put_contents($cacheFile, json_encode($data));
        }

        // Build upcoming passages with countdown in minutes
        $upcoming = [];
        $allDayArrivals = $data['all_day'] ?? [];
        foreach ($allDayArrivals as $arr) {
            $arrCopy = $arr;
            $arrTime = date('Y-m-d') . ' ' . $arrCopy['arrival_time'];
            $arrTs = strtotime($arrTime);
            $arrCopy['time'] = $arrTs;
            $arrCopy['minutes'] = round(($arrTs - $nowTs) / 60);

            if ($arrCopy['arrival_time'] > $now) {
                $upcoming[] = $arrCopy;
            }
            if (count($upcoming) >= 10) break; // Top 10 upcoming for station board
        }

        return [
            'stop_id' => $data['stop_id'] ?? $stopId,
            'stop_name' => $data['stop_name'] ?? $stopId,
            'lat' => $data['lat'] ?? null,
            'lon' => $data['lon'] ?? null,
            'lines' => $data['lines'] ?? [],
            'upcoming' => $upcoming,
            'all_day' => $allDayArrivals
        ];
    }

    /**
     * Calculates simulated vehicle positions based on trip updates and stop coordinates
     */
    public function getSimulatedVehicles()
    {
        ob_start();
        $cachePath = $this->cachePath;
        $rtData = null;

        // 1. Get RT trip updates if available
        if (file_exists($cachePath) && (time() - filemtime($cachePath)) < 30) {
            $rtData = json_decode(file_get_contents($cachePath), true);
        } else {
            $json = @file_get_contents($this->rtUrl);
            if ($json) {
                $rtData = json_decode($json, true);
                @file_put_contents($cachePath, $json);
            }
        }

        if (!$rtData || !isset($rtData['entity'])) {
            // FALLBACK: If RT is binary, 404 or empty, simulate based on static schedule
            return $this->getStatisticallySimulatedVehicles();
        }

        // 2. Pre-fetch all stop coordinates to avoid multiple DB calls
        $stopIds = [];
        foreach ($rtData['entity'] as $entity) {
            if (!isset($entity['trip_update']['stop_time_update'])) continue;
            foreach ($entity['trip_update']['stop_time_update'] as $update) {
                $stopIds[] = $update['stop_id'];
            }
        }
        $stopIds = array_unique($stopIds);
        
        $stopMap = $this->getStopCoordinatesMap();
        $vehicles = [];
        $now = time();
        $routeMap = $this->getRouteInfoMap();

        // 3. Interpolate for each trip
        foreach ($rtData['entity'] as $entity) {
            if (!isset($entity['trip_update']['stop_time_update'])) continue;

            $updates = $entity['trip_update']['stop_time_update'];
            $routeId = $entity['trip_update']['trip']['route_id'] ?? '';
            $tripId = $entity['trip_update']['trip']['trip_id'] ?? '';

            for ($i = 0; $i < count($updates) - 1; $i++) {
                $curr = $updates[$i];
                $next = $updates[$i+1];

                $tCurr = $curr['departure']['time'] ?? $curr['arrival']['time'] ?? null;
                $tNext = $next['arrival']['time'] ?? $next['departure']['time'] ?? null;

                if ($tCurr && $tNext && $now >= $tCurr && $now <= $tNext) {
                    $prevStop = $stopMap[$curr['stop_id']] ?? null;
                    $nextStop = $stopMap[$next['stop_id']] ?? null;
                    
                    if ($prevStop && $nextStop) {
                        $duration = $tNext - $tCurr;
                        if ($duration > 0) {
                            $elapsed = $now - $tCurr;
                            $ratio = $elapsed / $duration;
                            
                            $lat = $prevStop['lat'] + ($nextStop['lat'] - $prevStop['lat']) * $ratio;
                            $lon = $prevStop['lon'] + ($nextStop['lon'] - $prevStop['lon']) * $ratio;
                            
                            $routeInfo = $routeMap[$routeId] ?? null;

                            $vehicles[] = [
                                'trip_id' => $tripId,
                                'route_id' => $routeId,
                                'short_name' => $routeInfo ? $routeInfo['short_name'] : $routeId,
                                'color' => $routeInfo ? $routeInfo['color'] : '#00bfa5',
                                'lat' => $lat,
                                'lon' => $lon,
                                'prev_stop' => $prevStop['stop_name'],
                                'next_stop' => $nextStop['stop_name'],
                                'next_stop_id' => $nextStop['stop_id']
                            ];
                        }
                        break;
                    }
                }
            }
        }

        if (empty($vehicles)) {
            return $this->getStatisticallySimulatedVehicles();
        }

        ob_clean();
        header('Content-Type: application/json');
        echo json_encode($vehicles);
        exit;
    }

    /**
     * Simulates vehicles based solely on static GTFS schedule (Statistical Simulation)
     */
    private function getStatisticallySimulatedVehicles()
    {
        ob_start();
        date_default_timezone_set('Europe/Paris');
        $nowParts = explode(':', date('H:i:s'));
        $nowSec = (int)$nowParts[0] * 3600 + (int)$nowParts[1] * 60 + (int)$nowParts[2];
        
        $activeTrips = $this->getActiveTrips();
        $tripSegments = $this->getTodaySimulatedTripSegments();
        $routeMap = $this->getRouteInfoMap();
        $vehicles = [];

        foreach ($tripSegments as $tid => $segs) {
            $count = count($segs);
            if ($count < 2) continue;

            $firstSec = $segs[0]['sec'];
            $lastSec = $segs[$count - 1]['sec'];
            if ($nowSec < $firstSec || $nowSec > $lastSec) continue;

            for ($i = 0; $i < $count - 1; $i++) {
                $curr = $segs[$i];
                $next = $segs[$i+1];
                if ($nowSec >= $curr['sec'] && $nowSec <= $next['sec']) {
                    $dur = $next['sec'] - $curr['sec'];
                    $ratio = ($dur > 0) ? ($nowSec - $curr['sec']) / $dur : 0;
                    $routeId = $activeTrips[$tid] ?? '';
                    $routeInfo = $routeMap[$routeId] ?? null;

                    $vehicles[] = [
                        'trip_id' => $tid,
                        'route_id' => $routeId,
                        'short_name' => $routeInfo ? $routeInfo['short_name'] : $routeId,
                        'color' => $routeInfo ? $routeInfo['color'] : '#00bfa5',
                        'lat' => $curr['lat'] + ($next['lat'] - $curr['lat']) * $ratio,
                        'lon' => $curr['lon'] + ($next['lon'] - $curr['lon']) * $ratio,
                        'prev_stop' => $curr['stop_name'],
                        'next_stop' => $next['stop_name'],
                        'next_stop_id' => $next['stop_id']
                    ];
                    break;
                }
            }
        }

        ob_clean();
        header('Content-Type: application/json');
        echo json_encode($vehicles);
        exit;
    }
}
