<?php

namespace PoiData\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

/**
 * Contribution de POI
 */
class PoiContribution extends JsonActiveRecord
{
    protected static string $tableName = 'poi_contributions';
    protected static string $filePath = DATA_PLUGIN . 'poidata/contributions.json';
    protected static string $storageDriver = self::DRIVER_SQL;
    protected static string $primaryKey = 'id';
    protected static array $casts = [
        'poi_id' => 'integer',
        'user_id' => 'integer',
        'reviewed_by' => 'integer',
        'fields_count' => 'integer',
        'contribution_score' => 'integer',
        'fields_modified' => 'json'
    ];

    public function __construct($data = [])
    {
        parent::__construct($data);
    }
}
