<?php

namespace Core\Navigation\Providers;

use Core\Navigation\Interfaces\ConditionProviderInterface;
use Core\Navigation\ConditionContext;

/**
 * Fournisseur de condition basées sur les métadonnées de la ressource.
 * Type: 'meta'
 * Config: [
 *   'key' => 'owner_id',
 *   'operator' => 'equals_user_id'
 * ]
 */
class MetadataProvider implements ConditionProviderInterface
{
    public function getId(): string
    {
        return 'meta';
    }

    public function evaluate(array $config, ConditionContext $ctx): bool
    {
        $meta = $ctx->getResourceMetadata();
        $key = $config['key'] ?? '';
        $value = $meta[$key] ?? null;
        
        $required = $config['value'] ?? null;
        $operator = $config['operator'] ?? '=';

        // Cas spécial : is_owner sans clé (vérification globale via CurrentUser)
        if (empty($key) && $required === 'is_owner' && $operator === '=') {
            return \Core\Auth\CurrentUser::get()->isOwner();
        }

        switch ($operator) {
            case 'equals_user_id':
                $userId = $ctx->getUserId();
                return $userId !== null && $value == $userId;
            
            case 'is_empty':
                return empty($value);
                
            case 'is_not_empty':
                return !empty($value);

            case '!=': return $value != $required;
            case '>': return $value > $required;
            case '<': return $value < $required;
            case 'in': return is_array($required) && in_array($value, $required);
            case '=':
            default: return $value == $required;
        }
    }

    public function getMetadata(): array
    {
        return [
            'label' => 'Métadonnées de ressource',
            'description' => 'Vérifie une valeur dans les métadonnées de la ressource (id, owner_id, status...)',
            'fields' => [
                'key' => ['type' => 'text', 'label' => 'Clé (ex: owner_id)'],
                'operator' => ['type' => 'select', 'label' => 'Opérateur', 'options' => ['=', '!=', 'equals_user_id', 'is_empty']],
                'value' => ['type' => 'text', 'label' => 'Valeur attendue']
            ]
        ];
    }

    public function getCost(): int
    {
        return 1; // Très faible (lecture mémoire du contexte)
    }
}
