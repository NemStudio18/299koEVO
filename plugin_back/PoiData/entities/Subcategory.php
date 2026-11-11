<?php

namespace PoiData\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

/**
 * Sous-catégorie éditoriale
 */
class Subcategory extends JsonActiveRecord
{
    protected static string $tableName = 'poi_subcategories_editorial';
    protected static string $filePath = DATA_PLUGIN . 'poidata/subcategories.json';
    protected static string $storageDriver = self::DRIVER_SQL;
    protected static string $primaryKey = 'id';
    protected static array $casts = [
        'category_id' => 'integer',
        'priority' => 'integer',
        'active' => 'boolean'
    ];
}
