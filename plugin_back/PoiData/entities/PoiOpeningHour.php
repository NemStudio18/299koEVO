<?php

namespace PoiData\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

/**
 * PoiOpeningHour
 * 
 * Entité représentant une plage horaire d'ouverture pour un POI.
 */
class PoiOpeningHour extends JsonActiveRecord
{
    protected static string $tableName = 'poi_opening_hours';
    protected static string $filePath = DATA_PLUGIN . 'poidata/poi_opening_hours.json';
    protected static string $storageDriver = self::DRIVER_SQL;
    protected static string $primaryKey = 'id';
    protected static array $casts = [
        'poi_id' => 'integer',
        'day_of_week' => 'integer',
        'slot_index' => 'integer',
        'is_closed' => 'boolean'
    ];

    public function __construct($data = [])
    {
        parent::__construct($data);
    }
}
