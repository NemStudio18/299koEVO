<?php

namespace Core\Storage;

/**
 * @license MIT License
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 *
 * @version 1.0.0
 * @link https://github.com/MaxenceCauderlier/JsonActiveRecord
 */

/**
 * JsonActiveRecord is a class to deal with JSON files like Active Record
 */

abstract class JsonActiveRecord
{

    protected static string $filePath;
    protected static string $primaryKey = 'id';
    protected $queryConditions = [];
    protected $queryOrderBy = [];
    protected $queryLimit = null;
    protected $queryOffset = null;
    protected $queryGroups = [];
    protected $currentGroup = null;
    protected $withRelations = [];
    public array $attributes = [];

    protected array $trueAttributes = [];

    /**
     * Construct a new instance of the given class, optionally with attributes
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
        foreach ($this->trueAttributes as $attribute) {
            if (isset($this->attributes[$attribute])) {
                $this->{$attribute} = $this->attributes[$attribute];
            }
        }
        if (!file_exists(static::$filePath)) {
            file_put_contents(static::$filePath, json_encode([]));
        }
    }

    /**
     * Set the file path for the JSON file
     *
     * @param string $path
     *
     * @return void
     */
    public static function setFilePath(string $path): void
    {
        static::$filePath = $path;
    }

    /**
     * Return the primary key field name
     *
     * @return string
     */
    public static function getPrimaryKey(): string
    {
        return static::$primaryKey;
    }

    /**
     * Get a new instance of the current class for query building
     *
     * @return self
     */
    public static function queryBuilder(): self
    {
        return new static();
    }

    /**
     * Filter the results with an "AND" condition
     *
     * @param array $conditions associative array of "column" => "value"
     * @return self
     */
    public function where(string $key, string $operator, $value): self
    {
        $condition = ['key' => $key, 'operator' => $operator, 'value' => $value, 'logic' => 'and'];
        $this->addCondition($condition);
        return $this;
    }

    /**
     * Filter the results with an "OR" condition
     *
     * @param array $conditions associative array of "column" => "value"
     * @return self
     */
    public function orWhere(string $key, string $operator, $value): self
    {
        $condition = ['key' => $key, 'operator' => $operator, 'value' => $value, 'logic' => 'or'];
        $this->addCondition($condition);
        return $this;
    }

    /**
     * Start a query group. Any conditions added between this and {@see endGroup}
     * will be grouped together with an "AND" or "OR" operator, depending on the
     * last condition added.
     *
     * @return self
     */
    public function startGroup(string $logic = 'and'): self
    {
        $group = ['type' => 'group', 'logic' => $logic, 'conditions' => []];
        if ($this->currentGroup) {
            $this->currentGroup['conditions'][] = &$group;
        } else {
            $this->queryConditions[] = &$group;
        }
        $this->currentGroup = &$group;
        return $this;
    }

    /**
     * End the current query group and add it as a condition.
     *
     * @throws \RuntimeException if there is no active group to end.
     * @return self
     */

    public function endGroup(): self
    {
        if (!$this->currentGroup) {
            throw new \RuntimeException("No active group to end.");
        }

        // Close the current group
        $parentGroup = null;
        foreach ($this->queryConditions as &$condition) {
            if ($condition === $this->currentGroup) {
                break;
            }
            if (isset($condition['conditions']) && in_array($this->currentGroup, $condition['conditions'], true)) {
                $parentGroup = &$condition;
                break;
            }
        }
        $this->currentGroup = &$parentGroup;
        return $this;
    }

    /**
     * Set the order by clause for the query.
     *
     * Adds a sorting criterion to the query, specifying the field to sort by
     * and the direction of sorting.
     *
     * @param string $key The field name to sort by.
     * @param string $direction The direction of sorting, either 'asc' or 'desc'.
     *                          Defaults to 'asc'.
     * @return self
     * @throws \InvalidArgumentException If the sort direction is invalid.
     */
    public function orderBy(string $key, string $direction = 'asc'): self
    {
        $direction = strtolower($direction);
        if (!in_array($direction, ['asc', 'desc'])) {
            throw new \InvalidArgumentException('Invalid sort direction. Use "asc" or "desc".');
        }

        $this->queryOrderBy[] = ['key' => $key, 'direction' => $direction];
        return $this;
    }

    /**
     * Limit the number of results returned.
     *
     * @param int $limit The number of results to return.
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->queryLimit = $limit;
        return $this;
    }

    /**
     * Skip the first $offset results.
     *
     * @param int $offset The number of results to skip.
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->queryOffset = $offset;
        return $this;
    }

    /**
     * Specify the relations to be loaded with the model.
     *
     * @param array $relations Array of relation names to be included.
     * @return self
     */

