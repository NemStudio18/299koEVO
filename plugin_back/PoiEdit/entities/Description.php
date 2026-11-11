<?php
namespace PoiEdit\Entities;

use Core\Storage\JsonActiveRecord;

/**
 * @property int $id
 * @property int $poi_id
 * @property string $lang
 * @property string $content
 * @property int $author_id
 * @property string $status
 * @property string $created_at
 * @property string $updated_at
 */
class Description extends JsonActiveRecord
{
    protected static string $tableName = 'poi_descriptions';
    protected static string $filePath = DATA_PLUGIN . 'PoiEdit/poi_descriptions.json';
    protected static string $storageDriver = self::DRIVER_SQL;
}
