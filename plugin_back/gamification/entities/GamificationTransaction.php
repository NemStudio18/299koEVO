<?php

namespace Gamification\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

/**
 * Transaction de Gamification (Historique des points)
 */
class GamificationTransaction extends JsonActiveRecord
{
    protected static string $filePath = DATA_PLUGIN . 'gamification/transactions.json';
    protected static string $tableName = 'gamification_transactions';
    protected static string $primaryKey = 'id'; // Note: JSON uses implicit index usually, but for SQL we need an ID.
    protected static array $casts = [
        'user_id' => 'integer',
        'points' => 'integer',
        'metadata' => 'json'
    ];

    public function __construct($data = [])
    {
        parent::__construct($data);
        if (empty($this->date)) {
            $this->date = date('Y-m-d H:i:s');
        }
        if (empty($this->id)) {
            $this->id = uniqid();
        }
    }
}
