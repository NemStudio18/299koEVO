<?php

namespace Core\Navigation\Interfaces;

use Core\Navigation\ConditionContext;

/**
 * Interface pour les fournisseurs de conditions de navigation et d'accès.
 * Chaque source de données (MySQL, SQLite, JSON, etc.) doit implémenter cette interface.
 */
interface ConditionProviderInterface
{
    /**
     * Évalue une règle de condition unitaire.
     * 
     * @param array $config Configuration de la règle (extraite du JSON).
     * @param ConditionContext $ctx Contexte d'évaluation partagé (cache I/O).
     * @return bool True si la condition est remplie, false sinon.
     */
    public function evaluate(array $config, ConditionContext $ctx): bool;

    /**
     * Retourne les métadonnées pour l'interface d'administration.
     * Utilisé pour générer dynamiquement les formulaires d'édition.
     * 
     * @return array [label, params, operators]
     */
    public function getMetadata(): array;

    /**
     * Retourne le coût d'exécution estimé.
     * 1: Très rapide (Session, Rôle)
     * 5: Moyen (Lecture fichier unique)
     * 10: Élevé (Requête DB, Parsing lourd)
     * 
     * @return int
     */
    public function getCost(): int;

    /**
     * Identifiant unique du provider (ex: 'auth', 'mysql_field').
     * 
     * @return string
     */
    public function getId(): string;
}
