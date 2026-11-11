<?php

namespace PoiData\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

/**
 * Signalement de POI
 */
class PoiReport extends JsonActiveRecord
{
    protected static string $tableName = 'poi_reports';
    protected static string $filePath = DATA_PLUGIN . 'poidata/reports.json';
    protected static string $storageDriver = self::DRIVER_SQL;
    protected static string $primaryKey = 'id';
    protected static array $casts = [
        'poi_id' => 'integer',
        'user_id' => 'integer',
        'reviewed_by' => 'integer'
    ];

    public function __construct($data = [])
    {
        parent::__construct($data);
    }
}
