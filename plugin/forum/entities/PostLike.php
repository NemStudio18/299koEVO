<?php

namespace Forum\Entities;

defined('ROOT') or exit('Access denied!');

class PostLike extends \Core\Storage\JsonActiveRecord
{
    protected static string $tableName = 'forum_post_likes';
    protected static string $filePath = DATA_PLUGIN . 'forum/post_likes.json';
    protected static string $primaryKey = 'id';
    protected static bool $autoIncrementId = true;
    protected static array $schema = [
        'post_id' => 'integer',
        'user_id' => 'integer',
        'user_email' => 'string',
        'created_at' => 'string'
    ];

    public function __construct(array $data = [])
    {
        parent::__construct($data);
        if ($this->id === null) {
            $this->created_at = date('Y-m-d H:i:s');
        }
    }
}
