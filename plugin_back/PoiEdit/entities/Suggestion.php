<?php
namespace PoiEdit\Entities;

use Core\Storage\JsonActiveRecord;

/**
 * @property int $id
 * @property int $poi_id
 * @property int $user_id
 * @property string $field_name
 * @property string $old_value
 * @property string $new_value
 * @property string $status
 * @property int $moderator_id
 * @property string $moderated_at
 * @property string $created_at
 */
class Suggestion extends JsonActiveRecord
{
    protected static string $tableName = 'poi_suggestions';
    protected static string $filePath = DATA_PLUGIN . 'PoiEdit/poi_suggestions.json';
    protected static string $storageDriver = self::DRIVER_SQL;
}
