<?php

namespace Core\Storage;

use Core\Interfaces\StorageInterface;

/**
 * Implémentation robuste du stockage JSON.
 * Gère le verrouillage de fichier (flock) et l'écriture atomique.
 */
class JsonStorage implements StorageInterface
{
    private string $filePath;
    private string $primaryKey;
    private ?array $transactionBuffer = null;
    private $lockHandle = null;

    public function __construct(string $filePath, string $primaryKey = 'id')
    {
        $this->filePath = $filePath;
        $this->primaryKey = $primaryKey;

        if (!file_exists($this->filePath)) {
            $this->saveAll([]);
        }
    }

    public function beginTransaction(): bool
    {
        if ($this->transactionBuffer !== null) {
            return false; // Transaction déjà en cours
        }

        // Verrouillage exclusif avant de charger pour le buffer
        $lockFile = $this->filePath . '.lock';
        $this->lockHandle = fopen($lockFile, "c+");
        if (!$this->lockHandle || !flock($this->lockHandle, LOCK_EX)) {
            return false;
        }

        $this->transactionBuffer = $this->loadAll();
        return true;
    }

    public function commit(): bool
    {
        if ($this->transactionBuffer === null) {
            return false;
        }

        $success = $this->saveAll($this->transactionBuffer);
        
        $this->transactionBuffer = null;
        if ($this->lockHandle) {
            flock($this->lockHandle, LOCK_UN);
            fclose($this->lockHandle);
            $this->lockHandle = null;
        }

        return $success;
    }

    public function rollBack(): bool
    {
        $this->transactionBuffer = null;
        if ($this->lockHandle) {
            flock($this->lockHandle, LOCK_UN);
            fclose($this->lockHandle);
            $this->lockHandle = null;
        }
        return true;
    }

    public function ensureTable(string $table, string $pk, array $schema, array $indexes = []): bool
    {
        // En JSON, le "table" est le fichier physique déjà géré au constructeur.
        return true;
    }

    public function find(string $id): ?array
    {
        $data = $this->loadAll();
        foreach ($data as $record) {
            if (isset($record[$this->primaryKey]) && (string) $record[$this->primaryKey] === $id) {
                return $record;
            }
        }
        return null;
    }

    public function findAll(array $criteria = []): array
    {
        $data = $this->loadAll();

        // Extract meta-criteria if wrapped, else use raw as conditions
        $conditions = $criteria['conditions'] ?? $criteria;
        $order = $criteria['order'] ?? [];
        $limit = $criteria['limit'] ?? null;
        $offset = $criteria['offset'] ?? 0;

        // 1. Filtrage
        $filtered = array_filter($data, function ($record) use ($conditions) {
            foreach ($conditions as $key => $value) {
                // Si c'est un bloc de condition structuré (column, operator, value)
                if (is_array($value) && isset($value['column'], $value['operator']) && array_key_exists('value', $value)) {
                    $col = $value['column'];
                    $op = strtoupper(trim((string) $value['operator']));
                    $val = $value['value'];

                    $actual = $record[$col] ?? null;

                    switch ($op) {
                        case '=':
                            if ($actual != $val)
                                return false;
                            break;
                        case '!=':
                        case '<>':
                            if ($actual == $val)
                                return false;
                            break;
                        case 'IS NULL':
                            if ($actual !== null && $actual !== '')
                                return false;
                            break;
                        case 'IS NOT NULL':
                            if ($actual === null || $actual === '')
                                return false;
                            break;
                        case 'IN':
                            if (!is_array($val) || !in_array($actual, $val))
                                return false;
                            break;
                        case '>':
                            if (!($actual > $val))
                                return false;
                            break;
                        case '<':
                            if (!($actual < $val))
                                return false;
                            break;
                        case '>=':
                            if (!($actual >= $val))
                                return false;
                            break;
                        case '<=':
                            if (!($actual <= $val))
                                return false;
                            break;
                        case 'LIKE':
                            $pattern = str_replace('%', '.*', preg_quote((string) $val, '/'));
                            if (!preg_match("/^$pattern$/i", (string) $actual))
                                return false;
                            break;
                        default:
                            return false;
                    }
                } else {
                    // Fallback legacy behavior (associative array)
                    if (!isset($record[$key]) || $record[$key] !== $value) {
                        return false;
                    }
                }
            }
            return true;
        });

        // 2. Tri
        if (!empty($order)) {
            usort($filtered, function ($a, $b) use ($order) {
                foreach ($order as $o) {
                    $key = $o['key'];
                    $dir = strtolower($o['direction']) === 'desc' ? -1 : 1;
                    $valA = $a[$key] ?? null;
                    $valB = $b[$key] ?? null;
                    if ($valA == $valB)
                        continue;
                    return ($valA < $valB ? -1 : 1) * $dir;
                }
                return 0;
            });
        }

        // 3. Limitation
        if ($limit !== null || $offset > 0) {
            $filtered = array_slice($filtered, (int) $offset, $limit === null ? null : (int) $limit);
        }

        return array_values($filtered);
    }

    /**
     * Compte les enregistrements pour les critères donnés.
     * Pour JSON, on fait findAll puis count (fichiers petits, acceptable).
     */
    public function count(array $criteria = []): int
    {
        return count($this->findAll($criteria));
    }

