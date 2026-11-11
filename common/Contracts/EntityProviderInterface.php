<?php

namespace Core\Contracts;

/**
 * Interface EntityProviderInterface
 * 
 * Defines the contract for an Entity Provider (e.g. PoiProvider).
 * Ecosystem plugins use this contract to request entity details without coupling
 * to the entity's source implementation.
 */
interface EntityProviderInterface
{
    /**
     * Get details for a single entity
     * 
     * @param int $id The entity ID
     * @return array|null The entity data or null if not found
     */
    public function get(int $id): ?array;

    /**
     * Get details for multiple entities
     * 
     * @param array $ids List of entity IDs
     * @return array Key-value pair of ID => Data
     */
    public function getMany(array $ids): array;

    /**
     * Get the name of an entity
     * 
     * @param int $id
     * @return string|null
     */
    public function getName(int $id): ?string;

    /**
     * Get the URL slug or full URL for an entity
     * 
     * @param int $id
     * @return string|null
     */
    public function getUrl(int $id): ?string;

    /**
     * Get entity ID by its slug or name
     * 
     * @param string $slug
     * @return int|null
     */
    public function getIdBySlug(string $slug): ?int;
}
