<?php

namespace Core\Storage\Dialects;

use Core\Interfaces\SqlDialectInterface;

class MysqlDialect implements SqlDialectInterface
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
        return "MATCH(" . implode(', ', $columns) . ") AGAINST($placeholder IN BOOLEAN MODE)";
    }

    public function proximitySql(string $latColumn, string $lonColumn, string $latValue, string $lonValue): string
    {
        // MySQL ST_X is Longitude, ST_Y is Latitude for POINT(lon lat)
        // La formule Haversine standard est : 
        // 6371 * acos(cos(rad(lat1)) * cos(rad(lat2)) * cos(rad(lon2) - rad(lon1)) + sin(rad(lat1)) * sin(rad(lat2)))

        if (empty($lonColumn)) {
            // latColumn est en fait une colonne POINT
            $latColSql = "ST_Y($latColumn)";
            $lonColSql = "ST_X($latColumn)";
        } else {
            $latColSql = $latColumn;
            $lonColSql = $lonColumn;
        }

        return "(6371 * acos(GREATEST(-1.0, LEAST(1.0, cos(radians($latValue)) * cos(radians($latColSql)) * cos(radians($lonColSql) - radians($lonValue)) + sin(radians($latValue)) * sin(radians($latColSql))))))";
    }

    public function conditional(string $condition, string $ifTrue, string $ifFalse): string
    {
        return "IF($condition, $ifTrue, $ifFalse)";
    }

    public function dateSub(string $field, int $interval, string $unit): string
    {
        return "DATE_SUB($field, INTERVAL $interval $unit)";
    }

    public function now(): string
    {
        return "NOW()";
    }

    public function tableExists(string $tableName): string
    {
        return "SHOW TABLES LIKE '$tableName'";
    }

    public function getDateFormat(): string
    {
        return "Y-m-d H:i:s";
    }

    public function getColumnsSql(string $tableName): string
    {
        return "SHOW COLUMNS FROM $tableName";
    }

    public function getName(): string
    {
        return "mysql";
    }

    public function getCreateTableSql(string $table, string $pk, array $columns, array $indexes = []): string
    {
        $lines = ["`$pk` INT AUTO_INCREMENT PRIMARY KEY"];
        foreach ($columns as $name => $type) {
            if ($name === $pk) continue;
            $sqlType = $this->mapToSqlType($type);
            $lines[] = "`$name` $sqlType";
        }
        
        foreach ($indexes as $col) {
            $lines[] = "INDEX `idx_$col` (`$col`)";
        }

        return "CREATE TABLE `$table` (" . implode(', ', $lines) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    }

    public function getAlterTableSql(string $table, string $column, string $type): string
    {
        $sqlType = $this->mapToSqlType($type);
        return "ALTER TABLE `$table` ADD `$column` $sqlType";
    }

    public function getIndexSql(string $table, string $column): string
    {
        return "CREATE INDEX `idx_$column` ON `$table`(`$column`)";
    }

    private function mapToSqlType(string $type): string
    {
        $type = strtolower($type);
        if (strpos($type, 'int') !== false) return "INT";
        if (strpos($type, 'bool') !== false) return "TINYINT(1)";
        if (strpos($type, 'text') !== false) return "TEXT";
        if (strpos($type, 'json') !== false || strpos($type, 'array') !== false) return "JSON";
        if (strpos($type, 'date') !== false) return "DATETIME";
        return "VARCHAR(255)";
    }
}
