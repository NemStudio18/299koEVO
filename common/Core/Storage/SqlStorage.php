<?php

namespace Core\Storage;

use Core\Interfaces\StorageInterface;
use Core\Interfaces\SqlDriverInterface;
use Core\Interfaces\SqlDialectInterface;

/**
 * Implémentation SQL du stockage.
 * Délègue l'exécution au SqlDriver et la syntaxe au SqlDialect.
 */
class SqlStorage implements StorageInterface
{
    private SqlDriverInterface $driver;
    private SqlDialectInterface $dialect;
    private string $table;
    private string $primaryKey;
    private array $mapping;

    public function __construct(SqlDriverInterface $driver, string $table, string $primaryKey = 'id', array $mapping = [])
    {
        $this->driver = $driver;
        $this->table = $table;
        $this->primaryKey = $primaryKey;
        $this->mapping = $mapping;

        // Résolution du dialecte via le driver
        $this->dialect = DialectResolver::resolve($driver->getDriverName());
    }

    public function beginTransaction(): bool
    {
        return $this->driver->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->driver->commit();
    }

    public function rollBack(): bool
    {
        return $this->driver->rollBack();
    }

    public function ensureTable(string $table, string $pk, array $schema, array $indexes = []): bool
    {
        // 1. Vérifier si la table existe
        $sql = $this->dialect->tableExists($table);
        $exists = $this->driver->query($sql);
        
        if (empty($exists)) {
            // Créer la table
            if (method_exists($this->dialect, 'getCreateTableSql')) {
                $sql = $this->dialect->getCreateTableSql($table, $pk, $schema, $indexes);
                return $this->driver->execute($sql);
            }
            return false;
        }

        // 2. Vérifier les colonnes manquantes (migration)
        $tableColumns = $this->getTableColumns();
        $missing = [];
        foreach ($schema as $col => $type) {
            $mappedCol = $this->map($col);
            if (!in_array($mappedCol, $tableColumns)) {
                $missing[$mappedCol] = $type;
            }
        }

        if (!empty($missing) && method_exists($this->dialect, 'getAlterTableSql')) {
            foreach ($missing as $col => $type) {
                $sql = $this->dialect->getAlterTableSql($table, $col, $type);
                $this->driver->execute($sql);
            }
        }

        // 3. Vérifier les index
        if (!empty($indexes) && method_exists($this->dialect, 'getIndexSql')) {
            foreach ($indexes as $col) {
                try {
                    $sql = $this->dialect->getIndexSql($table, $this->map($col));
                    $this->driver->execute($sql);
                } catch (\Exception $e) {
                    // L'index existe déjà probablement, on ignore silencieusement ou on log
                    EmergencyLogger::log("SQL_INDEX_EXISTS: table=$table column=$col");
                }
            }
        }

        return true;
    }

    protected function map(string $key): string
    {
        return $this->mapping[$key] ?? $key;
    }

    public function find(string $id): ?array
    {
        $pk = $this->map($this->primaryKey);
        $sql = "SELECT * FROM {$this->table} WHERE {$pk} = :id LIMIT 1";
        $results = $this->driver->query($sql, [':id' => $id]);
        return $results[0] ?? null;
    }

    public function findAll(array $criteria = []): array
    {
        // Integration Mode Test Admin
        $testMode = \Core\Auth\AdminTestMode::getInstance();
        if ($testMode->isActive()) {
            $conditions = $criteria['conditions'] ?? $criteria;
            // Transmettre la structure originelle des conditions
            $volatile = $testMode->simulateSelect($this->table, $conditions);
            if ($volatile !== null) {
                EmergencyLogger::log("SQL_VOLATILE_RESULT: table={$this->table} count=" . count($volatile));
                return $volatile;
            }
        }

        // Extract meta-criteria if wrapped, else use raw as conditions
        $conditions = $criteria['conditions'] ?? $criteria;
        $order = $criteria['order'] ?? [];
        $limit = $criteria['limit'] ?? null;
        $offset = $criteria['offset'] ?? null;
        $selectCols = $criteria['select'] ?? ['*'];

        $cols = implode(', ', array_map(fn($c) => $c === '*' ? '*' : "`$c`", $selectCols));

        $where = [];
        $params = [];
        $idx = 0;

        foreach ($conditions as $k => $condition) {
            if (is_array($condition) && isset($condition['column'], $condition['operator'], $condition['value'])) {
                $col = $this->map($condition['column']);
                $op = $condition['operator'];
                $val = $condition['value'];

                // Detect IN operator with array value
                if (strtoupper($op) === 'IN' && is_array($val)) {
                    $inParams = [];
                    foreach ($val as $kVal => $vVal) {
                        $pName = ":p{$idx}_{$kVal}";
                        $inParams[] = $pName;
                        $params[$pName] = $vVal;
                    }
                    $where[] = "$col IN (" . implode(', ', $inParams) . ")";
                    $idx++;
                } else {
                    $paramName = ":p$idx";
                    $where[] = "$col $op $paramName";
                    $params[$paramName] = $val;
                    $idx++;
                }
            } else {
                // Fallback legacy behavior (associative array)
                if (is_numeric($k) && !is_array($condition))
                    continue;
                $key = is_numeric($k) ? $k : $this->map($k);
                $val = $condition;

                if (is_array($val))
                    continue; // Skip unexpected nesting

                $where[] = "$key = :k$idx";
                $params[":k$idx"] = $val;
                $idx++;
            }
        }

        $whereClause = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";

        $orderBy = "";
        if (!empty($order)) {
            $parts = array_map(fn($o) => "{$o['key']} {$o['direction']}", $order);
            $orderBy = " ORDER BY " . implode(", ", $parts);
        }

        $limitClause = "";
        if ($limit !== null) {
            $limitClause = $this->dialect->limit((int) $limit, (int) ($offset ?? 0));
        }

        $sql = "SELECT {$cols} FROM {$this->table}{$whereClause}{$orderBy}{$limitClause}";

        $results = $this->driver->query($sql, $params);
        EmergencyLogger::log("SQL_RESULT: table={$this->table} count=" . count($results));

        return $results;
    }

