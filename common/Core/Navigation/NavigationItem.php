<?php
namespace Core\Navigation;

use Core\Navigation\Interfaces\ResourceInterface;
use Core\Navigation\Interfaces\MetadataAwareInterface;
use Core\Page\Page;
use Core\Page\PageItem;

/**
 * Modèle de données pour un item de navigation
 */
class NavigationItem implements ResourceInterface, MetadataAwareInterface
{
    /**
     * ID unique avec namespace (ex: "core.home", "plugin.search.main")
     * @var string
     */
    private string $id;

    /**
     * Label affiché (sera échappé par le renderer)
     * @var string
     */
    private string $label;

    /**
     * URL cible
     * @var string
     */
    private string $target;

    /**
     * Attribut target HTML (_self, _blank, etc.)
     * @var string
     */
    private string $targetAttr;

    /**
     * Classes CSS additionnelles
     * @var string
     */
    private string $cssClass;

    /**
     * Icône FontAwesome (optionnel)
     * @var string|null
     */
    private ?string $icon;

    /**
     * Position pour le tri (poids numérique)
     * @var int
     */
    private int $position;

    /**
     * Position relative (avant/après un autre item)
     * @var array|null ['after' => 'core.home'] ou ['before' => 'core.about']
     */
    private ?array $positionRelative;

    /**
     * Source de l'item (core, plugin:name, etc.)
     * @var string
     */
    private string $source;

    /**
     * ID du parent (null pour racine)
     * @var string|null
     */
    private ?string $parentId;

    /**
     * Visibilité et permissions
     * @var array
     */
    private array $visibility;

    /**
     * Item activé/désactivé (pour lifecycle plugin)
     * @var bool
     */
    private bool $enabled;

    /**
     * Données additionnelles (pour extensions futures)
     * @var array
     */
    private array $metadata;

    /**
     * Enfants de cet item
     * @var NavigationItem[]
     */
    private array $children;

    /**
     * Contexte de navigation (ex: 'main', 'admin', 'footer')
     * @var string
     */
    private string $navContext;

    /**
     * Constructeur
     */
    public function __construct(
        string $id,
        string $label,
        string $target = '#',
        string $targetAttr = '_self',
        string $cssClass = '',
        ?string $icon = null,
        int $position = 100,
        ?array $positionRelative = null,
        string $source = 'core',
        ?string $parentId = null,
        array $visibility = ['public' => true],
        string $navContext = 'main',
        bool $enabled = true,
        array $metadata = []
    ) {
        $this->id = $this->validateId($id);
        $this->label = $label;
        $this->target = $target;
        $this->targetAttr = $targetAttr;
        $this->cssClass = $cssClass;
        $this->icon = $icon;
        $this->position = $position;
        $this->positionRelative = $positionRelative;
        $this->source = $source;
        $this->parentId = $parentId;
        $this->visibility = $this->normalizeVisibility($visibility);
        $this->navContext = $navContext;
        $this->enabled = $enabled;
        $this->metadata = $metadata;
        $this->children = [];
    }

    /**
     * Get value of one of the 6 visibility/context flags
     */
    public function getVisibilityFlag(string $flag): bool
    {
        $contexts = explode(',', $this->getNavContext());
        $contexts = array_map('trim', explode(',', implode(',', $contexts))); // Fix for explode('', '')

        switch ($flag) {
            case 'MP': return in_array('main', $contexts);
            case 'MA': return in_array('avatar', $contexts);
            case 'PP': return in_array('footer', $contexts);
            case 'AUTO': return in_array('auto', $contexts);
            case 'PUB': return $this->visibility['public'] ?? true;
            case 'MEM': return $this->visibility['logged'] ?? false;
            case 'ADM': return $this->visibility['admin'] ?? false;
        }
        return false;
    }

