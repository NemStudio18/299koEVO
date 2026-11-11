<?php

namespace Gamification\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

/**
 * Palier de Gamification
 */
class GamificationPalier extends JsonActiveRecord
{
    protected static string $filePath = DATA_PLUGIN . 'gamification/paliers.json';
    protected static string $tableName = 'gamification_paliers';
    protected static string $primaryKey = 'id';
    protected static array $casts = [
        'points_required' => 'integer',
        'id' => 'integer'
    ];

    public function __construct($data = [])
    {
        parent::__construct($data);
    }
}