    public function save(string $id, array $data): bool
    {
        // Integration Mode Test Admin
        $testMode = \Core\Auth\AdminTestMode::getInstance();
        if ($testMode->isActive()) {
            $testMode->addVolatileRow($this->table, $data);
            $testMode->recordSqlWrite("Simulated SAVE for {$this->table}", $data);
            return true;
        }

        $exists = $this->find($id);
        $tableColumns = $this->getTableColumns();

        if ($exists) {
            $sets = [];
            $params = [':id' => $id];
            $pk = $this->map($this->primaryKey);

            foreach ($data as $key => $value) {
                if ($key === $this->primaryKey)
                    continue;

                $col = $this->map($key);

                // On n'enregistre que si la colonne existe en DB
                if (in_array($col, $tableColumns)) {
                    $p = "p_" . $key;
                    $sets[] = "`$col` = :$p";
                    $params[":$p"] = $value;
                }
            }

            if (empty($sets)) {
                return true;
            }

            $sql = "UPDATE `{$this->table}` SET " . implode(", ", $sets) . " WHERE `{$pk}` = :where_id";
            $params[':where_id'] = $id;
            unset($params[':id']);

            $success = $this->driver->execute($sql, $params);
            return $success;
        } else {
            $data[$this->primaryKey] = $data[$this->primaryKey] ?? $id;
            $fields = [];
            $placeholders = [];
            $params = [];

            foreach ($data as $k => $v) {
                $col = ($k === $this->primaryKey) ? $this->map($this->primaryKey) : $this->map($k);

                // Skip PK if empty to allow AUTO_INCREMENT
                if ($k === $this->primaryKey && ($v === '' || $v === null)) {
                    continue;
                }

                if (in_array($col, $tableColumns)) {
                    $fields[] = $col;
                    $placeholders[] = ":$k";
                    $params[":$k"] = $v;
                }
            }

            if (empty($fields))
                return true;

            $sql = "INSERT INTO {$this->table} (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")";

            EmergencyLogger::log("SQL_SAVE_INSERT: table={$this->table} id=$id");
            $success = $this->driver->execute($sql, $params);
            return $success;
        }
    }

    private ?array $columnsCache = null;
    private function getTableColumns(): array
    {
        if ($this->columnsCache !== null)
            return $this->columnsCache;

        try {
            $sql = $this->dialect->getColumnsSql($this->table);
            $results = $this->driver->query($sql);
            
            // Normalize column names based on driver
            $this->columnsCache = array_map(function($c) {
                // MySQL: Field, SQLite: name, PgSQL: Field (via alias)
                return $c['Field'] ?? $c['field'] ?? $c['name'] ?? '';
            }, $results);
            
            $this->columnsCache = array_filter($this->columnsCache);
        } catch (\Exception $e) {
            EmergencyLogger::log("SQL_COLUMNS_ERROR: table={$this->table} error=" . $e->getMessage());
            $this->columnsCache = [];
        }
        return $this->columnsCache;
    }

    public function delete(string $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        return $this->driver->execute($sql, [':id' => $id]);
    }

    /**
     * Compte les enregistrements sans les charger en mémoire (SELECT COUNT(*)).
     * Utilisé par JsonActiveRecord::count() pour les models SQL.
     */
    public function count(array $criteria = []): int
    {
        // Test mode : simuler via les lignes volatiles
        $testMode = \Core\Auth\AdminTestMode::getInstance();
        if ($testMode->isActive()) {
            $conditions = $criteria['conditions'] ?? $criteria;
            $volatile = $testMode->simulateSelect($this->table, $conditions);
            if ($volatile !== null) {
                return count($volatile);
            }
        }

        $conditions = $criteria['conditions'] ?? $criteria;
        $where = [];
        $params = [];
        $idx = 0;

        foreach ($conditions as $k => $condition) {
            if (is_array($condition) && isset($condition['column'], $condition['operator'], $condition['value'])) {
                $col = $this->map($condition['column']);
                $op  = $condition['operator'];
                $val = $condition['value'];
                if (strtoupper($op) === 'IN' && is_array($val)) {
                    $inParams = [];
                    foreach ($val as $kVal => $vVal) {
                        $pName = ":p{$idx}_{$kVal}";
                        $inParams[] = $pName;
                        $params[$pName] = $vVal;
                    }
                    $where[] = "$col IN (" . implode(', ', $inParams) . ")";
                    $idx++;
                } else {
                    $paramName = ":p$idx";
                    $where[] = "$col $op $paramName";
                    $params[$paramName] = $val;
                    $idx++;
                }
            }
        }

        $whereClause = !empty($where) ? " WHERE " . implode(" AND ", $where) : "";
        $sql = "SELECT COUNT(*) as cnt FROM {$this->table}{$whereClause}";
        $results = $this->driver->query($sql, $params);
        return (int)($results[0]['cnt'] ?? 0);
    }
}

