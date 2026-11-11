<?php

namespace Core\Navigation;

use Core\Navigation\Interfaces\ResourceInterface;
use Core\Navigation\Interfaces\MetadataAwareInterface;

/**
 * Ressource générique permettant d'envelopper n'importe quel élément pour l'évaluateur.
 * Utile pour migrer les plugins sans modifier leurs classes de domaine.
 */
class GenericResource implements ResourceInterface, MetadataAwareInterface
{
    private string $id;
    private array $policy;
    private array $flags;
    private ?ResourceInterface $parent;
    private array $metadata;

    public function __construct(string $id, array $policy = [], array $flags = [], ?ResourceInterface $parent = null, array $metadata = [])
    {
        $this->id = $id;
        $this->policy = $policy;
        $this->metadata = array_merge(['resource_id' => $id], $metadata);
        // Normalisation des flags (PUB par défaut)
        $this->flags = array_merge([
            'PUB' => true,
            'MEM' => false,
            'ADM' => false
        ], $flags);
        $this->parent = $parent;
    }

    /**
     * @inheritDoc
     */
    public function getResourceId(): string
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function getPolicy(): array
    {
        return $this->policy;
    }

    /**
     * @inheritDoc
     */
    public function getVisibilityFlags(): array
    {
        return $this->flags;
    }

    /**
     * @inheritDoc
     */
    public function getResourceParent(): ?ResourceInterface
    {
        return $this->parent;
    }

    /**
     * @inheritDoc
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
