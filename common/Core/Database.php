<?php

namespace Core;

use Core\Interfaces\SqlDriverInterface;
use PDO;
use PDOException;

defined('ROOT') or exit('Access denied!');

/**
 * Gestion centralisée des connexions Base de Données (Singleton).
 * Supporte MySQL, PostgreSQL et SQLite3 simultanément.
 */
class Database implements SqlDriverInterface
{
    private static $instance = null;
    private $connections = [];
    private $configs = [];
    private $storageConfig = [];
    private $activeConnection = 'default';

    private function __construct()
    {
        $this->loadConfig();
    }

    private function __clone()
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Charge toutes les configurations de base de données depuis env.ini
     */
    private function loadConfig(): void
    {
        $envIniFile = defined('DATA') ? DATA . 'env.ini' : __DIR__ . '/../../data/env.ini';
        $envJsonFile = defined('DATA') ? DATA . 'env.json' : __DIR__ . '/../../data/env.json';

        // 1. Priorité : Env.json
        if (file_exists($envJsonFile)) {
            $json = json_decode(file_get_contents($envJsonFile), true);
            if (is_array($json)) {
                $this->configs['default'] = $json['database'] ?? [];
                if (isset($json['databases']) && is_array($json['databases'])) {
                    foreach ($json['databases'] as $name => $conf) {
                        $this->configs[$name] = $conf;
                    }
                }
                $this->storageConfig = $json['storage'] ?? [];
            }
        } 
        // 2. Fallback : Env.ini
        elseif (file_exists($envIniFile)) {
            $env = parse_ini_file($envIniFile, true);
            if ($env) {
                foreach ($env as $section => $values) {
                    if ($section === 'database') {
                        $this->configs['default'] = $values;
                    } elseif (strpos($section, 'database:') === 0) {
                        $name = substr($section, 9);
                        $this->configs[$name] = $values;
                    } elseif ($section === 'storage') {
                        $this->storageConfig = $values;
                    }
                }
            }
            // Auto-migration: Create json from ini if json doesn't exist
            if (!empty($this->configs['default'])) {
                $this->saveConfig(['database' => $this->configs['default'], 'storage' => $this->storageConfig]);
            }
        }

        // 3. Fallback Legacy searchEVO (uniquement si pas de config 'database' principale)
        if (empty($this->configs['default'])) {
            $confFile = defined('DATA_PLUGIN')
                ? DATA_PLUGIN . 'searchEVO' . DIRECTORY_SEPARATOR . 'search_conf.json'
                : __DIR__ . '/../../data/plugin/searchEVO/search_conf.json';

            if (file_exists($confFile)) {
                $json = json_decode(file_get_contents($confFile), true);
                if (isset($json['sql_host'], $json['sql_db'])) {
                    $this->configs['default'] = [
                        'host' => $json['sql_host'],
                        'dbname' => $json['sql_db'],
                        'user' => $json['sql_user'] ?? '',
                        'password' => $json['sql_pass'] ?? '',
                        'driver' => 'mysql',
                        'charset' => 'utf8mb4'
                    ];
                    // Save inferred config to new env.json
                    $this->saveConfig(['database' => $this->configs['default'], 'storage' => ['default' => 'json']]);
                }
            }
        }
        
        // 4. Create default if nothing exists
        if (empty($this->configs['default'])) {
             $defaultConfig = [
                'database' => [
                    'driver' => 'mysql',
                    'host' => 'localhost',
                    'dbname' => 'vivrebourges2',
                    'user' => 'root',
                    'password' => '',
                    'charset' => 'utf8mb4'
                ],
                'storage' => [
                    'default' => 'json',
                    'User' => 'json',
                    'Page' => 'json'
                ]
            ];
             $this->saveConfig($defaultConfig);
             $this->configs['default'] = $defaultConfig['database'];
             $this->storageConfig = $defaultConfig['storage'];
        }
    }

    public function getConfig(string $key, $default = null)
    {
        return $this->storageConfig[$key] ?? $this->configs['default'][$key] ?? $default;
    }

    /**
     * Définit dynamiquement une règle de stockage pour un modèle
     */
    public function setStorageConfig(string $key, $value): void
    {
        $this->storageConfig[$key] = $value;
    }

