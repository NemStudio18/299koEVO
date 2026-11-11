<?php

namespace PoiData\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

class PoiTag extends JsonActiveRecord
{
    protected static string $tableName = 'poi_tags';
    protected static string $filePath = DATA_PLUGIN . 'poidata/poi_tags.json';
    protected static string $storageDriver = self::DRIVER_SQL;
    protected static string $primaryKey = 'id';
    protected static array $casts = [
        'poi_id' => 'integer',
        'created_by' => 'integer'
    ];

    public function __construct($data = [])
    {
        parent::__construct($data);
    }
}
