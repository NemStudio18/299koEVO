<?php

namespace Core\Navigation;

/**
 * Arbre hiérarchique de navigation
 * 
 * Gère la structure récursive et les opérations sur l'arbre
 */
defined('ROOT') or exit('Access denied!');

class NavigationTree
{
    /**
     * Items racine (sans parent)
     * @var NavigationItem[]
     */
    private array $rootItems;

    /**
     * Index par ID pour accès rapide
     * @var NavigationItem[]
     */
    private array $itemsById;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->rootItems = [];
        $this->itemsById = [];
    }

    /**
     * Ajoute un item à l'arbre
     */
    public function addItem(NavigationItem $item): void
    {
        // Vérifier l'unicité de l'ID
        if (isset($this->itemsById[$item->getId()])) {
            throw new \RuntimeException("NavigationItem with ID '{$item->getId()}' already exists");
        }

        $this->itemsById[$item->getId()] = $item;

        // Si pas de parent, ajouter aux racines
        if ($item->getParentId() === null) {
            $this->rootItems[] = $item;
        } else {
            // Sinon, trouver le parent et l'ajouter comme enfant
            $parent = $this->getItemById($item->getParentId());
            if ($parent === null) {
                // Parent pas encore ajouté, ajouter temporairement aux racines
                // Le parent sera attaché plus tard lors de build()
                $this->rootItems[] = $item;
            } else {
                $parent->addChild($item);
            }
        }
    }

    /**
     * Récupère un item par son ID
     */
    public function getItemById(string $id): ?NavigationItem
    {
        return $this->itemsById[$id] ?? null;
    }

    /**
     * Construit l'arbre hiérarchique complet
     * Réorganise les items selon leurs relations parent/enfant
     */
    public function build(): void
    {
        // Nettoyer les enfants existants pour éviter les doublons si build() est appelé plusieurs fois
        foreach ($this->itemsById as $item) {
            $item->clearChildren();
        }
        
        // Réorganiser les items selon leurs parents
        $newRootItems = [];
        $processed = [];

        foreach ($this->itemsById as $item) {
            if ($item->getParentId() === null) {
                $newRootItems[] = $item;
                $processed[$item->getId()] = true;
            }
        }

        // Attacher les enfants à leurs parents
        $maxIterations = 100; // Protection contre boucles infinies
        $iteration = 0;

        while ($iteration < $maxIterations) {
            $attached = false;
            foreach ($this->itemsById as $item) {
                if (isset($processed[$item->getId()])) {
                    continue;
                }

                $parentId = $item->getParentId();
                if ($parentId !== null) {
                    $parent = $this->getItemById($parentId);
                    if ($parent !== null && isset($processed[$parentId])) {
                        // Retirer de rootItems si présent
                        $this->rootItems = array_filter($this->rootItems, function($rootItem) use ($item) {
                            return $rootItem->getId() !== $item->getId();
                        });
                        $this->rootItems = array_values($this->rootItems);

                        $parent->addChild($item);
                        $processed[$item->getId()] = true;
                        $attached = true;
                    }
                }
            }

            if (!$attached) {
                break;
            }
            $iteration++;
        }

        // Items orphelins (parent inexistant) restent en racine
        foreach ($this->itemsById as $item) {
            if (!isset($processed[$item->getId()]) && $item->getParentId() !== null) {
                $newRootItems[] = $item;
            }
        }

        $this->rootItems = $newRootItems;

        // Trier récursivement
        $this->sortRecursive($this->rootItems);
    }

    /**
     * Trie récursivement l'arbre par position puis par groupe
     * 
     * @param array $items (passage par référence)
     */
    private function sortRecursive(array &$items): void
    {
        usort($items, function (NavigationItem $a, NavigationItem $b) {
            // 1. Tri par position
            if ($a->getPosition() !== $b->getPosition()) {
                return $a->getPosition() <=> $b->getPosition();
            }
            
            // 2. Tri par groupe (si présent dans metadata)
            $groupA = $a->getMetadata()['group'] ?? '';
            $groupB = $b->getMetadata()['group'] ?? '';
            
            if ($groupA !== $groupB) {
                return strcmp($groupA, $groupB);
            }
            
            // 3. Tri par label (fallback stable)
            return strcmp($a->getLabel(), $b->getLabel());
        });

        foreach ($items as $item) {
            $children = $item->getChildren();
            if (!empty($children)) {
                $this->sortRecursive($children);
                $item->setChildren($children);
            }
        }
    }

    /**
     * Supprime les parents qui n'ont plus d'enfants visibles (et qui ne sont pas des liens cliquables)
     */
    public function pruneEmptyParents(): void
    {
        $this->rootItems = $this->pruneEmptyParentsRecursive($this->rootItems);
    }

    private function pruneEmptyParentsRecursive(array $items): array
    {
        $pruned = [];

        foreach ($items as $item) {
            // Traiter les enfants d'abord (post-order)
            $children = $item->getChildren();
            if (!empty($children)) {
                $children = $this->pruneEmptyParentsRecursive($children);
                $item->setChildren($children);
            }
            
            // Si l'item a des enfants, on le garde
            if (!empty($item->getChildren())) {
                $pruned[] = $item;
                continue;
            }
            
            // Si pas d'enfants, on regarde si c'est un container vide (target # ou vide)
            $target = $item->getTarget();
            if ($target === '#' || $target === '') {
                // C'est un container vide -> on le supprime
                continue;
            }
            
            // C'est un lien feuille -> on le garde
            $pruned[] = $item;
        }

        return $pruned;
    }

    /**
     * Évalue les conditions dynamiques pour chaque item
     * Si les conditions ne sont pas remplies, l'item est désactivé (enabled = false)
     * 
     * @param ConditionContext $ctx
     */
    public function evaluateConditions(ConditionContext $ctx): void
    {
        $this->evaluateConditionsRecursive($this->rootItems, $ctx);
    }

    private function evaluateConditionsRecursive(array $items, ConditionContext $ctx): void
    {
        $evaluator = AccessPolicyEvaluator::getInstance();
        foreach ($items as $item) {
            // Utiliser la nouvelle méthode isVisible qui gère tout (legacy + modern)
            if (!$item->isVisible($ctx)) {
                if ($item->getNavContext() !== 'auto') {
                    $item->setEnabled(false);
                }
            }
            
            // Récursion sur les enfants
            $children = $item->getChildren();
            if (!empty($children)) {
                $this->evaluateConditionsRecursive($children, $ctx);
            }
        }
    }

    /**
     * Filtre l'arbre pour ne garder que les items d'un canal donné (main, avatar, footer)
     * Gère aussi le mode 'auto' qui bascule entre main et avatar selon les conditions
     * Retourne une NOUVELLE instance de NavigationTree avec des items clonés
     * 
     * @param string $channel Canal demandé (main, avatar, footer)
     * @param ConditionContext $ctx
     * @return self Nouvel arbre filtré
     */
    public function filterByChannel(string $channel, ConditionContext $ctx): self
    {
        $newTree = new self();
        
        $filteredRootItems = $this->filterByChannelRecursive($this->rootItems, $channel, $ctx);
        
        foreach ($filteredRootItems as $item) {
            $newTree->addItem($item);
        }
        
        return $newTree;
    }

    private function filterByChannelRecursive(array $items, string $channel, ConditionContext $ctx): array
    {
        $filtered = [];

        foreach ($items as $item) {
            // Skip items disabled by evaluateConditions (non-auto items that failed conditions)
            if (!$item->isEnabled()) {
                continue;
            }

            $itemContext = $item->getNavContext();
            $itemContexts = array_map('trim', explode(',', $itemContext));
            
            // Gestion du mode 'auto' (bascule main <-> avatar)
            if (in_array('auto', $itemContexts)) {
                // Si conditions remplies -> avatar
                // Sinon -> main
                $conditionsMet = $item->isVisible($ctx);
                $effectiveChannel = $conditionsMet ? 'avatar' : 'main';
                
                if ($effectiveChannel !== $channel) {
                    continue;
                }
            } else {
                // Mode normal : stricte égalité (l'item doit être dans le canal demandé)
                if (!in_array($channel, $itemContexts)) {
                    continue;
                }
            }
            
            // CLONAGE : On clone l'item pour ne pas modifier l'original (car on va changer ses enfants)
            $clonedItem = clone $item;

            // Filtrer les enfants
            $children = $item->getChildren();
            if (!empty($children)) {
                $filteredChildren = $this->filterByChannelRecursive($children, $channel, $ctx);
                $clonedItem->setChildren($filteredChildren);
            } else {
                $clonedItem->setChildren([]);
            }

            $filtered[] = $clonedItem;
        }

        return $filtered;
    }


    /**
     * Applique les positions relatives (after/before)
     */
    public function applyRelativePositions(): void
    {
        foreach ($this->itemsById as $item) {
            $relative = $item->getPositionRelative();
            if ($relative === null) {
                continue;
            }

            // Trouver le parent ou la racine
            $siblings = $item->getParentId() === null 
                ? $this->rootItems 
                : ($this->getItemById($item->getParentId())?->getChildren() ?? []);

            if (empty($siblings)) {
                continue;
            }

            // Trouver l'index de référence
            $refId = $relative['after'] ?? $relative['before'] ?? null;
            if ($refId === null) {
                continue;
            }

            $refIndex = null;
            foreach ($siblings as $index => $sibling) {
                if ($sibling->getId() === $refId) {
                    $refIndex = $index;
                    break;
                }
            }

            if ($refIndex === null) {
                continue;
            }

            // Retirer l'item de sa position actuelle
            $siblings = array_filter($siblings, function($sibling) use ($item) {
                return $sibling->getId() !== $item->getId();
            });
            $siblings = array_values($siblings);

            // Insérer à la bonne position
            if (isset($relative['after'])) {
                array_splice($siblings, $refIndex + 1, 0, [$item]);
            } else {
                array_splice($siblings, $refIndex, 0, [$item]);
            }

            // Réattacher aux parents
            if ($item->getParentId() === null) {
                $this->rootItems = $siblings;
            } else {
                $parent = $this->getItemById($item->getParentId());
                if ($parent !== null) {
                    $parent->setChildren($siblings);
                }
            }
        }
    }

    /**
     * Filtre l'arbre selon la visibilité
     */
    public function filterVisible(ConditionContext $ctx): void
    {
        $this->rootItems = $this->filterRecursive(
            $this->rootItems,
            $ctx
        );
    }

    /**
     * Filtre récursif
     */
    private function filterRecursive(array $items, ConditionContext $ctx): array
    {
        $filtered = [];

        foreach ($items as $item) {
            if (!$item->isVisible($ctx)) {
                continue;
            }

            // Filtrer les enfants
            $children = $item->getChildren();
            if (!empty($children)) {
                $filteredChildren = $this->filterRecursive($children, $ctx);
                $item->setChildren($filteredChildren);
            }

            $filtered[] = $item;
        }

        return $filtered;
    }

    /**
     * Nettoie les items désactivés ou orphelins
     */
    public function cleanup(?array $activePluginSources = null): void
    {
        // Si une liste de sources actives est fournie, désactiver les items des plugins inactifs
        if ($activePluginSources !== null) {
            foreach ($this->itemsById as $item) {
                if (strpos($item->getSource(), 'plugin:') === 0) {
                    $pluginName = substr($item->getSource(), 7);
                    if (!in_array($pluginName, $activePluginSources)) {
                        $item->setEnabled(false);
                    }
                }
            }
        }

        // Supprimer les items désactivés de l'arbre
        $this->rootItems = $this->cleanupRecursive($this->rootItems);
    }

    /**
     * Nettoie récursivement
     */
    private function cleanupRecursive(array $items): array
    {
        $cleaned = [];

        foreach ($items as $item) {
            if (!$item->isEnabled()) {
                continue;
            }

            // Nettoyer les enfants
            $children = $item->getChildren();
            if (!empty($children)) {
                $cleanedChildren = $this->cleanupRecursive($children);
                $item->setChildren($cleanedChildren);
            }

            $cleaned[] = $item;
        }

        return $cleaned;
    }

    /**
     * Récupère tous les items racine
     */
    public function getRootItems(): array
    {
        return $this->rootItems;
    }

    /**
     * Récupère tous les items (plat)
     */
    public function getAllItems(): array
    {
        return array_values($this->itemsById);
    }

    /**
     * Vide l'arbre
     */
    public function clear(): void
    {
        $this->rootItems = [];
        $this->itemsById = [];
    }

    /**
     * Exporte l'arbre en tableau pour le cache
     */
    public function toArray(): array
    {
        $items = [];
        foreach ($this->itemsById as $item) {
            $items[] = $item->toArray();
        }
        return [
            'items' => $items,
            'rootIds' => array_map(fn($item) => $item->getId(), $this->rootItems)
        ];
    }

    /**
     * Reconstruit l'arbre depuis un tableau (cache)
     */
    public static function fromArray(array $data): self
    {
        $tree = new self();
        $itemsData = $data['items'] ?? [];
        
        foreach ($itemsData as $itemData) {
            $tree->addItem(NavigationItem::fromArray($itemData));
        }
        
        $tree->build();
        return $tree;
    }
}

