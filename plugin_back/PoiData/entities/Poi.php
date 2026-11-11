<?php

namespace PoiData\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

/**
 * POI (Point of Interest)
 * 
 * Entité représentant un établissement ou un lieu d'intérêt.
 */
class Poi extends JsonActiveRecord
{
    protected static string $tableName = 'poi_public';
    protected static string $filePath = DATA_PLUGIN . 'poidata/poi.json';
    protected static string $storageDriver = self::DRIVER_SQL;
    protected static string $primaryKey = 'id';
    protected static array $casts = [
        'lat' => 'float',
        'lon' => 'float',
        'is_closed' => 'boolean',
        'claimed_by' => 'integer'
    ];

    public function __construct($data = [])
    {
        parent::__construct($data);
    }
}
