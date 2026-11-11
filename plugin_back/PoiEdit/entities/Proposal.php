<?php
namespace PoiEdit\Entities;

use Core\Storage\JsonActiveRecord;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property float $latitude
 * @property float $longitude
 * @property int $category_id
 * @property string $data
 * @property string $status
 * @property int $moderator_id
 * @property string $moderated_at
 * @property string $created_at
 */
class Proposal extends JsonActiveRecord
{
    protected static string $tableName = 'poi_proposals';
    protected static string $filePath = DATA_PLUGIN . 'PoiEdit/poi_proposals.json';
    protected static string $storageDriver = self::DRIVER_SQL;
}
