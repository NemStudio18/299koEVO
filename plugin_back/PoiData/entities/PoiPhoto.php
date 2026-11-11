<?php

namespace PoiData\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

class PoiPhoto extends JsonActiveRecord
{
    protected static string $tableName = 'poi_photos';
    protected static string $filePath = DATA_PLUGIN . 'poidata/poi_photos.json';
    protected static string $storageDriver = self::DRIVER_SQL;
    protected static string $primaryKey = 'id';
    protected static array $casts = [
        'poi_id' => 'integer',
        'uploaded_by' => 'integer',
        'is_main' => 'boolean'
    ];

    public function __construct($data = [])
    {
        parent::__construct($data);
    }
}
