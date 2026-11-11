<?php

namespace Core\Navigation\Providers;

use Core\Navigation\Interfaces\ConditionProviderInterface;
use Core\Navigation\ConditionContext;

/**
 * Fournisseur de condition de rôle.
 * Type: 'role'
 * Config: ['value' => 'admin'] ou ['operator' => 'in', 'value' => ['admin', 'editor']]
 */
class RoleProvider implements ConditionProviderInterface
{
    public function getId(): string
    {
        return 'role';
    }

    public function evaluate(array $config, ConditionContext $ctx): bool
    {
        $requiredRole = $config['value'] ?? null;
        if (!$requiredRole) {
            return true;
        }

        $userRoles = $ctx->getRoles();
        $operator = $config['operator'] ?? '=';

        if (is_array($requiredRole)) {
            $operator = 'in';
        }

        switch ($operator) {
            case 'in':
                $requiredArray = (array)$requiredRole;
                return !empty(array_intersect($requiredArray, $userRoles));
            case '!=':
            case 'not':
                return !in_array($requiredRole, $userRoles);
            case '=':
            case 'is':
            default:
                return in_array($requiredRole, $userRoles);
        }
    }

    public function getMetadata(): array
    {
        return [
            'label' => 'Rôle Utilisateur',
            'description' => 'Vérifie les rôles de l\'utilisateur',
            'fields' => [
                'value' => [
                    'type' => 'text',
                    'label' => 'Rôle(s)',
                    'placeholder' => 'admin, member...'
                ],
                'operator' => [
                    'type' => 'select',
                    'label' => 'Opérateur',
                    'options' => [
                        '=' => 'Possède le rôle',
                        '!=' => 'Ne possède pas le rôle',
                        'in' => 'Possède l\'un des rôles'
                    ],
                    'default' => '='
                ]
            ]
        ];
    }

    public function getCost(): int
    {
        return 1; // Très peu coûteux
    }
}
