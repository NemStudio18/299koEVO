<?php

namespace Core\Storage\Dialects;

use Core\Interfaces\SqlDialectInterface;

class SqliteDialect implements SqlDialectInterface
{
    public function limit(int $limit, int $offset = 0): string
    {
        return " LIMIT " . $limit . ($offset > 0 ? " OFFSET " . $offset : "");
    }

    public function jsonExtract(string $field, string $path): string
    {
        return "JSON_EXTRACT($field, '$" . $path . "')";
    }

    public function fulltextMatch(array $columns, string $placeholder): string
    {
        $likes = array_map(fn($col) => "$col LIKE $placeholder", $columns);
        return "(" . implode(' OR ', $likes) . ")";
    }

    public function proximitySql(string $latColumn, string $lonColumn, string $latValue, string $lonValue): string
    {
        return "((abs($latColumn - ($latValue)) * 111.0) + (abs($lonColumn - ($lonValue)) * 75.0))";
    }

    public function conditional(string $condition, string $ifTrue, string $ifFalse): string
    {
        return "(CASE WHEN $condition THEN $ifTrue ELSE $ifFalse END)";
    }

    public function dateSub(string $field, int $interval, string $unit): string
    {
        return "datetime($field, '-$interval $unit')";
    }

    public function now(): string
    {
        return "datetime('now')";
    }

    public function tableExists(string $tableName): string
    {
        return "SELECT 1 FROM sqlite_master WHERE type='table' AND name='$tableName'";
    }

    public function getDateFormat(): string
    {
        return "Y-m-d H:i:s";
    }

    public function getColumnsSql(string $tableName): string
    {
        return "PRAGMA table_info($tableName)";
    }

    public function getName(): string
    {
        return "sqlite";
    }

    public function getCreateTableSql(string $table, string $pk, array $columns, array $indexes = []): string
    {
        $lines = ["$pk INTEGER PRIMARY KEY AUTOINCREMENT"];
        foreach ($columns as $name => $type) {
            if ($name === $pk) continue;
            $sqlType = $this->mapToSqlType($type);
            $lines[] = "$name $sqlType";
        }

        $sql = "CREATE TABLE $table (" . implode(', ', $lines) . ")";
        // Indexes are typically separate in SQLite for CREATE TABLE cleanliness
        return $sql;
    }

    public function getAlterTableSql(string $table, string $column, string $type): string
    {
        $sqlType = $this->mapToSqlType($type);
        return "ALTER TABLE $table ADD COLUMN $column $sqlType";
    }

    public function getIndexSql(string $table, string $column): string
    {
        return "CREATE INDEX idx_{$table}_{$column} ON $table($column)";
    }

    private function mapToSqlType(string $type): string
    {
        $type = strtolower($type);
        if (strpos($type, 'int') !== false) return "INTEGER";
        if (strpos($type, 'bool') !== false) return "INTEGER";
        if (strpos($type, 'json') !== false || strpos($type, 'array') !== false) return "TEXT";
        if (strpos($type, 'date') !== false) return "DATETIME";
        return "TEXT";
    }
}
