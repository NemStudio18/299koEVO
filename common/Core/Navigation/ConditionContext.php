<?php

namespace Core\Navigation;

use Core\Core;
use Core\Auth\User;

/**
 * Contexte d'évaluation partagé pour les providers de conditions.
 * Garantit l'absence de lectures redondantes (I/O) lors d'une même requête.
 */
class ConditionContext
{
    /** @var array Cache de données par provider/clé */
    private array $cache = [];

    /** @var User|null Utilisateur actuel */
    private ?User $user = null;

    /** @var array|null Rôles de l'utilisateur */
    private ?array $roles = null;

    /** @var string|null Hash du contexte utilisateur (UID + Rôles) */
    private ?string $userContextHash = null;

    /** @var array Métadonnées de la ressource (id, type, owner_id...) */
    private array $resourceMetadata = [];

    public function __construct(?User $user = null, array $resourceMetadata = [])
    {
        $this->user = $user;
        $this->resourceMetadata = $resourceMetadata;
        $this->computeUserContextHash();
    }

    /**
     * Calcule un hash unique pour le contexte de sécurité actuel.
     * Inclut maintenant les métadonnées de ressource pour distinguer les évaluations.
     */
    private function computeUserContextHash(): void
    {
        $roles = $this->getRoles();
        sort($roles);
        $data = [
            'uid' => $this->getUserId(),
            'roles' => $roles,
            'resource' => $this->resourceMetadata
        ];
        $this->userContextHash = md5(json_encode($data));
    }

    /**
     * Retourne le hash du contexte de sécurité.
     */
    public function getUserContextHash(): string
    {
        return $this->userContextHash;
    }

    /**
     * Récupère une donnée du cache ou l'exécute via le fetcher.
     * Garantit qu'un provider ne fait qu'une seule lecture I/O par requête.
     */
    public function getData(string $providerId, string $key, callable $fetcher)
    {
        $fullKey = $this->userContextHash . ':' . $providerId . ':' . $key;
        if (!isset($this->cache[$fullKey])) {
            $this->cache[$fullKey] = $fetcher();
        }
        return $this->cache[$fullKey];
    }

    /**
     * Récupère l'ID de l'utilisateur actuel.
     */
    public function getUserId(): int|string|null
    {
        return $this->user ? $this->user->getId() : null;
    }

    /**
     * Récupère les rôles de l'utilisateur actuel.
     */
    public function getRoles(): array
    {
        if ($this->roles === null) {
            if (!$this->user) {
                $this->roles = ['guest'];
            } else {
                $role = $this->user->getRole();
                
                // Si le rôle est un ID numérique (legacy), on essaie de le résoudre via le modèle User (qui gère la compatibilité)
                if (is_numeric($role)) {
                    $group = $this->user->getGroup();
                    if ($group) {
                        $role = $group->getSlug();
                    }
                }

                $this->roles = [$role];
                
                // Mappage de compatibilité pour les politiques core (ADM -> admin)
                if ($role === 'admin' && !in_array('ADM', $this->roles)) {
                    $this->roles[] = 'ADM';
                }
                
                if ($role === 'admin' || $role === 'ADM') {
                    $this->roles[] = 'member'; // Admin est aussi membre par défaut
                }
            }
        }
        return $this->roles;
    }

    /**
     * Récupère l'objet utilisateur complet.
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Récupère les métadonnées de la ressource.
     */
    public function getResourceMetadata(): array
    {
        return $this->resourceMetadata;
    }

    /**
     * Force une valeur dans le cache (utile pour pré-chargement).
     */
    public function setCache(string $providerId, string $key, $value): void
    {
        $this->cache[$this->userContextHash . ':' . $providerId . ':' . $key] = $value;
    }

    /**
     * Définit les métadonnées de la ressource et recalcule le hash.
     */
    public function setResourceMetadata(array $metadata): void
    {
        $this->resourceMetadata = $metadata;
        $this->computeUserContextHash();
    }
}