    /**
     * Retourne toute la configuration (database + storage)
     */
    public function getFullConfig(): array
    {
        return [
            'database' => $this->configs['default'] ?? [],
            'databases' => array_diff_key($this->configs, ['default' => true]),
            'storage' => $this->storageConfig ?? []
        ];
    }

    public function hasConnection(string $name): bool
    {
        return isset($this->configs[$name]);
    }

    public function getConnectionsList(): array
    {
        return array_keys($this->configs);
    }

    public function getPdo(string $name = 'default'): PDO
    {
        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        // Support dynamique pour SQLite par plugin (sqlite:{plugin_id})
        if (!isset($this->configs[$name]) && strpos($name, 'sqlite:') === 0) {
            $pluginId = substr($name, 7);
            $this->configs[$name] = [
                'driver' => 'sqlite',
                'path' => (defined('DATA') ? DATA : __DIR__ . '/../../data/') . 'storage' . DIRECTORY_SEPARATOR . $pluginId . '.sqlite'
            ];
        }

        try {
            return $this->createConnection($name);
        } catch (PDOException $e) {
            // On enrichit l'erreur avec le nom de la connexion pour le debug
            throw new PDOException("Connexion '$name' échouée : " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public function setActiveConnection(string $name): self
    {
        $this->activeConnection = $name;
        return $this;
    }

    public function query(string $sql, array $params = [], ?string $connection = null): array
    {
        Core::getInstance()->addQueryCounter();
        
        $conn = $connection ?: $this->activeConnection;
        $testMode = \Core\Auth\AdminTestMode::getInstance();
        if ($testMode->isActive()) {
             // Si c'est une lecture et qu'on a des données volatiles pour cette table
             if (preg_match('/FROM\s+([a-zA-Z0-9_]+)/i', $sql, $matches)) {
                 $table = $matches[1];
                 $volatileRows = $testMode->getVolatileRows($table);
                 if (!empty($volatileRows)) {
                     $stmt = $this->getPdo($conn)->prepare($sql);
                     $realRows = $stmt->execute($params) ? $stmt->fetchAll() : [];
                     return array_merge($realRows, $volatileRows);
                 }
             }
        }

        \Core\Storage\EmergencyLogger::log("DB_QUERY: $sql params=" . json_encode($params) . " conn=$conn");
        $stmt = $this->getPdo($conn)->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function execute(string $sql, array $params = [], ?string $connection = null): bool
    {
        Core::getInstance()->addQueryCounter();

        $conn = $connection ?: $this->activeConnection;
        $testMode = \Core\Auth\AdminTestMode::getInstance();
        if ($testMode->isActive() && $this->isWriteQuery($sql)) {
            $testMode->recordSqlWrite($sql, $params);
            
            // Simulation ultra-basique pour 'offers'
            if (strpos(strtoupper($sql), 'INSERT INTO OFFERS') !== false) {
                // On simule une ligne vide ou partielle pour que l'UI sache qu'il y a quelque chose
                $testMode->addVolatileRow('offers', [
                    'title' => 'Offre simulée (Mode Test)',
                    'status' => 'approved',
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
            return true;
        }

        return $this->getPdo($conn)->prepare($sql)->execute($params);
    }

    private function isWriteQuery(string $sql): bool
    {
        $sql = trim(strtoupper($sql));
        return (strpos($sql, 'INSERT') === 0 || strpos($sql, 'UPDATE') === 0 || strpos($sql, 'DELETE') === 0 || strpos($sql, 'REPLACE') === 0);
    }

    public function lastInsertId(?string $name = null): string
    {
        return $this->getPdo($this->activeConnection)->lastInsertId($name);
    }

    public function getDriverName(): string
    {
        return $this->configs[$this->activeConnection]['driver'] ?? 'mysql';
    }

    /**
     * Retourne le résolveur de dialecte pour la connexion active.
     */
    public function getDialect(): \Core\Interfaces\SqlDialectInterface
    {
        return \Core\Storage\DialectResolver::resolve($this->getDriverName());
    }

    public function beginTransaction(): bool
    {
        return $this->getPdo($this->activeConnection)->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->getPdo($this->activeConnection)->commit();
    }

    public function rollBack(): bool
    {
        return $this->getPdo($this->activeConnection)->rollBack();
    }

    private function createConnection(string $name): PDO
    {
        $config = $this->configs[$name] ?? $this->configs['default'] ?? null;
        if (!$config) {
            \Core\Storage\EmergencyLogger::log("DB_CREATE_ERROR: Config not found for '$name'");
            throw new PDOException("Configuration pour la connexion '$name' introuvable.");
        }

        $driver = $config['driver'] ?? 'mysql';
        \Core\Storage\EmergencyLogger::log("DB_CREATE_START: name=$name driver=$driver");
        switch ($driver) {
            case 'pgsql':
                return $this->createPgsqlConnection($name, $config);
            case 'sqlite':
                return $this->createSqliteConnection($config['path'] ?? $config['dbname'] ?? (DATA . "database_$name.sqlite"), $name);
            default:
                return $this->createMysqlConnection($name, $config);
        }
    }

    private function createMysqlConnection(string $name, array $config): PDO
    {
        $port = !empty($config['port']) ? ';port=' . (int)$config['port'] : '';
        $dsn = sprintf(
            'mysql:host=%s%s;dbname=%s;charset=%s',
            $config['host'] ?? 'localhost',
            $port,
            $config['dbname'] ?? '',
            $config['charset'] ?? 'utf8mb4'
        );
        \Core\Storage\EmergencyLogger::log("DB_MYSQL_DSN: $dsn");
        try {
            $pdo = new PDO($dsn, $config['user'] ?? '', $config['password'] ?? '', $this->getSafeOptions());
            $this->connections[$name] = $pdo;
            return $pdo;
        } catch (\PDOException $e) {
            \Core\Storage\EmergencyLogger::log("DB_MYSQL_ERROR: " . $e->getMessage());
            throw $e;
        }
    }

    private function createPgsqlConnection(string $name, array $config): PDO
    {
        $dsn = sprintf('pgsql:host=%s;dbname=%s', $config['host'], $config['dbname']);
        $pdo = new PDO($dsn, $config['user'], $config['password'], $this->getSafeOptions());
        $this->connections[$name] = $pdo;
        return $pdo;
    }

    private function createSqliteConnection(string $path, string $name): PDO
    {
        if (!is_dir(dirname($path)))
            @mkdir(dirname($path), 0755, true);
        $pdo = new PDO('sqlite:' . $path, null, null, $this->getSafeOptions());
        $this->connections[$name] = $pdo;
        return $pdo;
    }

    private function getSafeOptions(): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::ATTR_TIMEOUT => 2
        ];
    }

    public function getSqlitePdo(string $path): PDO
    {
        return $this->createSqliteConnection($path, 'sqlite_manual');
    }

    /**
     * Sauvegarde la configuration dans env.json
     */
    public function saveConfig(array $data): bool
    {
        $envJsonFile = defined('DATA') ? DATA . 'env.json' : __DIR__ . '/../../data/env.json';
        
        $currentData = [];
        if (file_exists($envJsonFile)) {
            $currentData = json_decode(file_get_contents($envJsonFile), true) ?? [];
        }

        // Si on reçoit juste un driver/host, on considère que c'est une mise à jour de 'database' (connexion par défaut)
        if (isset($data['driver']) || isset($data['host']) || isset($data['dbname'])) {
            $currentData['database'] = array_merge($currentData['database'] ?? [], $data);
        } else {
            // Sinon on fusionne les sections de haut niveau
            if (isset($data['database']) && is_array($data['database'])) {
                $currentData['database'] = array_merge($currentData['database'] ?? [], $data['database']);
            }
            if (isset($data['databases']) && is_array($data['databases'])) {
                $currentData['databases'] = array_merge($currentData['databases'] ?? [], $data['databases']);
            }
            if (isset($data['storage']) && is_array($data['storage'])) {
                $currentData['storage'] = array_merge($currentData['storage'] ?? [], $data['storage']);
            }
            
            // Si c'est un full config, on peut aussi avoir d'autres clés, on les préserve
            foreach ($data as $key => $val) {
                if (!in_array($key, ['database', 'databases', 'storage'])) {
                    $currentData[$key] = $val;
                }
            }
        }
        $data = $currentData;

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
             \Core\Storage\EmergencyLogger::log("DB_SAVE_ERROR: JSON encoding failed");
             return false;
        }

        $res = file_put_contents($envJsonFile, $json);
        if ($res === false) {
            \Core\Storage\EmergencyLogger::log("DB_SAVE_ERROR: Failed to write to $envJsonFile (Check permissions)");
            return false;
        }
        
        return true;
    }
}