    public function with(array $relations): self
    {
        $this->withRelations = $relations;
        return $this;
    }

    /**
     * Retrieve records from the JSON file based on the specified query conditions.
     *
     * This method filters the data according to the conditions set in the query,
     * applies any defined offset and limit, and then returns the resulting objects
     * as instances of the current class. If relations are specified, they are loaded
     * into the objects.
     *
     * @return array An array of objects that match the query conditions.
     */

    public function get(): array
    {
        if (!file_exists(static::$filePath)) {
            $data = [];
        } else {
            $data = json_decode(file_get_contents(static::$filePath), true);
        }

        $data = array_filter($data, function ($attributes): bool {
            return $this->evaluateConditions($this->queryConditions, $attributes);
        });

        $data = array_values($data); // Reset keys after filtering

        if (!is_null($this->queryOffset)) {
            $data = array_slice($data, $this->queryOffset);
        }

        if (!is_null($this->queryLimit)) {
            $data = array_slice($data, 0, $this->queryLimit);
        }

        if (!empty($this->queryOrderBy)) {
            usort($data, function ($a, $b) {
                foreach ($this->queryOrderBy as $order) {
                    $key = $order['key'];
                    $direction = $order['direction'];
                    if (!isset($a[$key]) || !isset($b[$key])) {
                        continue;
                    }
        
                    if ($a[$key] == $b[$key]) {
                        continue;
                    }
        
                    $comparison = $a[$key] <=> $b[$key];
                    return $direction === 'asc' ? $comparison : -$comparison;
                }
                return 0;
            });
        }

        $objects = array_map(function ($attributes) {
            return new static($attributes);
        }, $data);

        if (!empty($this->withRelations)) {
            $this->loadRelations($objects);
        }
        if (\Core\Core::getInstance()->getConfigVal('debug')) {
            \Core\Core::getInstance()->addQueryCounter();
        }

        return $objects;
    }


    /**
     * Add a condition to the query conditions stack.
     *
     * If the current group is null, the condition is added to the root of the
     * query conditions stack. Otherwise, it is added to the current group.
     *
     * @param array $condition A condition to add to the query conditions stack.
     */
    protected function addCondition(array $condition): void
    {
        if ($this->currentGroup) {
            $this->currentGroup['conditions'][] = $condition;
        } else {
            $this->queryConditions[] = $condition;
        }
    }

    /**
     * Evaluate the given conditions against the given attributes.
     *
     * This method takes an array of conditions and an array of attributes and
     * evaluates the conditions against the attributes. The conditions are
     * evaluated in the order they are given, and the result of each condition
     * is combined with the previous result using the logical operator specified
     * in the condition.
     *
     * @param array $conditions The conditions to evaluate.
     * @param array $attributes The attributes to evaluate against.
     *
     * @return bool The result of the conditions evaluation.
     */
    protected function evaluateConditions(array $conditions, array $attributes): bool
    {
        $result = null;

        foreach ($conditions as $condition) {
            if (isset($condition['type']) && $condition['type'] === 'group') {
                $match = $this->evaluateConditions($condition['conditions'], $attributes);
            } else {
                $key = $condition['key'];
                $operator = $condition['operator'];
                $value = $condition['value'];

                $match = false;
                if (isset($attributes[$key])) {
                    switch ($operator) {
                        case '=':
                            $match = $attributes[$key] === $value;
                            break;
                        case '!=':
                            $match = $attributes[$key] !== $value;
                            break;
                        case '>':
                            $match = $attributes[$key] > $value;
                            break;
                        case '<':
                            $match = $attributes[$key] < $value;
                            break;
                        case '>=':
                            $match = $attributes[$key] >= $value;
                            break;
                        case '<=':
                            $match = $attributes[$key] <= $value;
                            break;
                        case 'like':
                            $match = strpos($attributes[$key], $value) !== false;
                            break;
                        case '!like':
                            $match = strpos($attributes[$key], $value) === false;
                            break;
                        case 'in':
                            $match = in_array($attributes[$key], $value);
                            break;
                        case '!in':
                            $match = !in_array($attributes[$key], $value);
                            break;
                        case '#':
                            $match = preg_match($value, $attributes[$key]);
                            break;
                        case '!#':
                            $match = !preg_match($value, $attributes[$key]);
                            break;

                        default:
                            throw new \InvalidArgumentException("Invalid operator: {$operator}");
                    }
                }
            }

            if ($condition['logic'] === 'and') {
                $result = $result === null ? $match : $result && $match;
            } elseif ($condition['logic'] === 'or') {
                $result = $result === null ? $match : $result || $match;
            }
        }

        return $result ?? true;
    }