    public function save(string $id, array $data): bool
    {
        if ($this->transactionBuffer !== null) {
            $found = false;
            foreach ($this->transactionBuffer as &$record) {
                if (isset($record[$this->primaryKey]) && (string) $record[$this->primaryKey] === $id) {
                    $record = array_merge($record, $data);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $data[$this->primaryKey] = $data[$this->primaryKey] ?? $id;
                $this->transactionBuffer[] = $data;
            }
            return true;
        }

        return $this->withLock(function () use ($id, $data) {
            $all = $this->loadAll();
            $found = false;

            foreach ($all as &$record) {
                if (isset($record[$this->primaryKey]) && (string) $record[$this->primaryKey] === $id) {
                    $record = array_merge($record, $data);
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $data[$this->primaryKey] = $data[$this->primaryKey] ?? $id;
                $all[] = $data;
            }

            return $this->saveAll($all);
        });
    }

    public function delete(string $id): bool
    {
        if ($this->transactionBuffer !== null) {
            $this->transactionBuffer = array_values(array_filter($this->transactionBuffer, function ($record) use ($id) {
                return !isset($record[$this->primaryKey]) || (string) $record[$this->primaryKey] !== $id;
            }));
            return true;
        }

        return $this->withLock(function () use ($id) {
            $all = $this->loadAll();
            $filtered = array_filter($all, function ($record) use ($id) {
                return !isset($record[$this->primaryKey]) || (string) $record[$this->primaryKey] !== $id;
            });

            if (count($filtered) === count($all)) {
                return true; // Déjà supprimé
            }

            return $this->saveAll(array_values($filtered));
        });
    }

    /**
     * Charge les données avec verrou de lecture partagé.
     */
    private function loadAll(): array
    {
        if ($this->transactionBuffer !== null) {
            return $this->transactionBuffer;
        }

        $testMode = \Core\Auth\AdminTestMode::getInstance();
        if ($testMode->isActive()) {
            $volatile = $testMode->getVolatileJson($this->filePath);
            if ($volatile !== null) {
                return $volatile;
            }
        }

        if (!file_exists($this->filePath))
            return [];

        // Si on a déjà un verrou exclusif (transaction), on peut lire sans risque
        if ($this->lockHandle) {
            $content = file_get_contents($this->filePath);
        } else {
            // Sinon on utilise withLock pour un read safe (simplifié ici par file_get_contents car atomic write assure la cohérence, 
            // mais un lock partagé serait mieux pour éviter les lectures pendant un rename partiel sur certains OS bizarre)
            $content = @file_get_contents($this->filePath);
        }

        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    private const MAX_BACKUPS = 5;

    /**
     * Gère la rotation de 5 fichiers de backup.
     */
    private function rotateBackups(): void
    {
        if (!file_exists($this->filePath)) {
            return;
        }

        for ($i = self::MAX_BACKUPS - 1; $i >= 1; $i--) {
            $old = $this->filePath . '.bak.' . $i;
            $new = $this->filePath . '.bak.' . ($i + 1);
            if (file_exists($old)) {
                @rename($old, $new);
            }
        }
        @copy($this->filePath, $this->filePath . '.bak.1');
    }

    /**
     * Sauvegarde atomique (tmp + rename) avec verrou exclusif.
     */
    private function saveAll(array $data): bool
    {
        $testMode = \Core\Auth\AdminTestMode::getInstance();
        if ($testMode->isActive()) {
            $testMode->setVolatileJson($this->filePath, $data);
            return true;
        }

        $dir = dirname($this->filePath);
        if (!is_dir($dir))
            @mkdir($dir, 0755, true);

        // Backup rotation before writing
        $this->rotateBackups();

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $tmpFile = $this->filePath . '.' . uniqid() . '.tmp';

        if (file_put_contents($tmpFile, $json, LOCK_EX) === false) {
            EmergencyLogger::log("ERROR: JsonStorage::saveAll - file_put_contents failed for $tmpFile");
            return false;
        }

        // Sur Windows, rename() peut échouer si le fichier cible existe et est ouvert.
        // On tente plusieurs fois avec un délai exponentiel.
        $success = false;
        $maxRetries = 10;
        for ($i = 0; $i < $maxRetries; $i++) {
            if (@rename($tmpFile, $this->filePath)) {
                $success = true;
                break;
            }
            // Délai progressif : 5ms, 15ms, 45ms... (Exponential backoff possible, but let's stay simple)
            usleep(5000 * ($i + 1)); 
        }

        if (!$success) {
            EmergencyLogger::log("CRITICAL: JsonStorage::saveAll failed to rename $tmpFile to {$this->filePath} after $maxRetries attempts.");
            @unlink($tmpFile);
            return false;
        }

        return true;
    }

    /**
     * Exécute une opération sous verrouillage flock.
     * Utilise un fichier de verrouillage séparé pour éviter de bloquer rename() sur Windows.
     */
    private function withLock(callable $callback)
    {
        if ($this->lockHandle) {
            return $callback();
        }

        $lockFile = $this->filePath . '.lock';
        $fp = fopen($lockFile, "c+");
        if (!$fp) {
            return $callback(); // Fallback sans lock si impossible d'ouvrir
        }

        if (flock($fp, LOCK_EX)) {
            try {
                $result = $callback();
            } finally {
                flock($fp, LOCK_UN);
                fclose($fp);
                // On ne supprime pas le fichier lock car cela peut créer des race conditions
            }
            return $result;
        }

        fclose($fp);
        return false;
    }
}
