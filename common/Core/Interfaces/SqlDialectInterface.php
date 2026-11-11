<?php

namespace Core\Interfaces;

/**
 * Interface pour la résolution des dialectes SQL.
 * Gère les différences syntaxiques entre MySQL, PostgreSQL et SQLite.
 */
interface SqlDialectInterface
{
    /**
     * Génère la clause de limitation (LIMIT/OFFSET).
     */
    public function limit(int $limit, int $offset = 0): string;

    /**
     * Génère l'expression pour extraire une valeur d'un champ JSON.
     */
    public function jsonExtract(string $field, string $path): string;

    /**
     * Génère l'expression pour une recherche plein texte (Fulltext).
     */
    public function fulltextMatch(array $columns, string $placeholder): string;

    /**
     * Génère l'expression pour le calcul de distance ou filtrage géographique.
     */
    public function proximitySql(string $latColumn, string $lonColumn, string $latValue, string $lonValue): string;

    /**
     * Génère une expression conditionnelle (IF/CASE).
     */
    public function conditional(string $condition, string $ifTrue, string $ifFalse): string;

    /**
     * Génère une expression de soustraction de date (DATE_SUB/INTERVAL).
     */
    public function dateSub(string $field, int $interval, string $unit): string;

    /**
     * Retourne le mot-clé pour "Maintenant" (NOW()/CURRENT_TIMESTAMP).
     */
    public function now(): string;

    /**
     * Génère la requête SQL pour vérifier l'existence d'une table.
     */
    public function tableExists(string $tableName): string;

    /**
     * Retourne le type de données SQL pour les dates.
     */
    public function getDateFormat(): string;

    /**
     * Retourne la requête SQL pour lister les colonnes d'une table.
     */
    public function getColumnsSql(string $tableName): string;

    /**
     * Retourne le driver PDO correspondant.
     */
    public function getName(): string;

    /**
     * Génère le SQL pour la création d'une table.
     */
    public function getCreateTableSql(string $table, string $pk, array $columns, array $indexes = []): string;

    /**
     * Génère le SQL pour l'ajout d'une colonne.
     */
    public function getAlterTableSql(string $table, string $column, string $type): string;

    /**
     * Génère le SQL pour la création d'un index.
     */
    public function getIndexSql(string $table, string $column): string;
}
