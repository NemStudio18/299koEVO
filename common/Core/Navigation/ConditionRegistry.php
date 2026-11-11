<?php

namespace Core\Navigation;

use Core\Navigation\Interfaces\ConditionProviderInterface;
use Core\Core;

/**
 * Registre des fournisseurs de conditions.
 * Service Locator pour le moteur d'évaluation.
 */
class ConditionRegistry
{
    private static ?ConditionRegistry $instance = null;

    /** @var ConditionProviderInterface[] List of registered providers */
    private array $providers = [];

    private function __construct() {}

    public static function getInstance(): ConditionRegistry
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->initializeCoreProviders();
        }
        return self::$instance;
    }

    /**
     * Enregistre un nouveau fournisseur de conditions.
     */
    public function registerProvider(ConditionProviderInterface $provider): void
    {
        $id = $provider->getId();
        $this->providers[$id] = $provider;
    }

    /**
     * Récupère un fournisseur par son ID.
     */
    public function getProvider(string $id): ?ConditionProviderInterface
    {
        return $this->providers[$id] ?? null;
    }

    /**
     * Récupère tous les fournisseurs enregistrés.
     */
    public function getAllProviders(): array
    {
        return $this->providers;
    }

    /**
     * Initialise les providers par défaut (Core).
     */
    public function initializeCoreProviders(): void
    {
        $this->registerProvider(new \Core\Navigation\Providers\AuthProvider());
        $this->registerProvider(new \Core\Navigation\Providers\RoleProvider());
        $this->registerProvider(new \Core\Navigation\Providers\SqlProvider());
        $this->registerProvider(new \Core\Navigation\Providers\JsonProvider());
        $this->registerProvider(new \Core\Navigation\Providers\MetadataProvider());
    }
}