    /**
     * Valide et normalise l'ID (doit avoir un namespace)
     */
    private function validateId(string $id): string
    {
        // Vérifier qu'il y a au moins un point (namespace)
        if (strpos($id, '.') === false) {
            throw new \InvalidArgumentException("NavigationItem ID must have a namespace (ex: 'core.home', 'plugin.search.main'): {$id}");
        }
        
        // Vérifier les caractères autorisés
        if (!preg_match('/^[a-z0-9._-]+$/i', $id)) {
            throw new \InvalidArgumentException("NavigationItem ID contains invalid characters: {$id}");
        }
        
        return $id;
    }

    /**
     * Normalise la structure de visibilité
     */
    private function normalizeVisibility(array $visibility): array
    {
        return [
            'public' => $visibility['public'] ?? true,
            'admin' => $visibility['admin'] ?? false,
            'logged' => $visibility['logged'] ?? false,
            'roles' => $visibility['roles'] ?? [],
            'conditions' => $visibility['conditions'] ?? [],
        ];
    }

    /**
     * Crée un item depuis un tableau (pour chargement JSON)
     */
    public static function fromArray(array $data): self
    {
        $visibility = $data['visibility'] ?? ['public' => true];
        // Support pour le format legacy où conditions est au premier niveau
        if (isset($data['conditions']) && is_array($data['conditions'])) {
            $visibility['conditions'] = $data['conditions'];
        }

        return new self(
            $data['id'] ?? '',
            $data['label'] ?? '',
            $data['target'] ?? '#',
            $data['targetAttr'] ?? '_self',
            $data['cssClass'] ?? '',
            $data['icon'] ?? null,
            $data['position'] ?? 100,
            $data['positionRelative'] ?? null,
            $data['source'] ?? 'core',
            $data['parentId'] ?? null,
            $visibility,
            $data['navContext'] ?? 'main',
            $data['enabled'] ?? true,
            $data['metadata'] ?? []
        );
    }

    /**
     * Convertit en tableau (pour sauvegarde JSON)
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'label' => $this->label,
            'target' => $this->target,
            'targetAttr' => $this->targetAttr,
            'cssClass' => $this->cssClass,
            'position' => $this->position,
            'source' => $this->source,
            'visibility' => $this->visibility,
            'navContext' => $this->navContext,
            'enabled' => $this->enabled,
        ];

        if ($this->icon !== null) {
            $data['icon'] = $this->icon;
        }
        if ($this->positionRelative !== null) {
            $data['positionRelative'] = $this->positionRelative;
        }
        if ($this->parentId !== null) {
            $data['parentId'] = $this->parentId;
        }
        if (!empty($this->metadata)) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }

    /**
     * Vérifie si l'item est visible pour l'utilisateur actuel.
     * Utilise le moteur d'évaluation unifié pour une cohérence totale avec les contrôleurs.
     */
    public function isVisible(ConditionContext $ctx): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $evaluator = AccessPolicyEvaluator::getInstance();
        
        // On construit un groupe AND virtuel combinant les flags legacy et les conditions modernes
        // pour assurer une transition fluide et une compatibilité totale.
        $conditions = $this->visibility['conditions'] ?? [];
        
        // 1. Logique Legacy "Admin only"
        if ($this->visibility['admin'] ?? false) {
            $conditions[] = ['type' => 'role', 'value' => 'admin'];
        }
        
        // 2. Logique Legacy "Logged only"
        if ($this->visibility['logged'] ?? false) {
            $conditions[] = ['type' => 'auth', 'value' => true];
        }

        // 3. Logique Legacy "Roles list"
        if (!empty($this->visibility['roles'])) {
            $roleConditions = [];
            foreach ($this->visibility['roles'] as $role) {
                $roleConditions[] = ['type' => 'role', 'value' => $role];
            }
            if (count($roleConditions) > 1) {
                $conditions[] = ['logic' => 'OR', 'conditions' => $roleConditions];
            } else {
                $conditions[] = $roleConditions[0];
            }
        }

