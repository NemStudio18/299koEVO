<?php
namespace PoiEdit\Entities;

use Core\Storage\JsonActiveRecord;

/**
 * @property int $id
 * @property int $poi_id
 * @property string $subcategory
 * @property string $created_at
 */
class PoiSubcategory extends JsonActiveRecord
{
    protected static string $tableName = 'poi_subcategories';
    protected static string $filePath = DATA_PLUGIN . 'PoiEdit/poi_subcategories.json';
    protected static string $storageDriver = self::DRIVER_SQL;
}
