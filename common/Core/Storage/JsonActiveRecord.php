<?php

namespace Core\Storage;

use Core\Interfaces\StorageInterface;

/**
 * JsonActiveRecord is now a storage-agnostic Base Class (despite its name).
 * It delegates to specific StorageInterface implementations (Json or SQL).
 */
abstract class JsonActiveRecord implements \ArrayAccess
{
    /** Drivers constants */
    const DRIVER_JSON = 'json';
    const DRIVER_SQL = 'sql';

    protected static string $filePath;
    protected static string $tableName;
    protected static string $primaryKey = 'id';
    protected static string $storageDriver = self::DRIVER_JSON;
    protected static string $connectionName = 'default';
    protected static array $attributeMapping = [];
    protected static array $casts = [];
    protected static array $indexes = [];
    protected static bool $isBooted = false;
    private static array $bootedModels = [];

    protected $queryConditions = [];
    protected $queryOrderBy = [];
    protected $queryLimit = null;
    protected $queryOffset = null;
    protected $queryGroups = [];
    protected $currentGroup = null;
    protected $withRelations = [];
    protected $querySelect = ['*'];

    public array $attributes = [];
    protected array $trueAttributes = [];

    /**
     * Construct a new instance, optionally with attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $this->normalizeAttributes($attributes);
        $this->applyCastsOnLoad();

        foreach ($this->trueAttributes as $attribute) {
            if (isset($this->attributes[$attribute])) {
                $this->{$attribute} = $this->attributes[$attribute];
            }
        }
    }

    /**
     * Map storage-specific column names back to model attribute names.
     */
    protected function normalizeAttributes(array $attributes): array
    {
        if (empty(static::$attributeMapping)) {
            return $attributes;
        }

        $normalized = [];
        $reverseMapping = array_flip(static::$attributeMapping);

        foreach ($attributes as $key => $value) {
            $normalizedKey = $reverseMapping[$key] ?? $key;
            $normalized[$normalizedKey] = $value;
        }

        return $normalized;
    }

    protected function applyCastsOnLoad(): void
    {
        foreach (static::$casts as $key => $type) {
            if (isset($this->attributes[$key]) && is_string($this->attributes[$key])) {
                if ($type === 'array' || $type === 'json') {
                    $this->attributes[$key] = json_decode($this->attributes[$key], true) ?: [];
                }
            }
        }
    }

    /**
     * Set the file path for the JSON file (Legacy)
     */
    public static function setFilePath(string $path): void
    {
        static::$filePath = $path;
    }

    /**
     * Return the primary key field name
     */
    public static function getPrimaryKey(): string
    {
        return static::$primaryKey;
    }

    /**
     * Return the table name
     */
    public function getTableName(): string
    {
        $className = basename(str_replace('\\', '/', get_called_class()));
        return static::$tableName ?? strtolower($className);
    }

    /**
     * Get a new instance for query building
     */
    public static function queryBuilder(): self
    {
        return new static();
    }

    /**
     * Alias for queryBuilder()
     */
    public static function query(): self
    {
        return static::queryBuilder();
    }

