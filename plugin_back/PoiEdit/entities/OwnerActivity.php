<?php
namespace PoiEdit\Entities;

use Core\Storage\JsonActiveRecord;

/**
 * @property int $id
 * @property int $poi_id
 * @property int $user_id
 * @property string $action
 * @property string $details
 * @property string $created_at
 */
class OwnerActivity extends JsonActiveRecord
{
    protected static string $tableName = 'poi_owner_activity';
    protected static string $filePath = DATA_PLUGIN . 'PoiEdit/owner_activity.json';
    protected static string $storageDriver = self::DRIVER_SQL;
}
