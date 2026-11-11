<?php

namespace Forum\Entities;

defined('ROOT') or exit('Access denied!');

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property int $parent_id
 * @property int $order_index
 * @property string $icon
 * @property string $color
 * @property string $created_at
 * @property int $topic_count
 * @property int $post_count
 */
class Category extends \Core\Storage\JsonActiveRecord
{
    protected static string $tableName = 'forum_categories';
    protected static string $filePath = DATA_PLUGIN . 'forum/categories.json';
    protected static string $primaryKey = 'id';
    protected static array $schema = [
        'name' => 'string',
        'slug' => 'string',
        'description' => 'string',
        'parent_id' => 'integer',
        'order_index' => 'integer',
        'icon' => 'string',
        'color' => 'string',
        'created_at' => 'string',
        'topic_count' => 'integer',
        'post_count' => 'integer'
    ];

    private $subcategories = [];

    public function __construct(array $data = [])
    {
        parent::__construct($data);
        if (!isset($this->attributes['id'])) {
            $this->order_index = (int)($data['order_index'] ?? 0);
            $this->topic_count = 0;
            $this->post_count = 0;
            $this->created_at = date('Y-m-d H:i:s');
        }
    }

    public function getId(): ?int
    {
        return isset($this->attributes['id']) ? (int)$this->attributes['id'] : null;
    }

    public function getName(): string
    {
        return (string)$this->name;
    }

    public function getSlug(): string
    {
        return (string)$this->slug;
    }

    public function getDescription(): string
    {
        return (string)$this->description;
    }

    public function getOrderIndex(): int
    {
        return (int)$this->order_index;
    }

    public function getTopicCount(): int
    {
        return (int)$this->topic_count;
    }

    public function getPostCount(): int
    {
        return (int)$this->post_count;
    }
    
    public function setTopicCount(int $count): void
    {
        $this->topic_count = $count;
    }
    
    public function setPostCount(int $count): void
    {
        $this->post_count = $count;
    }
    
    public function getParentId(): ?int
    {
        return $this->parent_id ? (int)$this->parent_id : null;
    }
    
    public function isSubcategory(): bool
    {
        return $this->getParentId() !== null;
    }
    
    public function getIcon(): string
    {
        return (string)$this->icon;
    }
    
    public function getColor(): string
    {
        return (string)$this->color;
    }
    
    public function setSubcategories(array $subcategories): void
    {
        $this->subcategories = $subcategories;
    }
    
    public function getSubcategories(): array
    {
        return $this->subcategories;
    }

    public function getUrl(): string
    {
        $router = \Core\Router\Router::getInstance();
        try {
            return $router->generate('forum-category', ['id' => $this->id]);
        } catch (\Exception $e) {
            return '/forum/category/' . $this->id;
        }
    }

    public function toArray(): array
    {
        $data = $this->attributes;
        $data['is_subcategory'] = $this->isSubcategory();
        $data['subcategories'] = array_map(function($sub) { return $sub->toArray(); }, $this->subcategories);
        return $data;
    }
}

