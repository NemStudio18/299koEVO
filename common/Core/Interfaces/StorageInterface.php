<?php

namespace Core\Interfaces;

/**
 * Interface de haut niveau pour la gestion des données (Records).
 * Abstraite du support physique (JSON, SQL, CSV...).
 */
interface StorageInterface
{
    /**
     * Trouve un record par son identifiant unique.
     */
    public function find(string $id): ?array;

    /**
     * Récupère tous les records correspondants aux critères.
     * @param array $criteria ['field' => 'value'] ou syntaxe plus complexe
     */
    public function findAll(array $criteria = []): array;

    /**
     * Enregistre un record.
     */
    public function save(string $id, array $data): bool;

    /**
     * Supprime un record.
     */
    public function delete(string $id): bool;

    /**
     * Gestion des transactions.
     */
    public function beginTransaction(): bool;
    public function commit(): bool;
    public function rollBack(): bool;

    /**
     * Garantit l'existence d'une table/structure de stockage.
     * @param array $schema Définition des colonnes ['name' => 'type']
     * @param array $indexes Liste des colonnes à indexer
     */
    public function ensureTable(string $table, string $pk, array $schema, array $indexes = []): bool;

    /**
     * Compte les enregistrements correspondant aux critères.
     * Les implémentations SQL l'optimisent avec SELECT COUNT(*).
     */
    public function count(array $criteria = []): int;
}
