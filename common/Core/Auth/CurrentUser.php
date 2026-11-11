<?php

namespace Core\Auth;

use Core\Core;
use Core\Auth\UsersManager;

/**
 * Singleton représentant l'utilisateur actuel avec son contexte métier (SaaS).
 * 
 * Centralise les états : 
 * - Authentification (id, role)
 * - Métier (propriétaire, abonné, revendications)
 * - Gamification (points)
 */
class CurrentUser
{
    private static ?CurrentUser $instance = null;
    private ?User $user = null;
    
    private const CONTEXT_HIERARCHY = [
        'guest',        // non connecté
        'member',       // connecté, aucun claim
        'contributor',  // connecté, a contribué (avis/favs) mais pas owner
        'owner',        // claim validé, pas d'abo
        'premium',      // claim + abonnement actif
    ];

    // Cache structuré pour les données et le contexte
    private array $data = [
        'flags' => [
            'is_logged' => false,
            'is_owner' => false,
            'is_subscriber' => false,
            'has_contributions' => false,
        ],
        'capabilities' => [
            'messaging' => false,
            'favorites' => false,
            'notifications' => false,
        ],
        'addons' => [],
        'claims' => [],
        'subscription' => null,
        'points' => 0
    ];

    private bool $initialized = false;

    private function __construct()
    {
        $this->user = UsersManager::getCurrentUser();
        $this->data['flags']['is_logged'] = ($this->user !== null);
    }

    /**
     * Instance du singleton
     */
    public static function get(): CurrentUser
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Phase d'initialisation explicite (appelée après le boot des plugins)
     */
    public function initialize(bool $force = false): void
    {
        if (!$force && $this->initialized) {
            return;
        }

        // Re-fetch user if forced (simulation mode)
        if ($force) {
            $this->user = UsersManager::getCurrentUser();
            $this->data['flags']['is_logged'] = ($this->user !== null);
        }

        if (!$this->isLogged()) {
            $this->initialized = true;
            return;
        }

        $core = Core::getInstance();
        
        // Collecte des données via le hook de filtre
        $this->data = $core->callHook('user:collect-context-data', $this->data);
        
        // Calcul automatique des drapeaux et capacités
        $this->calculateContext();
        
        $this->initialized = true;
    }

    private function calculateContext(): void
    {
        // Flags métier de base
        $this->data['flags']['is_owner'] = !empty($this->data['claims']);
        $this->data['flags']['is_subscriber'] = ($this->data['subscription'] !== null);
        
        // Les capacités peuvent être déduites des flags ou surchargées par les plugins
        if ($this->isLogged()) {
            $this->data['capabilities']['favorites'] = true;
            // Un contributeur a des points ou des avis (placeholder)
            if ($this->data['points'] > 0) {
                $this->data['flags']['has_contributions'] = true;
            }
        }
    }

    // --- API d'accès aux données ---

    public function isLogged(): bool
    {
        return $this->data['flags']['is_logged'];
    }

    public function id(): string|int|null
    {
        return $this->user ? $this->user->getId() : null;
    }

    public function username(): string
    {
        return $this->user ? $this->user->username() : 'Visitor';
    }

    public function role(): string
    {
        return $this->user ? $this->user->getRole() : 'guest';
    }

    /**
     * L'utilisateur est-il propriétaire (au moins une fiche approved) ?
     */
    public function isOwner(): bool
    {
        return $this->data['flags']['is_owner'];
    }

    /**
     * L'utilisateur a-t-il le statut Pro Pilote (ambassadeur / accès illimité) ?
     */
    public function isPilot(): bool
    {
        if ($this->user && method_exists($this->user, 'isProPilote') && $this->user->isProPilote()) {
            return true;
        }
        return !empty($this->data['flags']['is_pilot']);
    }

    /**
     * L'utilisateur a-t-il un abonnement actif ?
     */
    public function isSubscriber(): bool
    {
        if ($this->isPilot()) {
            return true;
        }
        return $this->data['flags']['is_subscriber'];
    }

    /**
     * Alias sémantique pour l'UI
     */
    public function isPremium(): bool
    {
        return $this->isSubscriber();
    }

    public function hasClaims(): bool
    {
        return !empty($this->data['claims']);
    }

    public function claims(): array
    {
        return $this->data['claims'] ?? [];
    }

    public function capability(string $name): bool
    {
        return $this->data['capabilities'][$name] ?? false;
    }

    /**
     * Vérifie les permissions via AccessPolicyEvaluator
     */
    public function can_access(string $resource): bool
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        return $evaluator->canAccess(new \Core\Navigation\GenericResource($resource), $ctx);
    }

    /**
     * Alias de can_access pour la compatibilité
     */
    public function canAccess(string $resource): bool
    {
        return $this->can_access($resource);
    }

    public function hasAddon(string $name): bool
    {
        if ($this->isPilot()) {
            return true;
        }
        return isset($this->data['addons'][$name]) && $this->data['addons'][$name] === true;
    }

    public function points(): int
    {
        return $this->data['points'];
    }

    /**
     * Déterministique : retourne le niveau le plus élevé pour le contexte UI/CSS.
     * Priorité : Premium > Owner > Contributor > Member > Guest
     */
    public function uiContext(): string
    {
        $prefix = 'user-ctx-';

        if (!$this->isLogged()) {
            return $prefix . 'guest';
        }

        if ($this->isPremium()) {
            return $prefix . 'premium';
        }

        if ($this->isOwner()) {
            return $prefix . 'owner';
        }

        if ($this->data['flags']['has_contributions'] ?? false) {
            return $prefix . 'contributor';
        }

        return $prefix . 'member';
    }

    /**
     * Accès magique aux données brutes si nécessaire
     */
    public function __get($name)
    {
        if ($name === 'attributes') {
            return $this->attributes();
        }
        return $this->data[$name] ?? null;
    }

    /**
     * Attributs du profil de l'utilisateur connecté (ex: theme_preference).
     * Tableau vide pour un visiteur ou si le modèle n'en expose pas.
     */
    public function attributes(): array
    {
        if (!$this->user) {
            return [];
        }
        $attrs = $this->user->attributes ?? [];
        return is_array($attrs) ? $attrs : [];
    }
}
