<?php

namespace Core\Navigation\Providers;

use Core\Navigation\Interfaces\ConditionProviderInterface;
use Core\Navigation\ConditionContext;

/**
 * Fournisseur de condition d'authentification.
 * Type: 'auth'
 * Config: ['value' => true/false]
 */
class AuthProvider implements ConditionProviderInterface
{
    public function getId(): string
    {
        return 'auth';
    }

    public function evaluate(array $config, ConditionContext $ctx): bool
    {
        $required = $config['value'] ?? true;
        $isLogged = $ctx->getUserId() !== null;

        return $isLogged === $required;
    }

    public function getMetadata(): array
    {
        return [
            'label' => 'Authentification',
            'description' => 'Vérifie si l\'utilisateur est connecté',
            'fields' => [
                'value' => [
                    'type' => 'boolean',
                    'label' => 'Doit être connecté',
                    'default' => true
                ]
            ]
        ];
    }

    public function getCost(): int
    {
        return 1; // Très peu coûteux (déjà en mémoire)
    }
}
