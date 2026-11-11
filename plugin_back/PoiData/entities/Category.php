<?php

namespace PoiData\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

/**
 * Catégorie éditoriale
 */
class Category extends JsonActiveRecord
{
    protected static string $tableName = 'poi_categories';
    protected static string $filePath = DATA_PLUGIN . 'poidata/categories.json';
    protected static string $storageDriver = self::DRIVER_SQL;
    protected static string $primaryKey = 'id';
    protected static array $casts = [
        'priority' => 'integer',
        'active' => 'boolean'
    ];
}
