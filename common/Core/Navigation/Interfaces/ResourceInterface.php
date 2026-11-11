<?php

namespace Core\Navigation\Interfaces;

/**
 * Interface pour toute ressource dont l'accès doit être contrôlé.
 * Permet au AccessPolicyEvaluator d'être agnostique du type de donnée (Page, Article, Plugin...).
 */
interface ResourceInterface
{
    /**
     * Retourne l'identifiant unique de la ressource (ex: 'page:12', 'link:5').
     */
    public function getResourceId(): string;

    /**
     * Retourne les conditions d'accès spécifiques (JSON/Array).
     */
    public function getPolicy(): array;

    /**
     * Retourne les drapeaux legacy (PUB, MEM, ADM) pour compilation.
     */
    public function getVisibilityFlags(): array;

    /**
     * Retourne le parent pour l'héritage récursif.
     */
    public function getResourceParent(): ?ResourceInterface;
}