    /**
     * Load the specified relations into the objects.
     *
     * @param array &$objects The objects to load the relations into.
     *
     * This method loads the specified relations into the objects by calling the
     * corresponding methods on each object and storing the results in the
     * objects' attributes.
     */
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

    /**
     * Save the current object's attributes to the JSON file.
     *
     * This method filters the object's attributes to exclude any keys starting
     * with an underscore. It then checks if an existing record with the same
     * primary key exists in the JSON file. If it does, the record is updated.
     * Otherwise, a new record is created with a generated primary key if necessary.
     *
     * @return bool True on success, indicating the record was saved or updated.
     */

    public function save(): bool
    {
        // Update true attributes
        foreach ($this->trueAttributes as $attribute) {
            $this->attributes[$attribute] = $this->{$attribute};
        }
        if (!file_exists(static::$filePath)) {
            $data = [];
        } else {
            $data = json_decode(file_get_contents(static::$filePath), true);
        }

        // Filter attributes to exclude keys starting with _
        $filteredAttributes = array_filter(
            $this->attributes,
            fn($key): bool => strpos($key, '_') !== 0,
            ARRAY_FILTER_USE_KEY
        );

        // Update existing record if it exists
        foreach ($data as &$record) {
            if (isset($record[static::$primaryKey]) && $record[static::$primaryKey] === ($filteredAttributes[static::$primaryKey] ?? null)) {
                $record = $filteredAttributes;
                file_put_contents(static::$filePath, json_encode($data));
                return true;
            }
        }

        // Otherwise, add a new record
        if (!isset($filteredAttributes[static::$primaryKey])) {
            $filteredAttributes[static::$primaryKey] = $this->generateId($data);
            // Save PK in current object
            $this->attributes[static::$primaryKey] = $filteredAttributes[static::$primaryKey];
        }
        $data[] = $filteredAttributes;
        file_put_contents(static::$filePath, json_encode($data));

        return true;
    }

    /**
     * Delete the current object from the JSON file.
     *
     * This method removes the record with the same primary key as the current
     * object from the JSON file.
     *
     * @return bool True on success, indicating the record was deleted.
     */
    public function delete(): bool
    {
        if (!file_exists(static::$filePath)) {
            $data = [];
        } else {
            $data = json_decode(file_get_contents(static::$filePath), true);
        }

        $data = array_filter($data, function ($record): bool {
            return $record[static::$primaryKey] !== ($this->attributes[static::$primaryKey] ?? null);
        });

        file_put_contents(static::$filePath, json_encode(array_values($data)));

        return true;
    }

    /**
     * Generate a unique ID for a new record.
     *
     * This method takes the given array of records and finds the highest
     * existing Primary Key. If no records exist, it reeurns 1. Otherwise, it
     * adds one to the highest ID and returns the result.
     *
     * @param array $data The array of existing records.
     *
     * @return int The new ID.
     */
    protected function generateId(array $data): int
    {
        $ids = array_column($data, static::$primaryKey);
        return $ids ? (int) (max($ids)) + 1 : 1;
    }

    /**
     * Get all records from the JSON file.
     *
     * This method returns an array of objects, each representing a record in
     * the JSON file. The objects will have the same properties as the
     * attributes of the class.
     *
     * @return array An array of objects, each representing a record in the
     * JSON file.
     */
    public static function all(): array
    {
        if (!file_exists(static::$filePath)) {
            return [];
        }
        $data = json_decode(file_get_contents(static::$filePath), true);
        if ($data === null) {
            return [];
        }
        return array_map(function ($attributes): self {
            return new static($attributes);
        }, $data);
    }

    /**
     * Find a record in the JSON file by key/value pair.
     *
     * This method takes a key and a value and searches the JSON file for a
     * record with that key/value pair. If a record is found, an object is
     * returned with the same properties as the attributes of the class. If no
     * record is found, null is returned.
     *
     * @param string|int $key The key to search for.
     * @param mixed $value The value to search for.
     *
     * @return self|null An object representing the record, or null if no
     * record is found.
     */
    public static function find($key, $value): ?self
    {
        if (!file_exists(static::$filePath)) {
            return null;
        }
        $data = json_decode(file_get_contents(static::$filePath), true);

        foreach ($data as $attributes) {
            if (isset($attributes[$key]) && $attributes[$key] === $value) {
                return new static($attributes);
            }
        }

        return null;
    }

