<?php

namespace PoiData\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

/**
 * Override de catégorie pour un POI
 */
class CategoryOverride extends JsonActiveRecord
{
    protected static string $tableName = 'poi_category_override';
    protected static string $filePath = DATA_PLUGIN . 'poidata/category_overrides.json';
    protected static string $storageDriver = self::DRIVER_SQL;
    protected static string $primaryKey = 'poi_id'; // Utilise poi_id comme clé primaire ici car c'est un lien 1:1
    protected static array $casts = [
        'poi_id' => 'integer',
        'category_id' => 'integer',
        'subcategory_id' => 'integer'
    ];
}
