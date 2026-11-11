<?php

namespace PoiData\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

/**
 * Géométrie des communes (centroids)
 */
class CommuneGeometry extends JsonActiveRecord
{
    protected static string $tableName = 'commune_geometry';
    protected static string $filePath = DATA_PLUGIN . 'poidata/commune_geometry.json';
    protected static string $primaryKey = 'name'; // Le nom est la clé naturelle ici
    protected static string $storageDriver = self::DRIVER_SQL;
    protected static array $casts = [
        'centroid_lat' => 'float',
        'centroid_lon' => 'float',
        'bounding_radius_km' => 'float'
    ];
}
