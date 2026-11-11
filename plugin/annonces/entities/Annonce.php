<?php

namespace Annonces\Entities;

use Core\Storage\JsonActiveRecord;
use Core\Router\Router;

defined('ROOT') OR exit('Access denied!');

/**
 * Entité représentant une petite annonce créée par l'administrateur.
 */
class Annonce extends JsonActiveRecord
{
    public const VALID_STATUSES = ['available', 'reserved', 'closed', 'draft'];

    protected static string $filePath = DATA_PLUGIN . 'annonces/annonces.json';
    protected static string $tableName = 'annonces';
    protected static string $primaryKey = 'id';
    protected static bool $isBooted = false;

    protected static array $casts = [
        'is_featured' => 'int',
    ];

    protected static function getSchema(): array
    {
        return [
            'title'          => 'string',
            'slug'           => 'string',
            'category'       => 'string',
            'price_label'    => 'string',
            'location'       => 'string',
            'item_condition' => 'string',
            'image'          => 'string',
            'description'    => 'text',
            'contact_info'   => 'string',
            'status'         => 'string',
            'is_featured'    => 'int',
            'created_at'     => 'datetime',
            'updated_at'     => 'datetime',
        ];
    }

    public function getId(): string|int
    {
        return $this->attributes['id'] ?? '';
    }

    public function isAvailable(): bool
    {
        return ($this->attributes['status'] ?? 'available') === 'available';
    }

    public function isReserved(): bool
    {
        return ($this->attributes['status'] ?? '') === 'reserved';
    }

    public function isPublic(): bool
    {
        return in_array($this->attributes['status'] ?? 'available', ['available', 'reserved', 'closed'], true);
    }

    public function getStatusLabel(): string
    {
        $map = [
            'available' => 'Disponible',
            'reserved'  => 'Réservée',
            'closed'    => 'Clôturée',
            'draft'     => 'Brouillon',
        ];
        $status = $this->attributes['status'] ?? 'available';
        return $map[$status] ?? $status;
    }

    public function getPublicUrl(): string
    {
        $slug = trim((string) ($this->attributes['slug'] ?? ''));
        if ($slug === '') {
            $slug = 'annonce';
        }
        return Router::getInstance()->generate('annonces-view', [
            'id' => $this->getId(),
            'slug' => $slug,
        ]);
    }
}
