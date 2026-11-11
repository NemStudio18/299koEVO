<?php

namespace Core\Storage\Dialects;

use Core\Interfaces\SqlDialectInterface;

class PgsqlDialect implements SqlDialectInterface
{
    public function limit(int $limit, int $offset = 0): string
    {
        return " LIMIT " . $limit . ($offset > 0 ? " OFFSET " . $offset : "");
    }

    public function jsonExtract(string $field, string $path): string
    {
        $parts = explode('.', ltrim($path, '.'));
        $expr = $field;
        foreach ($parts as $part) {
            $expr .= "->>'$part'";
        }
        return $expr;
    }

    public function fulltextMatch(array $columns, string $placeholder): string
    {
        return "to_tsvector('french', " . implode(" || ' ' || ", $columns) . ") @@ plainto_tsquery('french', $placeholder)";
    }

    public function proximitySql(string $latColumn, string $lonColumn, string $latValue, string $lonValue): string
    {
        return "(6371 * acos(LEAST(1.0, cos(radians($latValue)) * cos(radians($latColumn)) * cos(radians($lonColumn) - radians($lonValue)) + sin(radians($latValue)) * sin(radians($latColumn)))))";
    }

    public function conditional(string $condition, string $ifTrue, string $ifFalse): string
    {
        return "(CASE WHEN $condition THEN $ifTrue ELSE $ifFalse END)";
    }

    public function dateSub(string $field, int $interval, string $unit): string
    {
        return "($field - INTERVAL '$interval $unit')";
    }

    public function now(): string
    {
        return "CURRENT_TIMESTAMP";
    }

    public function tableExists(string $tableName): string
    {
        return "SELECT 1 FROM information_schema.tables WHERE table_name = '$tableName'";
    }

    public function getDateFormat(): string
    {
        return "Y-m-d H:i:s";
    }

    public function getColumnsSql(string $tableName): string
    {
        return "SELECT column_name as \"Field\" FROM information_schema.columns WHERE table_name = '$tableName'";
    }

    public function getName(): string
    {
        return "pgsql";
    }

    public function getCreateTableSql(string $table, string $pk, array $columns, array $indexes = []): string
    {
        $lines = ["\"$pk\" SERIAL PRIMARY KEY"];
        foreach ($columns as $name => $type) {
            if ($name === $pk) continue;
            $sqlType = $this->mapToSqlType($type);
            $lines[] = "\"$name\" $sqlType";
        }

        return "CREATE TABLE \"$table\" (" . implode(', ', $lines) . ")";
    }

    public function getAlterTableSql(string $table, string $column, string $type): string
    {
        $sqlType = $this->mapToSqlType($type);
        return "ALTER TABLE \"$table\" ADD COLUMN \"$column\" $sqlType";
    }

    public function getIndexSql(string $table, string $column): string
    {
        return "CREATE INDEX \"idx_{$table}_{$column}\" ON \"$table\"(\"$column\")";
    }

    private function mapToSqlType(string $type): string
    {
        $type = strtolower($type);
        if (strpos($type, 'int') !== false) return "INTEGER";
        if (strpos($type, 'bool') !== false) return "BOOLEAN";
        if (strpos($type, 'json') !== false || strpos($type, 'array') !== false) return "JSONB";
        if (strpos($type, 'date') !== false) return "TIMESTAMP";
        return "TEXT";
    }
}
