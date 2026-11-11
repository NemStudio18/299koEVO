<?php

namespace Forum\Entities;

defined('ROOT') or exit('Access denied!');

/**
 * @property int $id
 * @property int $topic_id
 * @property string $content
 * @property int $author_id
 * @property string $author_name
 * @property string $author_email
 * @property int $is_approved
 * @property string $created_at
 * @property string $updated_at
 * @property string $edited_at
 * @property int $edit_count
 * @property int $like_count
 */
class Post extends \Core\Storage\JsonActiveRecord
{
    protected static string $tableName = 'forum_posts';
    protected static string $filePath = DATA_PLUGIN . 'forum/posts.json';
    protected static string $primaryKey = 'id';
    protected static bool $autoIncrementId = true;
    protected static array $schema = [
        'topic_id' => 'integer',
        'content' => 'string',
        'author_id' => 'integer',
        'author_name' => 'string',
        'author_email' => 'string',
        'is_approved' => 'integer',
        'created_at' => 'string',
        'updated_at' => 'string',
        'edited_at' => 'string',
        'edit_count' => 'integer',
        'like_count' => 'integer'
    ];

    private bool $isLikedByCurrentUser = false;

    public function __construct(array $data = [])
    {
        parent::__construct($data);
        if (!isset($this->attributes['id'])) {
            $this->is_approved = 1;
            $this->edit_count = 0;
            $this->like_count = 0;
            $this->created_at = date('Y-m-d H:i:s');
        }
        $this->isLikedByCurrentUser = (bool)($data['is_liked_by_current_user'] ?? false);
    }

    public function getId(): ?int
    {
        return isset($this->attributes['id']) ? (int)$this->attributes['id'] : null;
    }

    public function getTopicId(): ?int
    {
        return !empty($this->attributes['topic_id']) ? (int)$this->attributes['topic_id'] : null;
    }

    public function getContent(): string
    {
        return (string)($this->attributes['content'] ?? '');
    }

    public function getAuthorName(): string
    {
        return (string)($this->attributes['author_name'] ?? '');
    }

    public function isApproved(): bool
    {
        return (int)($this->attributes['is_approved'] ?? 0) === 1;
    }

    public function getCreatedAt(): string
    {
        return (string)($this->attributes['created_at'] ?? '');
    }
    
    public function getUpdatedAt(): ?string
    {
        return $this->attributes['updated_at'] ?? null;
    }
    
    public function getAuthorId(): ?int
    {
        return !empty($this->attributes['author_id']) ? (int)$this->attributes['author_id'] : null;
    }
    
    public function getAuthorEmail(): ?string
    {
        return $this->attributes['author_email'] ?? null;
    }
    
    public function getEditedAt(): ?string
    {
        return $this->attributes['edited_at'] ?? null;
    }
    
    public function getEditCount(): int
    {
        return (int)($this->attributes['edit_count'] ?? 0);
    }
    
    public function isEdited(): bool
    {
        return $this->getEditCount() > 0;
    }
    
    public function getLikeCount(): int
    {
        return (int)($this->attributes['like_count'] ?? 0);
    }
    
    public function isLikedByCurrentUser(): bool
    {
        return $this->isLikedByCurrentUser;
    }

    public function toArray(): array
    {
        $data = $this->attributes;
        $data['is_edited'] = $this->isEdited();
        $data['is_liked_by_current_user'] = $this->isLikedByCurrentUser;
        return $data;
    }
}

