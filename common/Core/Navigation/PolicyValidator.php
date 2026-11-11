<?php

namespace Core\Navigation;

/**
 * Validateur de politiques d'accès. 
 * Vérifie que les règles déclaratives sont conformes et sécurisées.
 */
class PolicyValidator
{
    private static array $allowedOperators = ['=', '!=', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE', 'contains', 'exists'];

    /**
     * Valide un ensemble de conditions.
     * @throws \InvalidArgumentException si une règle est malformée.
     */
    public static function validate(array $policies): void
    {
        foreach ($policies as $index => $policy) {
            self::validateItem($policy, "policy[{$index}]");
        }
    }

    private static function validateItem(array $policy, string $path): void
    {
        if (isset($policy['logic'])) {
            $conditions = $policy['conditions'] ?? [];
            if (!is_array($conditions)) {
                throw new \InvalidArgumentException("{$path}: 'conditions' doit être un tableau");
            }
            foreach ($conditions as $idx => $cond) {
                self::validateItem($cond, "{$path}.conditions[{$idx}]");
            }
            return;
        }

        $type = $policy['type'] ?? ($policy['provider'] ?? null);
        if (!$type) {
            throw new \InvalidArgumentException("{$path}: Type de condition manquant");
        }

        switch ($type) {
            case 'auth':
                if (!isset($policy['value']) || !is_bool($policy['value'])) {
                    throw new \InvalidArgumentException("{$path} (auth): 'value' doit être un booléen");
                }
                break;

            case 'role':
                if (!isset($policy['value']) || !is_string($policy['value'])) {
                    throw new \InvalidArgumentException("{$path} (role): 'value' doit être une chaîne (ex: 'admin')");
                }
                break;

            case 'sql':
                if (empty($policy['table'])) {
                    throw new \InvalidArgumentException("{$path} (sql): 'table' manquante");
                }
                if (isset($policy['val_operator']) && !in_array($policy['val_operator'], self::$allowedOperators)) {
                    throw new \InvalidArgumentException("{$path} (sql): Opérateur invalide '{$policy['val_operator']}'");
                }
                break;

            case 'json':
                if (empty($policy['path'])) {
                    throw new \InvalidArgumentException("{$path} (json): 'path' (clé JSON) manquante");
                }
                break;
        }
    }
}
