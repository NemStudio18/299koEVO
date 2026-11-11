<?php

namespace Core\Navigation\Providers;

use Core\Navigation\Interfaces\ConditionProviderInterface;
use Core\Navigation\ConditionContext;
use Core\Core;
use Core\Database;

/**
 * Fournisseur SQL pour les conditions de navigation.
 * Permet de vérifier l'existence de données en base (ex: droits de propriété).
 */
class SqlProvider implements ConditionProviderInterface
{
    public function getId(): string
    {
        return 'sql';
    }

    /**
     * Pré-charge les données pour une liste de ressources (N+1 prevention).
     * @param array $resources Liste de ressources
     * @param ConditionContext $ctx Contexte global
     */
    public function batchPrefetch(array $resources, ConditionContext $ctx): void
    {
        $groups = [];
        foreach ($resources as $res) {
            $policies = $res->getPolicy();
            foreach ($policies as $p) {
                if (($p['type'] ?? '') === 'sql') {
                    // On groupe par table + colonnes + opérateur (hors valeur de l'ID)
                    $groupKey = md5(json_encode([
                        'table' => $p['table'] ?? '',
                        'where' => $p['where'] ?? [],
                        'column' => $p['column'] ?? '',
                        'val_op' => $p['val_operator'] ?? '='
                    ]));
                    $groups[$groupKey]['config'] = $p;
                    $metadata = $res instanceof \Core\Navigation\Interfaces\MetadataAwareInterface 
                        ? $res->getMetadata() 
                        : null;
                    if ($metadata && isset($metadata['resource_id'])) {
                        $groups[$groupKey]['ids'][] = $metadata['resource_id'];
                    }
                }
            }
        }

        foreach ($groups as $group) {
            $this->executeBatch($group['config'], $group['ids'], $ctx);
        }
    }

    /**
     * Exécute une requête SQL groupée et injecte les résultats dans le cache.
     */
    private function executeBatch(array $config, array $ids, ConditionContext $ctx): void
    {
        if (empty($ids)) return;

        $table = $config['table'] ?? null;
        if (!$table || !$this->isTableAllowed($table)) return;

        $db = Database::getInstance();
        $params = [':user_id' => $ctx->getUserId()];
        $whereSql = [];

        // On cherche quelle colonne contient ":resource_id"
        $resourceIdColumn = null;
        
        // Cas 1 : Format simplifié
        if (isset($config['column']) && ($config['value'] ?? '') === ':resource_id') {
            $resourceIdColumn = $config['column'];
        }
        
        // Cas 2 : Format complexe
        $where = $config['where'] ?? [];
        foreach ($where as $k => $v) {
            $val = is_array($v) ? ($v['val'] ?? null) : $v;
            $col = is_array($v) ? ($v['col'] ?? $k) : $k;
            if ($val === ':resource_id') {
                $resourceIdColumn = $col;
            } else {
                // Ajouter les autres filtres (ex: status = 'approved')
                if (is_array($v)) {
                    $op = $v['op'] ?? '=';
                    $finalVal = $v['val'] ?? null;
                } else {
                    $op = '=';
                    $finalVal = $v;
                }
                
                // Résolution simple des variables (seulement :user_id supporté en batch pour l'instant)
                if ($finalVal === ':user_id') $finalVal = $ctx->getUserId();
                
                $paramName = ':' . $col . '_' . uniqid();
                $whereSql[] = "`{$col}` {$op} {$paramName}";
                $params[$paramName] = $finalVal;
            }
        }

        if (!$resourceIdColumn) return;

        // Construction du IN (...)
        $placeholders = [];
        foreach ($ids as $index => $id) {
            $pName = ':bid_' . $index;
            $placeholders[] = $pName;
            $params[$pName] = $id;
        }
        $whereSql[] = "`{$resourceIdColumn}` IN (" . implode(',', $placeholders) . ")";

        try {
            $sql = "SELECT DISTINCT `{$resourceIdColumn}` FROM `{$table}` WHERE " . implode(' AND ', $whereSql);
            $results = $db->query($sql, $params);
            
            $foundIds = [];
            foreach ($results as $row) {
                $foundIds[] = (string)array_values($row)[0];
            }

            // Injection forcée dans le cache pour chaque ID
            foreach ($ids as $id) {
                $scopedMetadata = ['resource_id' => $id, 'resource_type' => $config['resource_type'] ?? 'unknown'];
                $scopedCtx = new ConditionContext($ctx->getUser(), $scopedMetadata);
                
                // On simule la clé du cache que canAccess cherchera
                // Cache ID: resourceMetadata hash + supplierId + supplierKey
                // Note: getData utilise 'evaluator' et 'access_policy_result:...'
                // Mais ici on veut cacher le résultat du SqlProvider lui-même pour éviter l'exécution de evaluate()
                
                $isAllowed = in_array((string)$id, $foundIds);
                // Le SqlProvider n'utilise pas getData() de manière standard, il est appelé par AccessPolicyEvaluator
                // MAIS on peut injecter le résultat au niveau de l'Evaluator
                $scopedCtx->setCache('evaluator', 'access_policy_result:' . $id, $isAllowed);
            }
        } catch (\Throwable $e) {
            Core::getInstance()->getLogger()->error("SqlProvider Batch Error: " . $e->getMessage());
        }
    }

