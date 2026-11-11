<?php

defined('ROOT') or exit('Access denied!');

/**
 * Plugin Agglobus - Système d'Information Voyageurs (SIV) en temps réel
 */

// 1. Autoloader PSR-4 style
spl_autoload_register(function ($class) {
    if (strpos($class, 'Agglobus\\') === 0) {
        $relativeClass = substr($class, 9);
        $path = str_replace('\\', '/', $relativeClass);
        
        // Check controllers
        if (strpos($path, 'Controllers/') === 0) {
            $file = __DIR__ . '/controllers/' . substr($path, 12) . '.php';
        } else {
            $file = __DIR__ . '/src/' . $path . '.php';
        }

        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// 2. Chargement des Routes
if (file_exists(__DIR__ . '/param/routes.php')) {
    require_once __DIR__ . '/param/routes.php';
}

/**
 * Fonctions d'installation et initialisation
 */
function agglobusEnsureStops(): void {
    static $initialized = false;
    if ($initialized) {
        return;
    }

    try {
        $db = \Core\Database::getInstance();
        $pdo = $db->getPdo();
        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $isMysql = strpos($driver, 'mysql') !== false;

        if ($isMysql) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS agglobus_stops (
                stop_id VARCHAR(50) PRIMARY KEY,
                stop_name VARCHAR(255) NOT NULL,
                lat DOUBLE NOT NULL,
                lon DOUBLE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_stop_name (stop_name),
                INDEX idx_coords (lat, lon)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            $pdo->exec("CREATE TABLE IF NOT EXISTS agglobus_stops (
                stop_id VARCHAR(50) PRIMARY KEY,
                stop_name VARCHAR(255) NOT NULL,
                lat DOUBLE NOT NULL,
                lon DOUBLE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        }

        $count = (int)$pdo->query("SELECT COUNT(*) FROM agglobus_stops")->fetchColumn();
        if ($count > 0) {
            $initialized = true;
            return;
        }

        $candidates = [
            defined('DATA') ? DATA . 'gtfs_data/stops.txt' : null,
            defined('ROOT') ? ROOT . 'data/gtfs_data/stops.txt' : null,
            dirname(__DIR__, 2) . '/data/gtfs_data/stops.txt'
        ];
        $gtfsStops = null;
        foreach ($candidates as $c) {
            if ($c && file_exists($c)) {
                $gtfsStops = $c;
                break;
            }
        }

        if ($count === 0 && $gtfsStops) {
            $f = fopen($gtfsStops, 'r');
            if ($f) {
                $headers = fgetcsv($f, 0, ',', '"', '');
                $idIdx = array_search('stop_id', $headers);
                $nameIdx = array_search('stop_name', $headers);
                $latIdx = array_search('stop_lat', $headers);
                $lonIdx = array_search('stop_lon', $headers);

                if ($idIdx !== false && $nameIdx !== false && $latIdx !== false && $lonIdx !== false) {
                    if ($isMysql) {
                        $sql = "INSERT INTO agglobus_stops (stop_id, stop_name, lat, lon) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE stop_name=VALUES(stop_name), lat=VALUES(lat), lon=VALUES(lon)";
                    } else {
                        $sql = "INSERT OR REPLACE INTO agglobus_stops (stop_id, stop_name, lat, lon) VALUES (?, ?, ?, ?)";
                    }
                    $stmt = $pdo->prepare($sql);
                    $pdo->beginTransaction();
                    while (($row = fgetcsv($f, 0, ',', '"', '')) !== false) {
                        $sid = trim($row[$idIdx] ?? '');
                        $sname = trim($row[$nameIdx] ?? '');
                        $lat = (float)($row[$latIdx] ?? 0);
                        $lon = (float)($row[$lonIdx] ?? 0);
                        if ($sid !== '' && $sname !== '' && $lat != 0.0 && $lon != 0.0) {
                            $stmt->execute([$sid, $sname, $lat, $lon]);
                        }
                    }
                    $pdo->commit();
                }
                fclose($f);
            }
        }
    } catch (\Throwable $e) {
        // Silently skip if DB not accessible during boot
    }
}

function agglobusInstall(): void {
    agglobusEnsureStops();
}
