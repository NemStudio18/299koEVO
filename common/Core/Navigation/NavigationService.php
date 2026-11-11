<?php

namespace Core\Navigation;

use Core\Core;
use Core\Plugin\PluginsManager;
use Core\Page\Page;
use Utils\Util;

/**
 * Service principal de gestion de la navigation
 * 
 * Responsabilités :
 * - Chargement depuis JSON
 * - Fusion core + plugins
 * - Gestion du lifecycle
 * - Filtrage par permissions
 * - Hook système pour injection plugins
 */
defined('ROOT') or exit('Access denied!');

class NavigationService
{
    private static ?NavigationService $instance = null;
    private NavigationTree $tree;
    private string $navigationFile;
    private bool $built = false;

    private ?ConditionContext $ctx = null;
    private bool $hookCalled = false; // Protection contre appels multiples du hook

    private function __construct()
    {
        $this->tree = new NavigationTree();
        $this->navigationFile = DATA_CORE . 'navigation.json';
        $this->ensureNavigationFile();
    }

    /**
     * Instance singleton
     */
    public static function getInstance(): NavigationService
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * S'assure que le fichier navigation.json existe
     */
    private function ensureNavigationFile(): void
    {
        if (!is_dir(DATA_CORE)) {
            @mkdir(DATA_CORE, 0755, true);
        }

        if (!file_exists($this->navigationFile)) {
            $default = [
                'version' => '1.0',
                'items' => []
            ];
            Util::writeJsonFile($this->navigationFile, $default);
        }
    }

    /**
     * Construit l'arbre de navigation complet
     * Appelle les hooks, fusionne core + plugins, nettoie
     */
    public function build(): NavigationTree
    {
        if ($this->built) {
            return $this->tree;
        }

        $user = Core::getInstance()->auth()->getCurrentUser();
        $ctx = new ConditionContext($user);
        $this->ctx = $ctx;

        // Calcul du hash des rôles + état auth pour le cache
        $roles = $ctx->getRoles();
        sort($roles);
        $authState = $user ? 'logged' : 'guest';
        $roleHash = md5($authState . '|' . implode('|', $roles));
        $cacheFile = \CACHE . 'nav_tree_' . $roleHash . '.json';
        
        // 1. Essayer de charger depuis le cache (FS)
        if (!Core::getInstance()->getConfigVal('debug') && file_exists($cacheFile) && (time() - filemtime($cacheFile) < 3600)) {
            $cachedData = Util::readJsonFile($cacheFile, true);
            if ($cachedData) {
                $this->tree = NavigationTree::fromArray($cachedData);
                $this->built = true;
                return $this->tree;
            }
        }

        // Vérifier si des items ont déjà été enregistrés via register()
        $hasRegisteredItems = !empty($this->tree->getAllItems());
        
        if (!$hasRegisteredItems) {
            $this->tree->clear();
            $this->loadFromMenuItems();
        }

        // 3. Hook optionnel pour items dynamiques runtime (navigation:render-tree)
        $core = Core::getInstance();
        $core->callHook('navigation:render-tree', [$this->tree]);

        // 4. Construire l'arbre hiérarchique
        $this->tree->build();

        // 5. Appliquer les positions relatives éventuelles
        $this->tree->applyRelativePositions();

        // 6. Nettoyer (désactiver plugins inactifs)
        $this->tree->cleanup($this->getActivePluginSources());

        // 7. Évaluer les conditions
        $this->tree->evaluateConditions($ctx);

        // 8. Sauvegarder dans le cache
        if (!is_dir(\CACHE)) @mkdir(\CACHE, 0755, true);
        Util::writeJsonFile($cacheFile, $this->tree->toArray());

        $this->built = true;
        return $this->tree;
    }

