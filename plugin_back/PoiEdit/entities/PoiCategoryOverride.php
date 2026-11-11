<?php
namespace PoiEdit\Entities;

use Core\Storage\JsonActiveRecord;

/**
 * @property int $poi_id
 * @property int $category_id
 * @property int $subcategory_id
 * @property string $source
 * @property string $updated_at
 */
class PoiCategoryOverride extends JsonActiveRecord
{
    protected static string $tableName = 'poi_category_overrides';
    protected static string $filePath = DATA_PLUGIN . 'PoiEdit/poi_category_overrides.json';
    protected static string $storageDriver = self::DRIVER_SQL;
    protected static string $primaryKey = 'poi_id';
}