        // 4. Si absolument aucune condition n'est définie (ni legacy ni moderne),
        // on se base sur le flag 'public'.
        if (empty($conditions)) {
            return $this->visibility['public'] ?? true;
        }

        // Évaluation via le moteur unifié (short-circuit, caching, etc.)
        return $evaluator->evaluateGroup([
            'logic' => 'AND',
            'conditions' => $conditions
        ], $ctx);
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getLabel(): string { return $this->label; }
    public function getTarget(): string { return $this->target; }
    public function getTargetAttr(): string { return $this->targetAttr; }
    public function getCssClass(): string { return $this->cssClass; }
    public function getIcon(): ?string { return $this->icon; }
    public function getPosition(): int { return $this->position; }
    public function getPositionRelative(): ?array { return $this->positionRelative; }
    public function getSource(): string { return $this->source; }
    public function getParentId(): ?string { return $this->parentId; }
    public function getVisibility(): array { return $this->visibility; }
    public function isEnabled(): bool { return $this->enabled; }
    public function getMetadata(): array { return $this->metadata; }
    public function getChildren(): array { return $this->children; }

    /**
     * @inheritDoc
     */
    public function getResourceId(): string
    {
        return $this->id;
    }

    /**
     * Tentative de récupération de la ressource PageItem sous-jacente.
     */
    private function getUnderlyingPageItem(): ?PageItem
    {
        if (strpos($this->id, 'core.page.') === 0) {
            $pageId = (int)str_replace('core.page.', '', $this->id);
            if ($pageId > 0) {
                return (new Page())->create($pageId) ?: null;
            }
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getPolicy(): array
    {
        $page = $this->getUnderlyingPageItem();
        if ($page) {
            return $page->getPolicy();
        }
        return $this->visibility['conditions'] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function getVisibilityFlags(): array
    {
        $page = $this->getUnderlyingPageItem();
        if ($page) {
            return $page->getVisibilityFlags();
        }

        return [
            'MP' => $this->getVisibilityFlag('MP'),
            'MA' => $this->getVisibilityFlag('MA'),
            'PP' => $this->getVisibilityFlag('PP'),
            'AUTO' => $this->getVisibilityFlag('AUTO'),
            'PUB' => $this->getVisibilityFlag('PUB'),
            'MEM' => $this->getVisibilityFlag('MEM'),
            'ADM' => $this->getVisibilityFlag('ADM'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getResourceParent(): ?ResourceInterface
    {
        $page = $this->getUnderlyingPageItem();
        if ($page) {
            return $page->getResourceParent();
        }
        return null;
    }

    // Setters
    public function setLabel(string $label): void { $this->label = $label; }
    public function setTarget(string $target): void { $this->target = $target; }
    public function setTargetAttr(string $targetAttr): void { $this->targetAttr = $targetAttr; }
    public function setCssClass(string $cssClass): void { $this->cssClass = $cssClass; }
    public function setIcon(?string $icon): void { $this->icon = $icon; }
    public function setPosition(int $position): void { $this->position = $position; }
    public function setPositionRelative(?array $val): void { $this->positionRelative = $val; }

    public function setParentId(?string $parentId): void { $this->parentId = $parentId; }
    public function setEnabled(bool $enabled): void { $this->enabled = $enabled; }
    public function addChild(NavigationItem $child): void {
        // Vérifier si l'enfant existe déjà pour éviter les doublons
        foreach ($this->children as $existingChild) {
            if ($existingChild->getId() === $child->getId()) {
                return; // Enfant déjà présent, ne pas ajouter
            }
        }
        $this->children[] = $child;
    }
    
    /**
     * Vide la liste des enfants (utile lors du rebuild)
     */
    public function clearChildren(): void {
        $this->children = [];
    }
    public function setChildren(array $children): void { $this->children = $children; }

    public function getNavContext(): string { return $this->navContext; }
    public function setNavContext(string $navContext): void { $this->navContext = $navContext; }
}

