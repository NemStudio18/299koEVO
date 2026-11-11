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
        return (string)($this->attributes['title'] ?? '');
    }

    public function getSlug(): string
    {
        return (string)($this->attributes['slug'] ?? '');
    }

    public function getAuthorName(): string
    {
        return (string)($this->attributes['author_name'] ?? '');
    }

    public function isLocked(): bool
    {
        return (int)($this->attributes['is_locked'] ?? 0) === 1;
    }

    public function isPinned(): bool
    {
        return (int)($this->attributes['is_pinned'] ?? 0) === 1;
    }

    public function getViews(): int
    {
        return (int)($this->attributes['views'] ?? 0);
    }

    public function getPostCount(): int
    {
        return (int)($this->attributes['post_count'] ?? 0);
    }

    public function getCreatedAt(): ?string
    {
        return $this->attributes['created_at'] ?? null;
    }

    public function getLastPostAt(): ?string
    {
        return $this->attributes['last_post_at'] ?? null;
    }

    public function getUrl(): string
    {
        $router = \Core\Router\Router::getInstance();
        return $router->generate('forum-topic', ['id' => $this->getId()]);
    }

    public function getAuthorId(): ?int
    {
        return !empty($this->attributes['author_id']) ? (int)$this->attributes['author_id'] : null;
    }
    
    public function getAuthorEmail(): ?string
    {
        return $this->attributes['author_email'] ?? null;
    }
    
    public function getCategoryId(): ?int
    {
        return !empty($this->attributes['category_id']) ? (int)$this->attributes['category_id'] : null;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}