    /**
     * Find a record in the JSON file by Primary Key.
     *
     * This method is a shortcut to {@see find()} with the primary key as the
     * key.
     *
     * @param mixed $key The value of the primary key to search for.
     *
     * @return self|null An object representing the record, or null if no
     * record is found.
     */
    public static function findPK($key): ?self
    {
        return static::find(static::$primaryKey, $key);
    }

    /**
     * Retrieve the first record in the JSON file.
     *
     * This method returns the first record in the JSON file, or null if no
     * records exist.
     *
     * @return self|null An object representing the first record, or null if
     * no records exist.
     */
    public static function first(): ?self
    {
        $obj = new static();
        return $obj->limit(1)->get()[0] ?? null;
    }

    /**
     * Retrieve the last record in the JSON file.
     *
     * This method returns the last record in the JSON file, or null if no
     * records exist.
     *
     * @return self|null An object representing the last record, or null if
     * no records exist.
     */
    public static function last(): ?self
    {
        $results = (new static())->get();
        return !empty($results) ? $results[count($results) - 1] : null;
    }

    /**
     * Retrieve the related record from the given class.
     *
     * @param string $relatedClass The class name to retrieve records from.
     * @param string $foreignKey The foreign key to search for.
     * @param string $localKey The local key to search for. Defaults to the
     * primary key of the class if not provided.
     *
     * @return self The related record, or null if no record is found.
     */
    public function hasOne(string $relatedClass, string $foreignKey, $localKey = false): ?self
    {
        if ($localKey === false) {
            $localKey = static::$primaryKey;
        }
        /** @var self $relatedClass */
        $relatedClass::setFilePath($relatedClass::$filePath);
        return $relatedClass::queryBuilder()
            ->where($foreignKey, '=', $this->attributes[$localKey])
            ->get()[0] ?? null;
    }

    /**
     * Retrieve the related records from the given class.
     *
     * @param string $relatedClass The class name to retrieve records from.
     * @param string $foreignKey The foreign key to search for.
     * @param string $localKey The local key to search for. Defaults to the
     * primary key of the class if not provided.
     *
     * @return array[self] An array of objects representing the related records.
     */
    public function hasMany(string $relatedClass, string $foreignKey, $localKey = false): ?array
    {
        if ($localKey === false) {
            $localKey = static::$primaryKey;
        }
        /** @var self $relatedClass */
        $relatedClass::setFilePath($relatedClass::$filePath);
        return $relatedClass::queryBuilder()
            ->where($foreignKey, '=', $this->attributes[$localKey])
            ->get();
    }

    /**
     * Retrieve the related record from the given class.
     *
     * @param string $relatedClass The class name to retrieve records from.
     * @param string $foreignKey The foreign key to search for.
     * @param string $localKey The local key to search for. Defaults to the
     * primary key of the class if not provided.
     *
     * @return self The related record, or null if no record is found.
     */
    public function belongsTo(string $relatedClass, string $foreignKey, $localKey = false): ?self
    {
        if ($localKey === false) {
            $localKey = static::$primaryKey;
        }
        /** @var self $relatedClass */
        $relatedClass::setFilePath($relatedClass::$filePath);
        return $relatedClass::find($localKey, $this->attributes[$foreignKey]);
    }

    /**
     * Dynamically set a value for an attribute.
     *
     * This magic method intercepts any attempts to set a value for an
     * attribute that is not explicitly defined in the class, and stores
     * the value in the attributes array.
     *
     * @param string $name The name of the attribute to set.
     * @param mixed $value The value to set the attribute to.
     *
     * @return void
     */
    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Dynamically retrieve a value for an attribute.
     *
     * This magic method intercepts any attempts to retrieve a value for an
     * attribute that is not explicitly defined in the class, and returns the
     * value from the attributes array. If there is a getter method for the
     * attribute (i.e. a method with the name "get" followed by the attribute
     * name), that method is called and its return value is returned.
     *
     * @param string $name The name of the attribute to retrieve.
     *
     * @return mixed The value of the attribute, or null if no value is set.
     */
    public function __get($name)
    {
        if (method_exists($this, "get$name")) {
            return $this->{"get$name"}();
        }
        return $this->attributes[$name] ?? null;
    }

    /**
     * Dynamically check if a value is set for an attribute.
     *
     * This magic method intercepts any attempts to check if an attribute is
     * set using the "isset" language construct, and checks if the value is
     * set in the attributes array.
     *
     * @param string $name The name of the attribute to check.
     *
     * @return bool True if the attribute is set, false otherwise.
     */
    public function __isset($name)
    {
        return isset($this->attributes[$name]);
    }
}