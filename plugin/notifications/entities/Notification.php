<?php

namespace Notifications\Entities;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

class Notification extends JsonActiveRecord
{
    protected static string $tableName = 'notifications';
    protected static string $filePath = DATA_PLUGIN . 'notifications/notifications.json';
    protected static string $primaryKey = 'id';
    
    protected static array $casts = [
        'user_id' => 'integer',
        'is_read' => 'boolean',
        'priority' => 'integer',
        'data' => 'array',
        'created_at' => 'date'
    ];
}
