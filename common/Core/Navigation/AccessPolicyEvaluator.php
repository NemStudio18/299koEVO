<?php

namespace Core\Navigation;

use Core\Navigation\Interfaces\ResourceInterface;
use Core\Navigation\Interfaces\ConditionProviderInterface;
use Core\Core;

/**
 * Moteur d'évaluation des politiques d'accès.
 * Point d'entrée unique pour la Navigation (Visibilité) et le Contrôleur (Autorisation).
 */
class AccessPolicyEvaluator
{
    private static ?AccessPolicyEvaluator $instance = null;
    private ConditionRegistry $registry;
    private array $policyRegistry = [];
    private bool $shadowMode = false;
    private array $trace = [];

    private function __construct()
    {
        $this->registry = ConditionRegistry::getInstance();
        $this->loadPolicies();
    }

    public static function getInstance(): AccessPolicyEvaluator
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Charge les politiques depuis le coeur et les plugins actifs.
     */
    public function loadPolicies(): void
    {
        // On évite les dépendances cycliques si Core n'est pas prêt
        if (!class_exists('\\Core\\Core')) return;

        $this->policyRegistry = [];

        // 1. Charge les politiques du coeur
        $coreFile = DATA_CORE . 'policies.json';
        if (is_file($coreFile)) {
            $corePolicies = json_decode(file_get_contents($coreFile), true);
            if (is_array($corePolicies)) {
                $this->policyRegistry = array_merge($this->policyRegistry, $corePolicies);
            }
        }

        // 2. Charge les politiques des plugins actifs
        if (class_exists('\\Core\\Plugin\\PluginsManager')) {
            $plugins = \Core\Plugin\PluginsManager::getInstance()->listPlugins();
            foreach ($plugins as $plugin) {
                $pluginPolicyFile = PLUGINS . $plugin->getName() . '/param/policies.json';
                if (is_file($pluginPolicyFile)) {
                    $pluginPolicies = json_decode(file_get_contents($pluginPolicyFile), true);
                    if (is_array($pluginPolicies)) {
                        $this->policyRegistry = array_merge($this->policyRegistry, $pluginPolicies);
                    }
                }
            }
        }
    }

    /**
     * Vérifie si l'item doit être affiché dans la navigation.
     * Alias de evaluateResource pour la clarté sémantique dans Navigation.
     */
    public function canView(ResourceInterface $resource, ConditionContext $ctx): bool
    {
        return $this->canAccess($resource, $ctx);
    }

    /**
     * Vérifie si l'utilisateur a le droit d'accéder à la ressource.
     * Injecte les métadonnées de la ressource dans le contexte pour l'évaluation.
     */
    public function canAccess(ResourceInterface $resource, ConditionContext $ctx): bool
    {
        $resourceId = $resource->getResourceId();
        $metadata = $resource instanceof \Core\Navigation\Interfaces\MetadataAwareInterface 
            ? $resource->getMetadata() 
            : ['resource_id' => $resourceId];

        // On clone le contexte pour y injecter les métadonnées de cette ressource spécifique
        // sans polluer le contexte global de la requête.
        $scopedCtx = new ConditionContext($ctx->getUser(), $metadata);

        $cacheKey = 'access_policy_result:' . $resourceId;

        // 1. Vérification du cache persistant (Phase 7)
        $persistentCache = PolicyCache::getInstance();
        $cachedResult = $persistentCache->get($scopedCtx->getUserContextHash() . ':' . $cacheKey);
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        return $scopedCtx->getData('evaluator', $cacheKey, function() use ($resource, $scopedCtx, $resourceId, $persistentCache) {
            // 2. Règle d'or : On vérifie d'abord le parent (Héritage récursif)
            $parent = $resource->getResourceParent();
            if ($parent !== null) {
                if (!$this->canAccess($parent, $scopedCtx)) {
                    return false; // Parent bloqué => Enfant bloqué (Fail-closed)
                }
            }

            // 3. Évaluation des règles de la ressource elle-même
            $result = $this->evaluateResourceInternals($resource, $scopedCtx);

            // --- SHADOW MODE : Comparaison avec la logique legacy (Phase 9) ---
            if ($this->shadowMode) {
                $legacyResult = $this->evaluateLegacyFlags($resource, $scopedCtx);
                if ($result !== $legacyResult) {
                    Core::getInstance()->getLogger()->info(sprintf(
                        "[Shadow Discrepancy] Resource: %s | Result: %s | Legacy: %s | User: %s",
                        $resourceId,
                        $result ? 'ALLOW' : 'DENY',
                        $legacyResult ? 'ALLOW' : 'DENY',
                        $scopedCtx->getUserId()
                    ));
                }
            }
            // ------------------------------------------------------------------

            // 4. Mise en cache persistante
            $persistentCache->set($scopedCtx->getUserContextHash() . ':access_policy_result:' . $resourceId, $result);

            // 5. Logging centralisé en cas de refus (Audit Log)
            if (!$result) {
                $user = $scopedCtx->getUser();
                Core::getInstance()->getLogger()->warning(sprintf(
                    "[Access Denied] Resource: %s | User: %s | Roles: %s | Meta: %s",
                    $resourceId,
                    $user ? $user->getEmail() : 'Guest',
                    implode(',', $scopedCtx->getRoles()),
                    json_encode($scopedCtx->getResourceMetadata())
                ));
            }

            return $result;
        });
    }