    /**
     * Charge la navigation depuis le système unifié MenuItem (table menu_items).
     */
    private function loadFromMenuItems(): void
    {
        $menuService = MenuService::getInstance();
        $menuService->ensureMigrated();

        $menuItems = MenuItem::queryBuilder()
            ->where('is_enabled', 1)
            ->orderBy('position', 'asc')
            ->get();

        if (empty($menuItems)) {
            return;
        }

        $pageManager = new Page();
        $pagesById = [];
        foreach ($pageManager->getItems() as $p) {
            $pagesById[(int)$p->getId()] = $p;
        }

        foreach ($menuItems as $menuItem) {
            $resolved = $menuService->resolveMenuItemTarget($menuItem, $pagesById, $pageManager);
            if (!$resolved['active']) {
                continue;
            }

            $source = 'core';
            if ($menuItem->getType() === 'plugin') {
                $parts = explode(':', $menuItem->getTargetRef(), 2);
                $pluginName = trim($parts[0]);
                if ($pluginName !== '') {
                    $source = 'plugin:' . $pluginName;
                }
            }

            $parentId = $menuItem->getParentId() > 0 ? 'menu.' . $menuItem->getParentId() : null;
            $visibility = $menuItem->toNavigationVisibility($resolved['conditions']);

            try {
                $navItem = new NavigationItem(
                    'menu.' . $menuItem->getId(),
                    $menuItem->getLabel(),
                    $resolved['url'],
                    $menuItem->getTargetAttr(),
                    $menuItem->getCssClass(),
                    $menuItem->getIcon() !== '' ? $menuItem->getIcon() : null,
                    $menuItem->getPosition(),
                    null,
                    $source,
                    $parentId,
                    $visibility,
                    $menuItem->getLocation()
                );
                $this->tree->addItem($navItem);
            } catch (\Exception $e) {
                Core::getInstance()->getLogger()->error('NavigationService: Failed to add MenuItem: ' . $e->getMessage());
            }
        }
    }

    /**
     * Récupère un arbre filtré pour un canal spécifique (main, avatar, footer)
     * Applique le filtrage par canal et le nettoyage des parents vides
     */
    public function getTreeForChannel(string $channel): NavigationTree
    {
        // S'assurer que l'arbre principal est construit
        if (!$this->built) {
            $this->build();
        }
        
        $ctx = $this->ctx ?? new ConditionContext(Core::getInstance()->auth()->getCurrentUser());
        
        // filterByChannel retourne un NOUVEL arbre
        $channelTree = $this->tree->filterByChannel($channel, $ctx);

        // Nettoyer les parents vides (ex: dropdown dont tous les enfants sont masqués)
        $channelTree->pruneEmptyParents();

        return $channelTree;
    }

    /**
     * Charge les items depuis le fichier JSON
     */
    private function loadFromJson(): void
    {
        $data = Util::readJsonFile($this->navigationFile, true);
        if (!is_array($data) || !isset($data['items'])) {
            return;
        }

        foreach ($data['items'] as $itemData) {
            try {
                $item = NavigationItem::fromArray($itemData);
                $this->tree->addItem($item);
            } catch (\Exception $e) {
                Core::getInstance()->getLogger()->error("NavigationService: Failed to load item: " . $e->getMessage());
            }
        }
    }

