<?php

namespace Forum\Entities;

defined('ROOT') or exit('Access denied!');

class ForumNotification extends \Core\Storage\JsonActiveRecord
{
    protected static string $tableName = 'forum_notifications';
    protected static string $filePath = DATA_PLUGIN . 'forum/notifications.json';
    protected static string $primaryKey = 'id';
    protected static array $schema = [
        'user_id' => 'integer',
        'type' => 'string',
        'topic_id' => 'integer',
        'post_id' => 'integer',
        'message' => 'string',
        'is_read' => 'integer',
        'created_at' => 'string'
    ];

    public function __construct(array $data = [])
    {
        parent::__construct($data);
        if ($this->id === null) {
            $this->created_at = date('Y-m-d H:i:s');
            $this->is_read = $this->is_read ?? 0;
        }
    }
}
