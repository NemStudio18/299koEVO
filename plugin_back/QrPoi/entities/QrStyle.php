<?php

namespace QrPoi\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

/**
 * Style de QR Code
 */
class QrStyle extends JsonActiveRecord
{
    protected static string $filePath = DATA_PLUGIN . 'qr-poi/styles.json';
    protected static string $tableName = 'qr_styles';
    protected static string $primaryKey = 'poi_id';
    protected static array $casts = [
        'poi_id' => 'integer',
        'user_id' => 'integer',
        'size' => 'integer',
        'qzone' => 'integer'
    ];

    public function __construct($data = [])
    {
        parent::__construct($data);
    }
}
