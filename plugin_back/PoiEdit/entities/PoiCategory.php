<?php
namespace PoiEdit\Entities;

use Core\Storage\JsonActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property string $icon
 * @property string $color
 * @property int $parent_id
 * @property int $order_index
 */
class PoiCategory extends JsonActiveRecord
{
    protected static string $tableName = 'poi_categories';
    protected static string $filePath = DATA_PLUGIN . 'PoiEdit/poi_categories.json';
    protected static string $storageDriver = self::DRIVER_SQL;
}