    /**
     * Convert attributes to array
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Fill attributes from array
     */
    public function fill(array $data): self
    {
        foreach ($data as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    /**
     * Helper for grouped conditions (minimal implementation)
     */
    public function nest(callable $callback): self
    {
        $callback($this);
        return $this;
    }

    /**
     * Set columns to select
     */
    public function select(array $columns): self
    {
        $this->querySelect = $columns;
        return $this;
    }
    
    public static function __callStatic($name, $arguments)
    {
        if (in_array($name, ['first', 'last'])) {
            return (new static())->$name(...$arguments);
        }
        throw new \BadMethodCallException("Static method $name does not exist on " . get_called_class());
    }

    /**
     * Factory to get the delegated storage implementation.
     */
    protected static function getStorage(): StorageInterface
    {
        $db = \Core\Database::getInstance();
        $className = basename(str_replace('\\', '/', get_called_class()));

        // Priority: 1. Individual model config in env.json, 2. Class property static::$storageDriver, 3. Global storage.default
        $driver = $db->getConfig("$className")
            ?? static::$storageDriver
            ?? $db->getConfig("default");

        EmergencyLogger::log("STORAGE_HUB: Model=$className selected_driver=$driver");

        $storage = null;
        if ($driver !== self::DRIVER_JSON) {
            // Si le driver n'est pas "json", c'est soit "sql" (default), soit un nom de connexion spécifique (ex: "sqlite")
            $connectionName = ($driver === self::DRIVER_SQL) ? static::$connectionName : $driver;
            
            $primary = new SqlStorage(
                $db->setActiveConnection($connectionName),
                static::$tableName ?? strtolower($className),
                static::$primaryKey,
                static::$attributeMapping
            );

            // Fallback JSON automatique
            $fallback = new JsonStorage(static::$filePath, static::$primaryKey);

            $storage = new FallbackStorage($primary, $fallback);
        } else {
            $storage = new JsonStorage(static::$filePath, static::$primaryKey);
        }

        // Auto-Migration (Lazy boot per concrete model class & active driver)
        $modelClass = static::class;
        $bootKey = $modelClass . '|' . (string) $driver;
        if (empty(self::$bootedModels[$bootKey])) {
            static::$isBooted = true;
            $ensured = $storage->ensureTable(
                static::$tableName ?? strtolower($className),
                static::$primaryKey,
                static::getSchema(),
                static::$indexes
            );
            if ($ensured) {
                self::$bootedModels[$bootKey] = true;
            }
        }

        return $storage;
    }

    protected static function getSchema(): array
    {
        if (property_exists(static::class, 'schema') && !empty(static::$schema)) {
            return static::$schema;
        }
        $schema = [];
        foreach (static::$casts as $key => $type) {
            $schema[$key] = $type;
        }
        return $schema;
    }

    public static function beginTransaction(): bool
    {
        return static::getStorage()->beginTransaction();
    }

    public static function commit(): bool
    {
        return static::getStorage()->commit();
    }

    public static function rollBack(): bool
    {
        return static::getStorage()->rollBack();
    }

    /**
     * Filter results
     */
    public function where(string $key, $operator, $value = null): self
    {
        if ($value === null && func_num_args() < 3) {
            $value = $operator;
            $operator = '=';
        }
        $this->queryConditions[] = [
            'column' => $key,
            'operator' => $operator,
            'value' => $value
        ];
        return $this;
    }

    public function orWhere(string $key, $operator, $value = null): self
    {
        return $this->where($key, $operator, $value);
    }

    public function whereIn(string $key, array $values): self
    {
        $this->queryConditions[] = [
            'column' => $key,
            'operator' => 'IN',
            'value' => $values
        ];
        return $this;
    }

    /**
     * Set result limit and optional offset
     */
    public function limit(int $limit, ?int $offset = null): self
    {
        $this->queryLimit = $limit;
        if ($offset !== null) {
            $this->queryOffset = $offset;
        }
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->queryOffset = $offset;
        return $this;
    }

    public function orderBy(string $key, string $direction = 'asc'): self
    {
        $this->queryOrderBy[] = ['key' => $key, 'direction' => $direction];
        return $this;
    }

    /**
     * Alias for orderBy()
     */
    public function sortBy(string $key, string $direction = 'asc'): self
    {
        return $this->orderBy($key, $direction);
    }

    /**
     * Count records matching criteria — optimized, does NOT load all records into memory.
     */
    public function count(): int
    {
        $storage = static::getStorage();

        $criteria = [
            'conditions' => $this->queryConditions,
            'order'      => [],
            'limit'      => null,
            'offset'     => 0,
        ];

        // Use dedicated count method if available (SqlStorage)
        if (method_exists($storage, 'count')) {
            return $storage->count($criteria);
        }

        // Fallback: delegate to findAll (still memory-efficient for JSON since files are small)
        return count($storage->findAll($criteria));
    }

    public function with(array $relations): self
    {
        $this->withRelations = $relations;
        return $this;
    }

    /**
     * Execute and return objects.
     */
    public function get(): array
    {
        $storage = static::getStorage();

        // On construit le package complet pour le storage
        $criteria = [
            'conditions' => $this->queryConditions,
            'order' => $this->queryOrderBy,
            'limit' => $this->queryLimit,
            'offset' => $this->queryOffset,
            'select' => $this->querySelect
        ];

        $rawRecords = $storage->findAll($criteria);

        $objects = array_map(fn($attr) => new static($attr), $rawRecords);

        if (!empty($this->withRelations)) {
            $this->loadRelations($objects);
        }

        return $objects;
    }

    /**
     * Get first record of the query
     */
    public function first(): ?self
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Get last record of the query
     */
    public function last(): ?self
    {
        // Not efficient but works for now as a generic fallback
        $results = $this->get();
        return $results ? $results[count($results) - 1] : null;
    }

    // Alias pour compatibilité descendante (certains plugins utilisent all avec conditions)
    public static function all($criteria = []): array
    {
        $instance = new static();
        if (!empty($criteria)) {
            // Si c'est un tableau associatif legacy, on convertit
            foreach ($criteria as $key => $val) {
                if (is_numeric($key) && is_array($val)) {
                    $instance->queryConditions[] = $val;
                } else {
                    $instance->where($key, '=', $val);
                }
            }
        }
        return $instance->get();
    }

    /**
     * Save the record to chosen storage.
     */
    public function save(): bool
    {
        foreach ($this->trueAttributes as $attr) {
            $this->attributes[$attr] = $this->{$attr};
        }

        $id = (string) ($this->attributes[static::$primaryKey] ?? '');
        $storage = static::getStorage();
        
        $isSql = ($storage instanceof \Core\Storage\SqlStorage) || ($storage instanceof \Core\Storage\FallbackStorage);

        if (empty($id)) {
            // Only generate an id if we are NOT using SQL driver (or Fallback which implies SQL primary)
            // For SQL, we want to let the database handle AUTO_INCREMENT
            if (!$isSql) {
                if (property_exists(static::class, 'autoIncrementId') && static::$autoIncrementId) {
                    $existing = $storage->findAll([]);
                    $maxId = 0;
                    foreach ($existing as $row) {
                        $rowId = (int) ($row[static::$primaryKey] ?? 0);
                        if ($rowId > $maxId) {
                            $maxId = $rowId;
                        }
                    }
                    $id = (string) ($maxId + 1);
                } else {
                    $id = bin2hex(random_bytes(8));
                }
            }
        }

        // Apply casts for storage (encode arrays to JSON if using SQL)
        $storageData = $this->attributes;
        if ($isSql) {
            foreach (static::$casts as $key => $type) {
                if (isset($storageData[$key]) && is_array($storageData[$key])) {
                    $storageData[$key] = json_encode($storageData[$key], JSON_UNESCAPED_UNICODE);
                }
            }
        }

        $success = $storage->save($id, $storageData);

        if ($success) {
            if ($isSql && empty($id)) {
                $id = \Core\Database::getInstance()->lastInsertId();
            }
            $this->attributes[static::$primaryKey] = $id;
        }
        return $success;
    }

    public function delete(): bool
    {
        $id = (string) ($this->attributes[static::$primaryKey] ?? '');
        if (empty($id))
            return false;
        return static::getStorage()->delete($id);
    }

    public static function deletePK($id): bool
    {
        return static::getStorage()->delete((string)$id);
    }

    /** Legacy / Static helpers */
    public static function findPK($id): ?self
    {
        $data = static::getStorage()->find((string) $id);
        return $data ? new static($data) : null;
    }

    public static function find($key, $value): ?self
    {
        return (new static())->where($key, '=', $value)->get()[0] ?? null;
    }

    public function hasOne(string $relatedClass, string $foreignKey, $localKey = false): ?self
    {
        $localVal = $this->attributes[$localKey ?: static::$primaryKey] ?? null;
        return $relatedClass::find($foreignKey, $localVal);
    }

    public function hasMany(string $relatedClass, string $foreignKey, $localKey = false): array
    {
        $localVal = $this->attributes[$localKey ?: static::$primaryKey] ?? null;
        return $relatedClass::queryBuilder()->where($foreignKey, '=', $localVal)->get();
    }

    public function belongsTo(string $relatedClass, string $foreignKey, $localKey = false): ?self
    {
        $foreignVal = $this->attributes[$foreignKey] ?? null;
        return $relatedClass::findPK($foreignVal);
    }

    private array $gettingAttributes = [];

    // ... other magic methods __get, __set, __isset remain valid ...
    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }
    public function __get($name)
    {
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }
        $getter = "get$name";
        if (method_exists($this, $getter) && empty($this->gettingAttributes[$name])) {
            $this->gettingAttributes[$name] = true;
            try {
                return $this->{$getter}();
            } finally {
                unset($this->gettingAttributes[$name]);
            }
        }
        return null;
    }
    public function __isset($name)
    {
        return isset($this->attributes[$name]);
    }

    protected function loadRelations(array &$objects): void
    {
        foreach ($this->withRelations as $relation) {
            foreach ($objects as $object) {
                if (method_exists($object, $relation)) {
                    $object->attributes[$relation] = $object->{$relation}();
                }
            }
        }
    }
    /** ArrayAccess implementation */
    public function offsetExists($offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->__get($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->__set($offset, $value);
    }

    public function offsetUnset($offset): void
    {
        unset($this->attributes[$offset]);
    }
}