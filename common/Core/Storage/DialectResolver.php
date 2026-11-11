<?php

namespace Core\Storage;

use Core\Interfaces\SqlDialectInterface;
use Core\Storage\Dialects\MysqlDialect;
use Core\Storage\Dialects\PgsqlDialect;
use Core\Storage\Dialects\SqliteDialect;

class DialectResolver
{
    public static function resolve(string $driverName): SqlDialectInterface
    {
        switch (strtolower($driverName)) {
            case 'pgsql':
                return new PgsqlDialect();
            case 'sqlite':
                return new SqliteDialect();
            case 'mysql':
            default:
                return new MysqlDialect();
        }
    }
}