    /**
     * Charge les items depuis Page (legacy)
     * 
     * Note: Cette méthode ne devrait être appelée que si les items n'ont pas déjà été
     * enregistrés via register() dans Page::addToNavigation()
     */
    private function loadFromPage(): void
    {
        $core = Core::getInstance();
        $coreNavigation = $core->getCoreNavigation();
        
        // Charger les pages pour récupérer leurs positions réelles
        $page = new Page();
        $pagesById = [];
        foreach ($page->getItems() as $pageItem) {
            $pagesById[$pageItem->getId()] = $pageItem;
        }

        foreach ($coreNavigation as $navData) {
            try {
                // Générer un ID cohérent avec Page::addToNavigation()
                // Utiliser l'ID de la page si disponible, sinon générer un ID unique
                $pageId = $navData['id'] ?? 0;
                if ($pageId > 0) {
                    $id = 'core.page.' . $pageId;
                } else {
                    // Si pas d'ID, générer un ID unique mais ne pas charger cet item
                    // car il sera probablement enregistré via register() avec un ID correct
                    continue;
                }
                
                // Vérifier si l'item existe déjà (évite les doublons)
                if ($this->tree->getItemById($id) !== null) {
                    continue;
                }
                
                // Récupérer la position réelle de la page (pas l'ID)
                $position = 100; // Par défaut
                if (isset($pagesById[$pageId])) {
                    $position = (int)$pagesById[$pageId]->getPosition();
                }
                
                // Préparation des données de visibilité et conditions
                $visibility = ['public' => true, 'admin' => false, 'logged' => false, 'roles' => [], 'conditions' => []];
                if (isset($pagesById[$pageId])) {
                    $itemConditions = $pagesById[$pageId]->getConditions();
                    $visibility['conditions'] = $itemConditions;
                    
                    // Déterminer les flags simplifiés à partir des conditions pour NavigationItem::isVisible
                    foreach ($itemConditions as $c) {
                        $type = $c['type'] ?? '';
                        if ($type === 'auth') {
                            $visibility['logged'] = true;
                            $visibility['public'] = false;
                        } elseif ($type === 'role') {
                            if (($c['value'] ?? '') === 'admin') {
                                $visibility['admin'] = true;
                                $visibility['public'] = false;
                            } else {
                                $visibility['roles'][] = $c['value'];
                                $visibility['public'] = false;
                            }
                        }
                    }
                }
                
                // Correction targetAttr (pages.json utilise targetAttr, mais on supporte targetAttribut legacy au cas où)
                $targetAttr = $navData['targetAttr'] ?? $navData['targetAttribut'] ?? '_self';

                $item = new NavigationItem(
                    $id,
                    $navData['label'] ?? '',
                    $navData['target'] ?? '#',
                    $targetAttr,
                    $navData['cssClass'] ?? '',
                    $navData['icon'] ?? null,
                    $position,
                    null,
                    'core',
                    $navData['parent'] > 0 ? 'core.page.' . $navData['parent'] : null,
                    $visibility,
                    $navData['navContext'] ?? 'main'
                );

                $this->tree->addItem($item);
            } catch (\Exception $e) {
                // Ignorer les erreurs d'ID dupliqué (item déjà enregistré)
                if (strpos($e->getMessage(), 'already exists') === false) {
                    Core::getInstance()->getLogger()->error("NavigationService: Failed to load legacy item: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Appelle le hook navigation:build pour injection plugins
     */
    private function callBuildHook(): void
    {
        // Protection contre appels multiples
        if ($this->hookCalled) {
            return;
        }
        $this->hookCalled = true;
        
        $core = Core::getInstance();
        
        // Hook pour plugins
        $core->callHook('navigation:build', [$this->tree]);

        // Charger aussi depuis les plugins legacy (addToNavigation) - DÉSACTIVÉ POUR TEST
        /*
        $pluginsManager = PluginsManager::getInstance();
        foreach ($pluginsManager->getPlugins() as $plugin) {
            if ($plugin->getConfigVal('activate') == 1
                && $plugin->getInfoVal('hideInPublicMenu') !== true) {
                
                foreach ($plugin->getNavigation() as $navData) {
                    if (empty($navData['label'])) {
                        continue;
                    }

                    try {
                        $id = 'plugin.' . $plugin->getName() . '.' . ($navData['id'] ?? uniqid());
                        
                        $item = new NavigationItem(
                            $id,
                            $navData['label'],
                            $navData['target'] ?? '#',
                            $navData['targetAttribut'] ?? '_self',
                            $navData['cssClass'] ?? '',
                            null,
                            $navData['id'] ?? 100,
                            null,
                            'plugin:' . $plugin->getName(),
                            $navData['parent'] > 0 ? 'plugin.' . $plugin->getName() . '.' . $navData['parent'] : null,
                            ['public' => true]
                        );

                        $this->tree->addItem($item);
                    } catch (\Exception $e) {
                        Core::getInstance()->getLogger()->error("NavigationService: Failed to load plugin item: " . $e->getMessage());
                    }
                }
            }
        }
        */
    }

    /**
     * Récupère la liste des sources de plugins actifs
     */
    private function getActivePluginSources(): array
    {
        $pluginsManager = PluginsManager::getInstance();
        $sources = [];

        foreach ($pluginsManager->getPlugins() as $plugin) {
            if ($plugin->getConfigVal('activate') == 1) {
                $sources[] = $plugin->getName();
            }
        }

        return $sources;
    }

    /**
     * Récupère les rôles de l'utilisateur actuel
     */
    private function getUserRoles(): array
    {
        $user = \Core\Auth\UsersManager::getCurrentUser();
        if ($user === null) {
            return [];
        }

        try {
            $user = \Core\Auth\UsersManager::getCurrentUser();
            if ($user === null) {
                return [];
            }

            $group = $user->getGroup();
            if ($group === null) {
                return [];
            }

            // Retourner les permissions comme rôles (à adapter selon votre système)
            return $group->getPermissions() ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Enregistre un item de navigation (pour plugins via hook)
     * 
     * Note: Les items enregistrés ici seront ajoutés à l'arbre lors du build()
     * Si build() a déjà été appelé, l'item sera ajouté immédiatement
     */
    public function register(string $id, array $data): void
    {
        try {
            // Vérifier si l'item existe déjà (évite les doublons)
            if ($this->tree->getItemById($id) !== null) {
                return;
            }
            
            // Vérifier aussi les doublons par label + target + parent (pour éviter les doublons même avec IDs différents)
            $label = $data['label'] ?? '';
            $target = $data['target'] ?? '#';
            $parent = $data['parent'] ?? null;
            $source = $data['source'] ?? 'plugin:unknown';
            foreach ($this->tree->getAllItems() as $existingItem) {
                if ($existingItem->getLabel() === $label 
                    && $existingItem->getTarget() === $target 
                    && $existingItem->getParentId() === $parent
                    && $existingItem->getSource() === $source) {
                    return;
                }
            }
            
            // Charger les positions personnalisées depuis le fichier de configuration
            $customPosition = $data['position'] ?? 100;
            $navPositionsFile = DATA_CORE . 'navigation_positions.json';
            if (file_exists($navPositionsFile)) {
                $customPositions = Util::readJsonFile($navPositionsFile, true);
                if (is_array($customPositions) && isset($customPositions[$id])) {
                    $customPosition = (int)$customPositions[$id];
                }
            }

            $visibility = $data['visibility'] ?? ['public' => true];
            $navContext = $data['navContext'] ?? 'main';

            // Appliquer les surcharges de visibilité (overrides)
            $overridesFile = DATA_CORE . 'navigation_overrides.json';
            if (file_exists($overridesFile)) {
                $overrides = Util::readJsonFile($overridesFile, true);
                if (is_array($overrides) && isset($overrides[$id])) {
                    if (isset($overrides[$id]['navContext'])) {
                        $navContext = $overrides[$id]['navContext'];
                    }
                    if (isset($overrides[$id]['visibility'])) {
                        $visibility = array_merge($visibility, $overrides[$id]['visibility']);
                    }
                }
            }
            
            $item = new NavigationItem(
                $id,
                $data['label'] ?? '',
                $data['target'] ?? '#',
                $data['targetAttr'] ?? '_self',
                $data['cssClass'] ?? '',
                $data['icon'] ?? null,
                $customPosition, // Utiliser la position personnalisée si disponible
                $data['positionRelative'] ?? null,
                $data['source'] ?? 'plugin:unknown',
                $data['parent'] ?? null,
                $visibility,
                $navContext,
                $data['enabled'] ?? true,
                $data['metadata'] ?? []
            );
            
            $this->tree->addItem($item);
        } catch (\Exception $e) {
            // Ignorer les erreurs d'ID dupliqué (item déjà enregistré)
            if (strpos($e->getMessage(), 'already exists') === false) {
                Core::getInstance()->getLogger()->error("NavigationService: Failed to register item '{$id}': " . $e->getMessage());
            }
        }
    }

    /**
     * Applique les positions sauvegardées à tous les items de l'arbre
     * Cela permet de surcharger les positions définies par les plugins lors de leur enregistrement via hook
     */
    private function applySavedPositions(): void
    {
        $navPositionsFile = DATA_CORE . 'navigation_positions.json';
        if (!file_exists($navPositionsFile)) {
            return;
        }

        $positions = Util::readJsonFile($navPositionsFile, true);
        if (!is_array($positions)) {
            return;
        }

        foreach ($this->tree->getAllItems() as $item) {
            $id = $item->getId();
            if (isset($positions[$id])) {
                $item->setPosition((int)$positions[$id]);
                // Clear relative position to ensure saved order is respected and not overridden by applyRelativePositions
                $item->setPositionRelative(null);
            }
        }
    }

    /**
     * Persiste une surcharge de visibilité pour un item (utilisé par admin)
     */
    public function saveOverride(string $id, array $flags): bool
    {
        try {
            $overridesFile = DATA_CORE . 'navigation_overrides.json';
            $overrides = [];
            if (file_exists($overridesFile)) {
                $overrides = Util::readJsonFile($overridesFile, true) ?: [];
            }

            // Convertir les flags en navContext et visibility unifié
            $navContexts = [];
            if ($flags['MP'] ?? false) $navContexts[] = 'main';
            if ($flags['MA'] ?? false) $navContexts[] = 'avatar';
            if ($flags['PP'] ?? false) $navContexts[] = 'footer';
            if ($flags['AUTO'] ?? false) $navContexts[] = 'auto';

            $overrides[$id] = [
                'navContext' => implode(',', $navContexts),
                'visibility' => [
                    'public' => $flags['PUB'] ?? true,
                    'logged' => $flags['MEM'] ?? false,
                    'admin' => $flags['ADM'] ?? false,
                ]
            ];

            if (!is_dir(DATA_CORE)) {
                @mkdir(DATA_CORE, 0755, true);
            }

            return Util::writeJsonFile($overridesFile, $overrides);
        } catch (\Exception $e) {
            Core::getInstance()->getLogger()->error("NavigationService: Failed to save override for '{$id}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère l'arbre (force rebuild si nécessaire)
     */
    public function getTree(bool $forceRebuild = false): NavigationTree
    {
        if ($forceRebuild || !$this->built) {
            $this->built = false;
            return $this->build();
        }
        return $this->tree;
    }

    /**
     * Réinitialise le cache (utile après modifications)
     */
    public function reset(): void
    {
        $this->built = false;
        $this->hookCalled = false;
        $this->tree->clear();

        // Nettoyer le cache persistant sur disque
        if (is_dir(\CACHE)) {
            $files = glob(\CACHE . 'nav_tree_*.json');
            if ($files) {
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
        }

        // Invalider le cache HTML de page complète (pour que la nouvelle navigation apparaisse partout)
        if (class_exists('\\Core\\Core') && method_exists('\\Core\\Core', 'bumpContentVersion')) {
            \Core\Core::bumpContentVersion();
        }
    }

    /**
     * Sauvegarde l'arbre dans le fichier JSON
     */
    public function save(): bool
    {
        $items = [];
        foreach ($this->tree->getAllItems() as $item) {
            // Ne sauvegarder que les items core (pas les plugins)
            if (strpos($item->getSource(), 'core') === 0) {
                $items[] = $item->toArray();
            }
        }

        $data = [
            'version' => '1.0',
            'items' => $items
        ];

        return Util::writeJsonFile($this->navigationFile, $data);
    }
}

