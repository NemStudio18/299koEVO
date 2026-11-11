<?php
namespace PoiEdit\Entities;

use Core\Storage\JsonActiveRecord;

/**
 * @property int $id
 * @property int $poi_id
 * @property string $url
 * @property string $label
 * @property int $created_by
 * @property string $created_at
 */
class PoiLink extends JsonActiveRecord
{
    protected static string $tableName = 'poi_links';
    protected static string $filePath = DATA_PLUGIN . 'PoiEdit/poi_links.json';
    protected static string $storageDriver = self::DRIVER_SQL;
}
