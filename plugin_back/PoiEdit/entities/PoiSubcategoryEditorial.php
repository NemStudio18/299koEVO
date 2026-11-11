<?php
namespace PoiEdit\Entities;

use Core\Storage\JsonActiveRecord;

/**
 * @property int $id
 * @property int $category_id
 * @property string $osm_key
 * @property string $label_fr
 * @property string $label_en
 * @property int $priority
 * @property int $active
 * @property string $created_at
 */
class PoiSubcategoryEditorial extends JsonActiveRecord
{
    protected static string $tableName = 'poi_subcategories_editorial';
    protected static string $filePath = DATA_PLUGIN . 'PoiEdit/poi_subcategories_editorial.json';
    protected static string $storageDriver = self::DRIVER_SQL;
}
