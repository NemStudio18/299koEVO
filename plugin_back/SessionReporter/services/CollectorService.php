<?php

namespace SessionReporter\Services;

use Core\Core;
use Core\Logger;
use Utils\Util;

/**
 * Service to collect and buffer debug events into SQLite.
 * Handles normalization, smart purge, and plugin auditing.
 */
class CollectorService
{
    private static ?CollectorService $instance = null;
    private ?\PDO $db = null;
    private string $dbPath;
    private int $maxRowsPerSession = 50;
    private int $maxRowsTotal = 2000;
    private string $batchId;

    private function __construct()
    {
        $this->dbPath = DATA . 'SessionReporter' . DS . 'debug.sqlite';
        $this->batchId = bin2hex(random_bytes(4));
        
        if (!is_dir(dirname($this->dbPath))) {
            @mkdir(dirname($this->dbPath), 0755, true);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getDb(): ?\PDO
    {
        if ($this->db !== null) {
            return $this->db;
        }

        try {
            $isNew = !file_exists($this->dbPath);
            $this->db = new \PDO("sqlite:" . $this->dbPath);
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

            // SQLite Optimizations
            $this->db->exec("PRAGMA journal_mode=WAL");
            $this->db->exec("PRAGMA synchronous=NORMAL");

            if ($isNew) {
                $this->createSchema();
            }
        } catch (\Exception $e) {
            Core::getInstance()->getLogger()->error("SessionReporter: SQLite Connection Error: " . $e->getMessage());
            return null;
        }

        return $this->db;
    }

    private function createSchema(): void
    {
        $queries = [
            "CREATE TABLE IF NOT EXISTS events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                debug_session_id TEXT,
                request_id TEXT,
                insert_batch_id TEXT,
                type TEXT,
                fingerprint TEXT,
                content TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS beta_codes (
                code TEXT PRIMARY KEY,
                generated_by_admin_id INTEGER,
                used_by_user_id INTEGER NULL,
                used_at DATETIME NULL,
                expires_at DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE INDEX IF NOT EXISTS idx_session ON events (debug_session_id)",
            "CREATE INDEX IF NOT EXISTS idx_request ON events (request_id)",
            "CREATE INDEX IF NOT EXISTS idx_fingerprint ON events (fingerprint)",
            "CREATE INDEX IF NOT EXISTS idx_created ON events (created_at)"
        ];

        foreach ($queries as $q) {
            $this->db->exec($q);
        }
    }

    /**
     * Logs an event into the buffer.
     */
    public function logEvent(string $type, array $data, ?string $sessionId = null): void
    {
        $db = $this->getDb();
        if (!$db) return;

        $sessionId = $sessionId ?: ($_SESSION['debug_session_id'] ?? 'guest');
        $requestId = defined('REQUEST_ID') ? REQUEST_ID : 'none';

        // Advanced Normalization & Fingerprinting
        $fingerprint = $this->calculateFingerprint($type, $data);
        
        try {
            // Enrich data with CORE_VERSION
            $data['core_version'] = defined('VERSION') ? VERSION : 'unknown';
            
            $stmt = $db->prepare("INSERT INTO events (debug_session_id, request_id, insert_batch_id, type, fingerprint, content) 
                                 VALUES (:sid, :rid, :bid, :type, :fp, :content)");
            $stmt->execute([
                ':sid' => $sessionId,
                ':rid' => $requestId,
                ':bid' => $this->batchId,
                ':type' => $type,
                ':fp' => $fingerprint,
                ':content' => json_encode($data)
            ]);

            // Smart Purge: 10% chance or if total rows check exceeds threshold
            if (mt_rand(1, 100) === 1) {
                $this->smartPurge();
            }
        } catch (\Exception $e) {
            Core::getInstance()->getLogger()->error("SessionReporter: Failed to log event: " . $e->getMessage());
        }
    }

    /**
     * Normalizes message and calculates a stable fingerprint.
     */
    private function calculateFingerprint(string $type, array $data): string
    {
        $file = $data['file'] ?? 'unknown';
        $line = $data['line'] ?? 0;
        $msg = $data['message'] ?? '';
        $topFunction = $this->extractTopFunction($data['stack'] ?? '');

        // Normalize message: mask dynamic variables
        $normMsg = $this->normalizeMessage($msg);

        // Fingerprint includes version context to avoid grouping across major updates
        return sha1(json_encode([
            'type' => $type,
            'msg'  => $normMsg,
            'file' => $file,
            'line' => $line,
            'func' => $topFunction,
            'core' => defined('VERSION') ? VERSION : '1.0'
        ]));
    }

    private function normalizeMessage(string $message): string
    {
        // Replace numbers
        $message = preg_replace('/\b\d+\b/', '{int}', $message);
        // Replace UUIDs
        $message = preg_replace('/[a-f0-9-]{36}/i', '{uuid}', $message);
        // Replace long hashes (32+ chars)
        $message = preg_replace('/[a-f0-9]{32,}/i', '{hash}', $message);

        return $message;
    }

    private function extractTopFunction(string $stack): string
    {
        if (empty($stack)) return 'unknown';
        // Simple heuristic to find the first function call in a PHP stack trace
        if (preg_match('/at\s+([a-zA-Z0-9_\\\]+->[a-zA-Z0-9_]+|[a-zA-Z0-9_\\\]+::[a-zA-Z0-9_]+)\(/i', $stack, $matches)) {
            return $matches[1];
        }
        return 'main';
    }

    /**
     * Cleans up the database to avoid write-locks and disk explosion.
     */
    private function smartPurge(): void
    {
        $db = $this->getDb();
        if (!$db) return;

        try {
            $count = (int)$db->query("SELECT COUNT(*) FROM events")->fetchColumn();
            if ($count > $this->maxRowsTotal) {
                $limit = $count - $this->maxRowsTotal + 200; // Purge some extra
                $db->exec("DELETE FROM events WHERE id IN (SELECT id FROM events ORDER BY created_at ASC LIMIT $limit)");
            }
        } catch (\Exception $e) {
            // Passive failure
        }
    }

    /**
     * Snapshot active plugins with version and priority.
     */
    public function getPluginAudit(): array
    {
        $audit = [];
        $pluginsManager = \Core\Plugin\PluginsManager::getInstance();
        
        foreach ($pluginsManager->getPlugins() as $id => $plugin) {
            $audit[$id] = [
                'active' => (bool)$plugin->getConfigVal('activate'),
                'version' => $plugin->getConfigVal('version') ?: 'unknown',
                'priority' => $plugin->getConfigVal('priority') ?: 0
            ];
        }
        
        return $audit;
    }
    
    /**
     * Normalize Route URI (remove dynamic segments).
     */
    public function normalizeUri(string $uri): string
    {
        $uri = strtok($uri, '?'); // Remove query string
        $segments = explode('/', trim($uri, '/'));

        foreach ($segments as &$segment) {
            if (ctype_digit($segment)) {
                $segment = '{id}';
            } elseif (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $segment)) {
                // Compatible with old UUID regex in previous implementation but refined
                $segment = '{uuid}';
            } elseif (preg_match('/^[a-f0-9-]{36}$/i', $segment)) {
                $segment = '{uuid}';
            } elseif (strlen($segment) > 20 && preg_match('/^[a-zA-Z0-9_-]+$/', $segment)) {
                $segment = '{slug}';
            }
        }

        return '/' . implode('/', $segments);
    }
}