    /**
     * Pré-charge les permissions pour une liste de ressources (Batching N+1).
     * @param ResourceInterface[] $resources
     * @param ConditionContext $ctx
     */
    public function prefetch(array $resources, ConditionContext $ctx): void
    {
        // Regrouper les ressources par type de provider pour optimiser
        // Actuellement focus sur SqlProvider qui est le plus coûteux
        $sqlResources = [];
        foreach ($resources as $res) {
            foreach ($res->getPolicy() as $policy) {
                if (($policy['type'] ?? '') === 'sql') {
                    $sqlResources[] = $res;
                    break;
                }
            }
        }

        if (!empty($sqlResources)) {
            $sqlProvider = $this->registry->getProvider('sql');
            if ($sqlProvider instanceof \Core\Navigation\Providers\SqlProvider) {
                // Le SqlProvider doit implémenter une méthode batchPrefetch
                $sqlProvider->batchPrefetch($sqlResources, $ctx);
            }
        }
    }

    /**
     * Helper pour vérifier rapidement si l'utilisateur est administrateur via la politique globale.
     * Centralise la définition de ce qu'est un "admin" pour le système.
     *
     * @param ConditionContext $ctx
     * @return bool
     */
    public function isAdmin(ConditionContext $ctx): bool
    {
        return $this->canAccess(new GenericResource('system:admin-access', [], ['ADM' => true]), $ctx);
    }

    /**
     * Évaluation pure des règles propres à une ressource (Flags + Conditions).
     */
    private function evaluateResourceInternals(ResourceInterface $resource, ConditionContext $ctx): bool
    {
        $resourceId = $resource->getResourceId();
        // 1. Compilation des conditions (Modernes + Legacy Flags)
        $conditions = $resource->getPolicy();
        $flags = $resource->getVisibilityFlags();
        $logic = 'AND';

        // Si la ressource n'a pas de politique propre, on cherche dans le registre global (Modular Policies)
        if (empty($conditions)) {
            if (isset($this->policyRegistry[$resourceId])) {
                $policyDef = $this->policyRegistry[$resourceId];
                // Si c'est une définition complète avec logique et conditions
                if (isset($policyDef['conditions'])) {
                    $logic = $policyDef['logic'] ?? 'AND';
                    $conditions = $policyDef['conditions'];
                } else {
                    // Si c'est juste une liste brute de conditions (AND par défaut)
                    $conditions = $policyDef;
                }
            }
        }

        // Traduction des flags legacy en conditions ACL
        $extraConditions = [];
        if ($flags['ADM'] ?? false) {
            $extraConditions[] = ['type' => 'role', 'value' => 'admin'];
        }
        if ($flags['MEM'] ?? false) {
            $extraConditions[] = ['type' => 'auth', 'value' => true];
        }

        if (!empty($extraConditions)) {
            if (empty($conditions)) {
                $conditions = $extraConditions;
            } else {
                // On encapsule la politique existante et on ajoute les flags en AND
                $conditions = [
                    [
                        'logic' => $logic,
                        'conditions' => $conditions
                    ],
                    [
                        'logic' => 'AND',
                        'conditions' => $extraConditions
                    ]
                ];
                $logic = 'AND';
            }
        }

        // Si aucune condition d'accès restrictive, on vérifie le flag Public
        if (empty($conditions)) {
            return $flags['PUB'] ?? true;
        }

        // 2. Évaluation du groupe
        return $this->evaluateGroup([
            'logic' => $logic,
            'conditions' => $conditions
        ], $ctx);
    }