    private ?string $lastError = null;

    public function evaluate(array $config, ConditionContext $ctx): bool
    {
        $this->lastError = null;
        $table = $config['table'] ?? null;
        if (!$table) {
            $this->lastError = "Table non spécifiée";
            return false;
        }

        if (!$this->isTableAllowed($table)) {
            $this->lastError = "Table '{$table}' non autorisée par la politique de sécurité";
            Core::getInstance()->getLogger()->warning("SqlProvider: " . $this->lastError);
            return false;
        }

        $db = Database::getInstance();
        
        $whereElements = [];
        
        // 1. Format simplifié via l'interface d'admin
        if (isset($config['column']) && isset($config['value'])) {
            $whereElements[] = [
                'col' => $config['column'],
                'op' => $config['val_operator'] ?? '=',
                'val' => $config['value']
            ];
        }

        // 2. Format complexe via JSON manuel (compatibilité)
        $where = $config['where'] ?? [];
        if (is_array($where) && !empty($where)) {
            foreach ($where as $k => $v) {
                if (is_array($v)) {
                    $whereElements[] = $v;
                } else {
                    $whereElements[] = ['col' => $k, 'op' => '=', 'val' => $v];
                }
            }
        }

        $params = [];
        $whereSql = [];

        foreach ($whereElements as $item) {
            if (!isset($item['col'])) continue;

            $col = $item['col'];
            $val = $item['val'] ?? null;
            $op = $item['op'] ?? '=';

            // Sécurité : Seules les colonnes alphanumériques
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $col)) continue;
            
