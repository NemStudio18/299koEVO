<?php

namespace Core\Monitor;

defined('ROOT') or exit('Access denied!');

use Utils\Util;

/**
 * SystemMonitor - Moteur de télémétrie et monitoring technique souverain.
 * 
 * Mesure en temps réel :
 * - Temps de réponse du Core et des contrôleurs (en ms).
 * - Pic de consommation mémoire RAM PHP et allocations.
 * - Chronométrage unitaire de l'exécution des Hooks (identification des plugins lents).
 * - Volume d'I/O par page (lectures/écritures JSON, requêtes SQL).
 */
class SystemMonitor
{
    private static ?SystemMonitor $instance = null;
    private static bool $initialized = false;
    private static float $startTime = 0.0;
    private static int $startMemory = 0;
    private static array $currentHooks = [];
    private static array $ioCounters = [
        'json_read' => 0,
        'json_write' => 0,
        'sql_query' => 0,
        'sql_execute' => 0,
    ];

    private string $metricsFile;
    private const MAX_ROLLING_REQUESTS = 50;

    public function __construct()
    {
        $dir = defined('DATA_CORE') ? DATA_CORE : (defined('ROOT') ? ROOT . 'data/core/' : sys_get_temp_dir() . '/');
        $this->metricsFile = $dir . 'system_metrics.json';
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Démarre l'enregistrement de la requête en cours.
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$startTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
        self::$startMemory = memory_get_usage();
        self::$initialized = true;

        register_shutdown_function([self::class, 'onShutdown']);
    }

    /**
     * Enregistre l'exécution d'un Hook spécifique.
     */
    public static function recordHook(string $hookName, float $durationMs, string $handler = ''): void
    {
        self::$currentHooks[] = [
            'hook' => $hookName,
            'duration' => round($durationMs, 3),
            'handler' => $handler,
        ];
    }

    /**
     * Incrémente un compteur d'I/O (json_read, json_write, sql_query, etc.).
     */
    public static function incrementIo(string $type): void
    {
        if (isset(self::$ioCounters[$type])) {
            self::$ioCounters[$type]++;
        } else {
            self::$ioCounters[$type] = 1;
        }
    }

    /**
     * Capture à l'extinction du script PHP les métriques de la requête.
     */
    public static function onShutdown(): void
    {
        try {
            $uri = $_SERVER['REQUEST_URI'] ?? '';

            // Ignorer les requêtes CLI, assets statiques ou les endpoints internes de polling (monitor / check-updates)
            if (empty($uri) || str_contains($uri, '/admin/api/monitor') || str_contains($uri, '/admin/api/check-updates') || str_contains($uri, '/pwa/')) {
                return;
            }

            // Ignorer si safeguard asset 404
            if (http_response_code() === 404 && preg_match('/\.(css|js|png|jpg|ico|woff2?|map)$/i', $uri)) {
                return;
            }

            $endTime = microtime(true);
            $durationMs = round(($endTime - self::$startTime) * 1000, 2);
            $peakMemoryMb = round(memory_get_peak_usage(true) / (1024 * 1024), 2);
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $statusCode = http_response_code() ?: 200;

            $monitor = self::getInstance();
            $monitor->persistRequestMetrics([
                'timestamp' => date('Y-m-d H:i:s'),
                'uri' => $uri,
                'method' => $method,
                'status' => $statusCode,
                'duration_ms' => $durationMs,
                'peak_memory_mb' => $peakMemoryMb,
                'hooks_count' => count(self::$currentHooks),
                'hooks_time_ms' => round(array_sum(array_column(self::$currentHooks, 'duration')), 2),
                'io' => self::$ioCounters,
                'hooks' => self::$currentHooks,
            ]);
        } catch (\Throwable $e) {
            // Silencieux pour ne jamais bloquer la réponse client
        }
    }

