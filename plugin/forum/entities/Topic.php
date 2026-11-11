<?php

namespace Forum\Entities;

use Utils\Util;

defined('ROOT') or exit('Access denied!');

/**
 * @property int $id
 * @property int $category_id
 * @property string $title
 * @property string $slug
 * @property int $author_id
 * @property string $author_name
 * @property string $author_email
 * @property int $is_locked
 * @property int $is_pinned
 * @property int $views
 * @property string $last_post_at
 * @property string $created_at
 * @property int $post_count
 */
class Topic extends \Core\Storage\JsonActiveRecord
{
    protected static string $tableName = 'forum_topics';
    protected static string $filePath = DATA_PLUGIN . 'forum/topics.json';
    protected static string $primaryKey = 'id';
    protected static bool $autoIncrementId = true;
    protected static array $schema = [
        'category_id' => 'integer',
        'title' => 'string',
        'slug' => 'string',
        'author_id' => 'integer',
        'author_name' => 'string',
        'author_email' => 'string',
        'is_locked' => 'integer',
        'is_pinned' => 'integer',
        'views' => 'integer',
        'last_post_at' => 'string',
        'created_at' => 'string',
        'post_count' => 'integer'
    ];

    public function __construct(array $data = [])
    {
        parent::__construct($data);
        if (!isset($this->attributes['id'])) {
            $this->is_locked = 0;
            $this->is_pinned = 0;
            $this->views = 0;
            $this->post_count = 0;
            $this->created_at = date('Y-m-d H:i:s');
        }
    }

    public function getId(): ?int
    {
        return isset($this->attributes['id']) ? (int)$this->attributes['id'] : (isset($this->attributes['topic_id']) ? (int)$this->attributes['topic_id'] : null);
    }

    public function getTitle(): string
    {
        return (string)$this->title;
    }

    public function getSlug(): string
    {
        return (string)$this->slug;
    }

    public function getAuthorName(): string
    {
        return (string)$this->author_name;
    }

    public function isLocked(): bool
    {
        return (int)$this->is_locked === 1;
    }

    public function isPinned(): bool
    {
        return (int)$this->is_pinned === 1;
    }

    public function getViews(): int
    {
        return (int)$this->views;
    }

    public function getPostCount(): int
    {
        return (int)$this->post_count;
    }

    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    public function getLastPostAt(): ?string
    {
        return $this->last_post_at;
    }

    public function getUrl(): string
    {
        $router = \Core\Router\Router::getInstance();
        return $router->generate('forum-topic', ['id' => $this->id]);
    }

    public function getAuthorId(): ?int
    {
        return $this->author_id ? (int)$this->author_id : null;
    }
    
    public function getAuthorEmail(): ?string
    {
        return $this->author_email;
    }
    
    public function getCategoryId(): ?int
    {
        return $this->category_id ? (int)$this->category_id : null;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}
