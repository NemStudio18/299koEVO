<?php

namespace Annonces\Entities;

use Core\Storage\JsonActiveRecord;
use Core\Storage\StorageFactory;
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
        'images'      => 'array',
    ];

    protected static function boot(): void
    {
        if (static::$isBooted) {
            return;
        }
        parent::boot();

        // Migration douce : assurer la présence de la colonne `images` si stockage SQL
        self::ensureImagesColumn();
    }

    private static function ensureImagesColumn(): void
    {
        try {
            $driver = StorageFactory::getDefaultDriver();
            if ($driver === 'sqlite' || $driver === 'mysql') {
                $conn = StorageFactory::getConnection();
                if ($conn instanceof \PDO) {
                    $hasCol = false;
                    if ($driver === 'sqlite') {
                        $stmt = $conn->query("PRAGMA table_info(annonces)");
                        if ($stmt) {
                            $cols = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                            foreach ($cols as $col) {
                                if (($col['name'] ?? '') === 'images') {
                                    $hasCol = true;
                                    break;
                                }
                            }
                        }
                    } else {
                        $stmt = $conn->query("SHOW COLUMNS FROM annonces LIKE 'images'");
                        if ($stmt && $stmt->fetch()) {
                            $hasCol = true;
                        }
                    }
                    if (!$hasCol) {
                        @$conn->exec("ALTER TABLE annonces ADD COLUMN images TEXT");
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignorer silencieusement si la table n'existe pas encore
        }
    }

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
            'images'         => 'text',
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

    /**
     * Retourne la liste ordonnée de toutes les photos de l'annonce.
     * Rétrocompatible avec le champ unitaire `image`.
     *
     * @return string[]
     */
    public function getImages(): array
    {
        $raw = $this->attributes['images'] ?? [];
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $raw)));
        }
        if (!is_array($raw)) {
            $raw = [];
        }
        if (empty($raw) && !empty($this->attributes['image'])) {
            $raw = [(string) $this->attributes['image']];
        }
        return array_values(array_filter($raw, static fn($v) => is_string($v) && trim($v) !== ''));
    }

    /**
     * Retourne la photo principale de l'annonce.
     */
    public function getMainImage(): string
    {
        $imgs = $this->getImages();
        return $imgs[0] ?? (string) ($this->attributes['image'] ?? '');
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