            // Sécurité : Opérateurs autorisés
            if (!in_array($op, ['=', '!=', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE'])) {
                $op = '=';
            }

            // Remplacement des variables contextuelles
            if (is_string($val) && strpos($val, ':') === 0) {
                switch ($val) {
                    case ':user_id':    $val = $ctx->getUserId(); break;
                    case ':user_role':  $val = $ctx->getRoles()[0] ?? 'guest'; break;
                    case ':user_email': $val = $ctx->getUser() ? $ctx->getUser()->getEmail() : null; break;
                    case ':user_login': $val = $ctx->getUser() ? $ctx->getUser()->username() : null; break;
                    case ':resource_id': 
                        $val = $ctx->getResourceMetadata()['resource_id'] ?? null; 
                        break;
                    case ':resource_type': 
                        $val = $ctx->getResourceMetadata()['resource_type'] ?? null; 
                        break;
                    case ':null':       $val = null; break;
                }
                
                if ($val === null && in_array($val, [':user_id', ':user_email', ':user_login', ':resource_id'])) {
                    $this->lastError = "Variable {$val} non disponible dans le contexte actuel";
                    return false;
                }
            }

            // Gestion spéciale du NULL pour les comparaisons SQL
            if ($val === null || $val === ':null') {
                if ($op === '=') {
                    $whereSql[] = "`{$col}` IS NULL";
                } elseif ($op === '!=') {
                    $whereSql[] = "`{$col}` IS NOT NULL";
                } else {
                    $this->lastError = "Opérateur '{$op}' incompatible avec une valeur NULL (utilisez = ou !=)";
                    return false;
                }
            } else {
                $paramName = ':' . $col . '_' . uniqid();
                $finalVal = $val;
                if ($op === 'LIKE' || $op === 'NOT LIKE') {
                    $finalVal = "%{$val}%";
                }
                $whereSql[] = "`{$col}` {$op} {$paramName}";
                $params[$paramName] = $finalVal;
            }
        }

        try {
            $sql = "SELECT COUNT(*) FROM `{$table}`";
            if (!empty($whereSql)) {
                $sql .= " WHERE " . implode(' AND ', $whereSql);
            }

            $results = $db->query($sql, $params);
            $count = (int)($results[0]['COUNT(*)'] ?? $results[0][0] ?? 0);
            $opRoot = $config['operator'] ?? 'exists';

            if ($opRoot === 'exists') {
                $res = $count > 0;
                if (!$res) {
                    $details = !empty($whereSql) ? " avec les filtres demandés" : "";
                    $this->lastError = "Aucun enregistrement trouvé dans '{$table}'{$details}";
                }
                return $res;
            }

            $threshold = (int)($config['threshold'] ?? 0);
            $res = false;
            switch ($opRoot) {
                case '>': $res = $count > $threshold; break;
                case '>=': $res = $count >= $threshold; break;
                case '<': $res = $count < $threshold; break;
                case '<=': $res = $count <= $threshold; break;
                case '=': $res = $count == $threshold; break;
                default: $res = $count > 0;
            }
            if (!$res) $this->lastError = "Condition '{$opRoot} {$threshold}' non remplie (Compte actuel: {$count})";
            return $res;
        } catch (\Throwable $e) {
            $this->lastError = "Erreur SQL : " . $e->getMessage();
            throw $e;
        }
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    private function isTableAllowed(string $table): bool
    {
        $whitelist = Core::getInstance()->getConfigVal('auth_sql_whitelist');
        if (!is_array($whitelist)) {
            $whitelist = ['users', 'pages', 'comments'];
        }

        if (in_array($table, $whitelist)) {
            return true;
        }

        $allowedPrefixes = ['poi_', 'bugtracker_', 'claim_', 'forum_', 'subscription_'];
        foreach ($allowedPrefixes as $prefix) {
            if (strpos($table, $prefix) === 0) return true;
        }

        return false;
    }

    public function getTables(): array
    {
        $db = Database::getInstance();
        $tables = [];
        try {
            // On essaie de détecter le driver via la config de Database
            // Note: Database::getConfigVal n'existe pas, il faut passer par l'instance et ses configs internes ou faire une simple requête
            $res = $db->query("SHOW TABLES"); 
            foreach ($res as $row) {
                $name = array_values($row)[0];
                if ($this->isTableAllowed($name)) {
                    $tables[$name] = $name;
                }
            }
        } catch (\Throwable $e) {
            // Fallback SQLite si MySQL échoue
            try {
                $res = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
                foreach ($res as $row) {
                    $name = $row['name'] ?? null;
                    if ($name && $this->isTableAllowed($name)) $tables[$name] = $name;
                }
            } catch (\Throwable $e2) {}
        }
        asort($tables);
        return $tables;
    }

    public function getColumns(string $table): array
    {
        if (!$this->isTableAllowed($table)) return [];
        $db = Database::getInstance();
        $cols = [];
        try {
            $res = $db->query("DESCRIBE `{$table}`");
            foreach ($res as $row) {
                $name = $row['Field'] ?? array_values($row)[0] ?? null;
                if ($name) $cols[$name] = $name;
            }
        } catch (\Throwable $e) {
            try {
                // Fallback SQLite
                $res = $db->query("PRAGMA table_info(`{$table}`)");
                foreach ($res as $row) {
                    $name = $row['name'] ?? null;
                    if ($name) $cols[$name] = $name;
                }
            } catch (\Throwable $e2) {}
        }
        asort($cols);
        return $cols;
    }

    public function getMetadata(): array
    {
        return [
            'label' => 'Base de données (SQL)',
            'description' => 'Vérifie si une ligne existe en base. Pratique pour les droits de propriété.',
            'fields' => [
                'table' => [
                    'type' => 'select', 
                    'label' => '1. Table', 
                    'options' => $this->getTables(),
                    'dynamic_reload' => true // Signal pour l'UI qu'il faut recharger les métadonnées si cette valeur change
                ],
                'column' => [
                    'type' => 'select', 
                    'label' => '2. Colonne', 
                    'options' => [], // Sera rempli par l'UI via le rechargement
                    'dependsOn' => 'table'
                ],
                'val_operator' => [
                    'type' => 'select',
                    'label' => '3. Comparaison',
                    'options' => [
                        '=' => 'égal à',
                        '!=' => 'différent de',
                        '>' => 'supérieur à',
                        'LIKE' => 'contient'
                    ]
                ],
                'value' => [
                    'type' => 'suggest', 
                    'label' => '4. Valeur', 
                    'placeholder' => 'ex: :user_id',
                    'options' => [
                        ':user_id' => 'ID utilisateur actuel',
                        ':user_role' => 'Rôle utilisateur',
                        '1' => '1 (Vrai / Actif)',
                        '0' => '0 (Faux / Inactif)',
                        'pending' => 'En attente (pending)',
                        'approved' => 'Approuvé (approved)'
                    ]
                ],
                'operator' => [
                    'type' => 'select',
                    'label' => 'Résultat attendu',
                    'options' => [
                        'exists' => 'Au moins une ligne correspondante',
                        '=' => 'Nombre de lignes égal à...',
                    ]
                ],
                'threshold' => ['type' => 'text', 'label' => 'Nombre exact (si utile)', 'placeholder' => '0']
            ]
        ];
    }

    public function getCost(): int
    {
        return 10;
    }
}