    /**
     * @deprecated Utilisez evaluateResource() ou canAccess()
     */
    private function evaluatePageItem(ResourceInterface $item, ConditionContext $ctx, string $scope): bool
    {
        return $this->canAccess($item, $ctx);
    }

    /**
     * Évalue un groupe de conditions (AND/OR).
     */
    public function evaluateGroup(array $group, ConditionContext $ctx): bool
    {
        $this->trace = []; // Reset trace at entry point if needed, but normally handled by explain API
        $logic = strtoupper($group['logic'] ?? 'AND');
        $conditions = $group['conditions'] ?? [];

        if (empty($conditions)) {
            return true;
        }

        // Trier les conditions par coût
        usort($conditions, function($a, $b) {
            $costA = $this->getConditionCost($a);
            $costB = $this->getConditionCost($b);
            return $costA <=> $costB;
        });

        if ($logic === 'OR') {
            foreach ($conditions as $cond) {
                if ($this->evaluateRecursive($cond, $ctx)) {
                    return true;
                }
            }
            return false;
        } else { // AND par défaut
            foreach ($conditions as $cond) {
                if (!$this->evaluateRecursive($cond, $ctx)) {
                    return false;
                }
            }
            return true;
        }
    }

    /**
     * Évaluation récursive (Gère les groupes imbriqués et les feuilles).
     */
    private function evaluateRecursive(array $node, ConditionContext $ctx): bool
    {
        if (isset($node['logic'])) {
            return $this->evaluateGroup($node, $ctx);
        }

        $type = $node['provider'] ?? ($node['type'] ?? null);
        if (!$type) {
            return false;
        }

        $provider = $this->registry->getProvider($type);
        if (!$provider) {
            return false;
        }

        try {
            $result = $provider->evaluate($node['config'] ?? $node, $ctx);
            
            return $result;
        } catch (\Throwable $e) {
            if (Core::getInstance()->getConfigVal('debug')) {
                throw $e;
            }
            return false;
        }
    }

    public function getTrace(): array
    {
        return $this->trace;
    }

    public function getPolicyRegistry(): array
    {
        return $this->policyRegistry;
    }

    public function clearTrace(): void
    {
        $this->trace = [];
    }

    /**
     * Estime le coût d'une condition.
     */
    private function getConditionCost(array $node): int
    {
        if (isset($node['logic'])) {
            return 1;
        }

        $type = $node['provider'] ?? ($node['type'] ?? null);
        $provider = $this->registry->getProvider($type);
        
        return $provider ? $provider->getCost() : 100;
    }

    /**
     * Active/Désactive le mode fantôme (Shadow Mode).
     */
    public function setShadowMode(bool $enabled): void
    {
        $this->shadowMode = $enabled;
    }

    /**
     * Évalue les flags de visibilité legacy pour comparaison en Shadow Mode.
     */
    private function evaluateLegacyFlags(ResourceInterface $resource, ConditionContext $ctx): bool
    {
        $flags = $resource->getVisibilityFlags();
        
        // 1. Admin
        if (($flags['ADM'] ?? false) && $this->isAdmin($ctx)) return true;
        
        // 2. Member
        if (($flags['MEM'] ?? false) && $ctx->getUserId() > 0) return true;
        
        // 3. Public
        if ($flags['PUB'] ?? false) return true;
        
        return false;
    }
}