    /**
     * Enregistre les métriques en base JSON avec fenêtre glissante et agrégats.
     */
    public function persistRequestMetrics(array $requestData): void
    {
        $data = $this->loadMetricsData();

        // 1. Mise à jour de l'historique glissant
        array_unshift($data['requests'], [
            'timestamp' => $requestData['timestamp'],
            'uri' => $requestData['uri'],
            'method' => $requestData['method'],
            'status' => $requestData['status'],
            'duration_ms' => $requestData['duration_ms'],
            'peak_memory_mb' => $requestData['peak_memory_mb'],
            'hooks_count' => $requestData['hooks_count'],
            'hooks_time_ms' => $requestData['hooks_time_ms'],
            'io' => $requestData['io'],
        ]);

        if (count($data['requests']) > self::MAX_ROLLING_REQUESTS) {
            $data['requests'] = array_slice($data['requests'], 0, self::MAX_ROLLING_REQUESTS);
        }

        // 2. Agrégation globale
        $data['summary']['total_requests']++;
        $data['summary']['total_duration_ms'] += $requestData['duration_ms'];
        $data['summary']['avg_duration_ms'] = round($data['summary']['total_duration_ms'] / $data['summary']['total_requests'], 2);
        $data['summary']['max_duration_ms'] = max($data['summary']['max_duration_ms'], $requestData['duration_ms']);

        $data['summary']['total_memory_mb'] += $requestData['peak_memory_mb'];
        $data['summary']['avg_memory_mb'] = round($data['summary']['total_memory_mb'] / $data['summary']['total_requests'], 2);
        $data['summary']['max_memory_mb'] = max($data['summary']['max_memory_mb'], $requestData['peak_memory_mb']);

        $data['summary']['total_json_reads'] += ($requestData['io']['json_read'] ?? 0);
        $data['summary']['total_json_writes'] += ($requestData['io']['json_write'] ?? 0);
        $data['summary']['total_sql_queries'] += ($requestData['io']['sql_query'] ?? 0) + ($requestData['io']['sql_execute'] ?? 0);

        // 3. Profilage des Hooks
        if (!empty($requestData['hooks'])) {
            foreach ($requestData['hooks'] as $h) {
                $hookName = $h['hook'];
                $duration = $h['duration'];
                $handler = $h['handler'] ?? '';

                if (!isset($data['hooks_profiler'][$hookName])) {
                    $data['hooks_profiler'][$hookName] = [
                        'count' => 0,
                        'total_ms' => 0.0,
                        'avg_ms' => 0.0,
                        'max_ms' => 0.0,
                        'slowest_handler' => '',
                    ];
                }

                $hp = &$data['hooks_profiler'][$hookName];
                $hp['count']++;
                $hp['total_ms'] = round($hp['total_ms'] + $duration, 2);
                $hp['avg_ms'] = round($hp['total_ms'] / $hp['count'], 2);
                if ($duration >= $hp['max_ms']) {
                    $hp['max_ms'] = $duration;
                    if (!empty($handler)) {
                        $hp['slowest_handler'] = $handler;
                    }
                }
            }
        }

        $data['last_updated'] = date('Y-m-d H:i:s');

        @file_put_contents($this->metricsFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    /**
     * Charge les données de métriques existantes.
     */
    private function loadMetricsData(): array
    {
        if (file_exists($this->metricsFile)) {
            $content = @file_get_contents($this->metricsFile);
            $decoded = json_decode((string)$content, true);
            if (is_array($decoded) && isset($decoded['summary'])) {
                return $decoded;
            }
        }

        return [
            'summary' => [
                'total_requests' => 0,
                'total_duration_ms' => 0.0,
                'avg_duration_ms' => 0.0,
                'max_duration_ms' => 0.0,
                'total_memory_mb' => 0.0,
                'avg_memory_mb' => 0.0,
                'max_memory_mb' => 0.0,
                'total_json_reads' => 0,
                'total_json_writes' => 0,
                'total_sql_queries' => 0,
            ],
            'requests' => [],
            'hooks_profiler' => [],
            'created_at' => date('Y-m-d H:i:s'),
            'last_updated' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Récupère le rapport complet de monitoring technique avec indicateurs serveur en direct.
     */
    public function getMetrics(): array
    {
        $data = $this->loadMetricsData();

        // Trier le profilage des hooks du plus consommateur au moins consommateur
        if (!empty($data['hooks_profiler'])) {
            uasort($data['hooks_profiler'], fn($a, $b) => $b['total_ms'] <=> $a['total_ms']);
        }

        // Télémétrie instantanée du serveur
        $data['server_live'] = [
            'php_version' => PHP_VERSION,
            'os' => PHP_OS,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI/Standalone',
            'memory_limit' => ini_get('memory_limit') ?: 'N/A',
            'current_memory_mb' => round(memory_get_usage(true) / (1024 * 1024), 2),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / (1024 * 1024), 2),
            'opcache_enabled' => function_exists('opcache_get_status') && !empty(opcache_get_status()['opcache_enabled']),
            'load_average' => function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0],
        ];

        return $data;
    }

    /**
     * Réinitialise l'ensemble des métriques enregistrées.
     */
    public function reset(): bool
    {
        if (file_exists($this->metricsFile)) {
            @unlink($this->metricsFile);
        }
        return true;
    }
}

