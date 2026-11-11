<?php
namespace PoiEdit\Entities;

use Core\Storage\JsonActiveRecord;

/**
 * @property int $id
 * @property string $key_name
 * @property string $default_value
 * @property string $emoji
 * @property string $label
 * @property string $description
 * @property int $is_active
 * @property string $created_at
 * @property string $updated_at
 */
class CustomTag extends JsonActiveRecord
{
    protected static string $tableName = 'poi_custom_tags';
    protected static string $filePath = DATA_PLUGIN . 'PoiEdit/poi_custom_tags.json';
    protected static string $storageDriver = self::DRIVER_SQL;
}
