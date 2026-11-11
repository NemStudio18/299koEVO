<?php

namespace QrPoi\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

/**
 * Scan de QR Code
 */
class QrScan extends JsonActiveRecord
{
    protected static string $filePath = DATA_PLUGIN . 'qr-poi/scans.json';
    protected static string $tableName = 'qr_scans';
    protected static string $primaryKey = 'id';
    protected static array $casts = [
        'poi_id' => 'integer'
    ];

    public function __construct($data = [])
    {
        parent::__construct($data);
        if (empty($this->scanned_at)) {
            $this->scanned_at = date('Y-m-d H:i:s');
        }
    }
}
