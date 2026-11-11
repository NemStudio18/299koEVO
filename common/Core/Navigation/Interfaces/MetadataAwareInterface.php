<?php

namespace Core\Navigation\Interfaces;

/**
 * Interface pour les ressources capables de fournir des métadonnées contextuelles.
 * Permet au moteur d'accès d'évaluer des règles basées sur l'ID ou le type de la ressource.
 */
interface MetadataAwareInterface
{
    /**
     * Retourne les métadonnées de la ressource.
     * @return array ex: ['resource_id' => 123, 'resource_type' => 'poi', 'owner_id' => 45]
     */
    public function getMetadata(): array;
}
