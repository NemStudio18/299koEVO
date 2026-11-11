<?php

namespace Core\Interfaces;

/**
 * Interface de bas niveau pour l'exécution SQL.
 * Dialogue avec PDO et gère la robustesse (exceptions, prepares).
 */
interface SqlDriverInterface
{
    /**
     * Exécute une requête de lecture.
     */
    public function query(string $sql, array $params = [], ?string $connection = null): array;

    /**
     * Exécute une requête d'écriture.
     */
    public function execute(string $sql, array $params = [], ?string $connection = null): bool;

    /**
     * Récupère l'ID du dernier enregistrement inséré.
     */
    public function lastInsertId(?string $name = null): string;

    /**
     * Retourne le nom du driver (mysql, pgsql, sqlite).
     */
    public function getDriverName(): string;

    /**
     * Gestion des transactions.
     */
    public function beginTransaction(): bool;
    public function commit(): bool;
    public function rollBack(): bool;
}
