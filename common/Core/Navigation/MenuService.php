<?php

namespace Core\Navigation;

use Core\Core;
use Core\Page\Page;
use Core\Page\PageItem;
use Core\Plugin\PluginsManager;
use Core\Router\Router;
use Utils\Util;

/**
 * Service de gestion des menus indépendants des pages CMS.
 */
defined('ROOT') or exit('Access denied!');

class MenuService
{
    private static ?MenuService $instance = null;

    private function __construct()
    {
    }

    public static function getInstance(): MenuService
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function registerRoutes(Router $router): void
    {
        require_once COMMON . 'Core/Navigation/routes.php';
    }

    public function getAdminNavigationEntries(): array
    {
        return [
            [
                'name'  => 'menus',
                'label' => 'Menus',
                'icon'  => 'fa-solid fa-bars-staggered',
            ],
        ];
    }

    /**
     * Effectue une migration non destructive une seule fois depuis le système legacy (pages + JSON).
     */
    public function ensureMigrated(): void
    {
        if (!file_exists(DATA . 'config.json')) {
            return;
        }
        $flagFile = DATA_CORE . 'menus_migrated.flag';
        if (file_exists($flagFile)) {
            return;
        }

        try {
            if (MenuItem::queryBuilder()->count() > 0) {
                $this->writeMigratedFlag($flagFile);
                return;
            }

            $this->migrateFromLegacy();
            $this->writeMigratedFlag($flagFile);
        } catch (\Throwable $e) {
            Core::getInstance()->getLogger()->error('MenuService::ensureMigrated failed: ' . $e->getMessage());
        }
    }

    private function writeMigratedFlag(string $flagFile): void
    {
        if (!is_dir(DATA_CORE)) {
            @mkdir(DATA_CORE, 0755, true);
        }
        @file_put_contents($flagFile, date('c'));
    }

    /**
     * Migre les items existants (pages SQL, pages.json fallback, overrides JSON) vers menu_items.
     */
    public function migrateFromLegacy(): void
    {
        $sqlPages = PageItem::queryBuilder()->orderBy('position', 'asc')->get();
        $jsonPages = [];
        $legacyFile = DATA_CORE_PAGE . 'pages.json';
        if (file_exists($legacyFile)) {
            $raw = Util::readJsonFile($legacyFile, true);
            if (is_array($raw)) {
                foreach ($raw as $row) {
                    if (is_array($row)) {
                        $jsonPages[] = new PageItem($row);
                    }
                }
            }
        }

        // Fusionner SQL + éventuels items présents uniquement dans pages.json (ex: plugins auto-ajoutés)
        $seenKeys = [];
        $allLegacyItems = [];
        foreach (array_merge($sqlPages, $jsonPages) as $pageItem) {
            $target = (string)$pageItem->getTarget();
            $key = $target !== '' ? 'target:' . $target : 'id:' . $pageItem->getId();
            if (isset($seenKeys[$key])) {
                continue;
            }
            $seenKeys[$key] = true;
            $allLegacyItems[] = $pageItem;
        }

        $pluginsManager = PluginsManager::getInstance();
        $idMapByLocation = ['main' => [], 'avatar' => [], 'footer' => []];
        $pendingParents = [];

        foreach ($allLegacyItems as $pageItem) {
            if ($pageItem->getIsHidden()) {
                continue;
            }

            $targetType = $pageItem->targetIs();
            if ($targetType === 'plugin' && !$pluginsManager->isActivePlugin((string)$pageItem->getTarget())) {
                continue;
            }

            $rawContext = trim((string)$pageItem->getNavContext());
            if ($rawContext === '') {
                continue;
            }

            $locations = [];
            foreach (explode(',', $rawContext) as $ctx) {
                $ctx = trim($ctx);
                if ($ctx === 'auto' || $ctx === 'menu' || $ctx === 'default') {
                    $ctx = 'main';
                }
                if (in_array($ctx, MenuItem::VALID_LOCATIONS, true) && !in_array($ctx, $locations, true)) {
                    $locations[] = $ctx;
                }
            }

            if (empty($locations)) {
                continue;
            }

            $menuType = match ($targetType) {
                'plugin' => 'plugin',
                'url'    => 'url',
                'parent' => 'folder',
                default  => 'page',
            };

            $targetRef = match ($menuType) {
                'page'   => (string)$pageItem->getId(),
                'plugin' => (string)$pageItem->getTarget(),
                'url'    => (string)$pageItem->getTarget(),
                default  => '',
            };

            $icon = (string)$pageItem->getIcon();
            if ($icon === '' && $menuType === 'plugin') {
                $p = $pluginsManager->getPlugin((string)$pageItem->getTarget());
                if ($p) {
                    $icon = (string)($p->getInfoVal('icon') ?: '');
                }
            }

            $visibility = $this->convertLegacyConditionsToVisibility($pageItem->getConditions());

            foreach ($locations as $loc) {
                $menuItem = new MenuItem();
                $menuItem->setLocation($loc);
                $menuItem->setParentId(0);
                $menuItem->setPosition((int)$pageItem->getPosition());
                $menuItem->setType($menuType);
                $menuItem->setTargetRef($targetRef);
                $menuItem->setLabel((string)$pageItem->getName());
                $menuItem->setIcon($icon);
                $menuItem->setCssClass((string)$pageItem->getCssClass());
                $menuItem->setTargetAttr((string)($pageItem->getTargetAttr() ?: '_self'));
                $menuItem->setVisibility($visibility);
                $menuItem->setIsEnabled(1);

                if ($menuItem->save()) {
                    $legacyId = (string)$pageItem->getId();
                    $idMapByLocation[$loc][$legacyId] = $menuItem->getId();
                    $legacyParent = (string)$pageItem->getParent();
                    if ($legacyParent !== '' && $legacyParent !== '0') {
                        $pendingParents[] = [
                            'menuItem'     => $menuItem,
                            'location'     => $loc,
                            'legacyParent' => $legacyParent,
                        ];
                    }
                }
            }
        }

        // Résoudre les parents hiérarchiques dans chaque emplacement
        foreach ($pendingParents as $entry) {
            $loc = $entry['location'];
            $legacyParent = $entry['legacyParent'];
            if (isset($idMapByLocation[$loc][$legacyParent])) {
                $entry['menuItem']->setParentId($idMapByLocation[$loc][$legacyParent]);
                $entry['menuItem']->save();
            }
        }
    }

    private function convertLegacyConditionsToVisibility(array $conditions): array
    {
        foreach ($conditions as $c) {
            $type = $c['type'] ?? '';
            if ($type === 'role' && ($c['value'] ?? '') === 'admin') {
                return ['mode' => 'admin', 'roles' => []];
            }
            if ($type === 'auth') {
                return ['mode' => (($c['value'] ?? true) === false ? 'guest' : 'logged'), 'roles' => []];
            }
        }
        return ['mode' => 'public', 'roles' => []];
    }

    /**
     * Retourne le catalogue des entrées de plugins ajoutables dans /admin/menus.
     * Combine le hook 'navigation:catalog' et la découverte automatique des plugins publics actifs.
     */
    public function getCatalog(): array
    {
        $catalog = Core::getInstance()->callHook('navigation:catalog', []);
        if (!is_array($catalog)) {
            $catalog = [];
        }

        $registeredPlugins = [];
        $normalized = [];
        foreach ($catalog as $entry) {
            if (!is_array($entry) || empty($entry['label'])) {
                continue;
            }
            $plugin = (string)($entry['plugin'] ?? '');
            if ($plugin !== '') {
                $registeredPlugins[$plugin] = true;
            }
            $route = (string)($entry['route'] ?? '');
            $key = (string)($entry['key'] ?? ($route !== '' ? "{$plugin}:{$route}" : $plugin));
            $normalized[] = [
                'key'         => $key,
                'plugin'      => $plugin,
                'route'       => $route,
                'label'       => (string)$entry['label'],
                'icon'        => (string)($entry['icon'] ?? 'fa-solid fa-puzzle-piece'),
                'description' => (string)($entry['description'] ?? ''),
                'url'         => $this->resolvePluginUrl($plugin, $route),
            ];
        }

        // Découverte automatique des plugins publics actifs qui n'utilisent pas encore le hook
        $pluginsManager = PluginsManager::getInstance();
        foreach ($pluginsManager->getPlugins() as $plugin) {
            $name = $plugin->getName();
            if ($name === 'page' || isset($registeredPlugins[$name])) {
                continue;
            }
            if (!$plugin->getConfigVal('activate')) {
                continue;
            }
            if ($plugin->getInfoVal('hideInPublicMenu') === true) {
                continue;
            }
            if (!$plugin->getPublicFile() && !$plugin->getIsCallableOnPublic()) {
                continue;
            }

            $label = $plugin->getInfoVal('name') ?: ucfirst($name);
            $icon = $plugin->getInfoVal('icon') ?: 'fa-solid fa-puzzle-piece';
            $normalized[] = [
                'key'         => $name,
                'plugin'      => $name,
                'route'       => '',
                'label'       => (string)$label,
                'icon'        => (string)$icon,
                'description' => (string)($plugin->getInfoVal('desc') ?: ''),
                'url'         => $this->resolvePluginUrl($name, ''),
            ];
        }

        usort($normalized, fn($a, $b) => strcasecmp($a['label'], $b['label']));
        return $normalized;
    }

    /**
     * Résout l'URL publique d'un plugin ou d'une route nommée de plugin.
     */
    public function resolvePluginUrl(string $pluginName, string $routeName = ''): string
    {
        $router = Router::getInstance();
        $siteUrl = rtrim((string)Core::getInstance()->getConfigVal('siteUrl'), '/');

        if ($routeName !== '') {
            $url = $router->generate($routeName);
            if ($url !== '#') {
                return $url;
            }
        }

        if ($pluginName !== '') {
            $candidates = [
                $pluginName,
                "{$pluginName}-index",
                "{$pluginName}-home",
                strtolower($pluginName),
                strtolower($pluginName) . "-index",
                strtolower($pluginName) . "-home",
                "rdv-public-home", // fallback specifique ProRdv
            ];
            foreach ($candidates as $candidate) {
                $url = $router->generate($candidate);
                if ($url !== '#') {
                    return $url;
                }
            }

            // Tentative d'analyse de la route mappée à homePublicMethod
            $pluginObj = PluginsManager::getInstance()->getPlugin($pluginName);
            if ($pluginObj) {
                $hpm = $pluginObj->getInfoVal('homePublicMethod');
                if (!empty($hpm)) {
                    foreach ($router->getRoutes() as $r) {
                        // $r = [$method, $pattern, $target, $name]
                        if (($r[2] ?? '') === $hpm && !empty($r[3])) {
                            $u = $router->generate($r[3]);
                            if ($u !== '#') {
                                return $u;
                            }
                        }
                    }
                }
            }

            return $siteUrl . '/' . $pluginName . '/';
        }

        return '#';
    }

    /**
     * Résout dynamiquement l'URL et l'état d'un MenuItem.
     *
     * @return array{url: string, active: bool, conditions: array, targetLabel: string}
     */
    public function resolveMenuItemTarget(MenuItem $item, array $pagesById = [], ?Page $pageManager = null): array
    {
        $type = $item->getType();
        $ref = $item->getTargetRef();

        if ($type === 'page') {
            $pageManager = $pageManager ?? Core::getInstance()->pages();
            $pageItem = $pagesById[(string)$ref] ?? $pageManager->create($ref);
            if (!$pageItem || $pageItem->getIsHidden()) {
                return [
                    'url'         => '#',
                    'active'      => false,
                    'conditions'  => [],
                    'targetLabel' => $pageItem ? $pageItem->getName() . ' (Brouillon)' : 'Page introuvable',
                ];
            }

            return [
                'url'         => $pageManager->makeUrl($pageItem),
                'active'      => true,
                'conditions'  => $pageItem->getConditions(),
                'targetLabel' => $pageItem->getName(),
            ];
        }

        if ($type === 'plugin') {
            $parts = explode(':', $ref, 2);
            $pluginName = trim($parts[0]);
            $routeName = trim($parts[1] ?? '');

            $pluginsManager = PluginsManager::getInstance();
            if ($pluginName === '' || !$pluginsManager->isActivePlugin($pluginName)) {
                return [
                    'url'         => '#',
                    'active'      => false,
                    'conditions'  => [],
                    'targetLabel' => 'Plugin inactif (' . $pluginName . ')',
                ];
            }

            $plugin = $pluginsManager->getPlugin($pluginName);
            return [
                'url'         => $this->resolvePluginUrl($pluginName, $routeName),
                'active'      => true,
                'conditions'  => [],
                'targetLabel' => $plugin ? ($plugin->getInfoVal('name') ?: $pluginName) : $pluginName,
            ];
        }

        if ($type === 'folder') {
            return [
                'url'         => '#',
                'active'      => true,
                'conditions'  => [],
                'targetLabel' => 'Dossier',
            ];
        }

        return [
            'url'         => $ref !== '' ? $ref : '#',
            'active'      => true,
            'conditions'  => [],
            'targetLabel' => $ref,
        ];
    }

    /**
     * Retourne tous les items d'un emplacement triés par position.
     *
     * @return MenuItem[]
     */
    public function getLocationItems(string $location): array
    {
        $this->ensureMigrated();
        if (!in_array($location, MenuItem::VALID_LOCATIONS, true)) {
            $location = 'main';
        }
        return MenuItem::queryBuilder()
            ->where('location', $location)
            ->orderBy('position', 'asc')
            ->get();
    }

    /**
     * Construit la structure imbriquée pour l'interface d'administration /admin/menus.
     */
    public function getLocationTree(string $location): array
    {
        $items = $this->getLocationItems($location);
        $pageManager = Core::getInstance()->pages();
        $pagesById = [];
        foreach ($pageManager->getItems() as $p) {
            $pagesById[(string)$p->getId()] = $p;
        }

        $nodes = [];
        foreach ($items as $item) {
            $resolved = $this->resolveMenuItemTarget($item, $pagesById, $pageManager);
            $vis = $item->getVisibility();
            $id = $item->getId();
            $nodes[(string)$id] = [
                'id'          => $id,
                'location'    => $item->getLocation(),
                'parentId'    => $item->getParentId(),
                'position'    => $item->getPosition(),
                'type'        => $item->getType(),
                'targetRef'   => $item->getTargetRef(),
                'label'       => $item->getLabel(),
                'icon'        => $item->getIcon(),
                'cssClass'    => $item->getCssClass(),
                'targetAttr'  => $item->getTargetAttr(),
                'visibility'  => $vis,
                'isEnabled'   => $item->isEnabled(),
                'url'         => $resolved['url'],
                'targetActive'=> $resolved['active'],
                'targetLabel' => $resolved['targetLabel'],
                'children'    => [],
            ];
        }

        $roots = [];
        foreach ($nodes as $idKey => &$node) {
            $pid = MenuItem::normalizeId($node['parentId']);
            $pidKey = (string)$pid;
            if ($pid !== 0 && isset($nodes[$pidKey]) && $pidKey !== (string)$idKey) {
                $nodes[$pidKey]['children'][] = &$node;
            } else {
                $roots[] = &$node;
            }
        }
        unset($node);

        return $roots;
    }

    public function getLocationCounts(): array
    {
        $this->ensureMigrated();
        $counts = ['main' => 0, 'avatar' => 0, 'footer' => 0];
        foreach (MenuItem::all() as $item) {
            $loc = $item->getLocation();
            if (isset($counts[$loc])) {
                $counts[$loc]++;
            }
        }
        return $counts;
    }

    /**
     * Retourne les emplacements où une page donnée apparaît (pour affichage informatif dans /admin/page).
     */
    public function getMenuLocationsForPage(int $pageId): array
    {
        $this->ensureMigrated();
        $items = MenuItem::queryBuilder()
            ->where('type', 'page')
            ->where('target_ref', (string)$pageId)
            ->get();

        $locations = [];
        foreach ($items as $item) {
            $loc = $item->getLocation();
            if (!in_array($loc, $locations, true)) {
                $locations[] = $loc;
            }
        }
        return $locations;
    }

    public function hasTargetInLocation(string $location, string $type, string $targetRef): bool
    {
        $targetRef = trim($targetRef);
        if ($targetRef === '' || !in_array($type, ['page', 'plugin'], true)) {
            return false;
        }
        foreach ($this->getLocationItems($location) as $item) {
            if ($item->getType() === $type && trim($item->getTargetRef()) === $targetRef) {
                return true;
            }
        }
        return false;
    }

    public function getNextPosition(string $location, int|string $parentId = 0): int
    {
        $items = $this->getLocationItems($location);
        $parentKey = (string)MenuItem::normalizeId($parentId);
        $max = 0;
        foreach ($items as $item) {
            if ((string)$item->getParentId() === $parentKey && $item->getPosition() > $max) {
                $max = $item->getPosition();
            }
        }
        return $max + 10;
    }

    public function createItem(array $data): ?MenuItem
    {
        $this->ensureMigrated();
        $location = in_array($data['location'] ?? 'main', MenuItem::VALID_LOCATIONS, true)
            ? $data['location']
            : 'main';
        $parentId = MenuItem::normalizeId($data['parent_id'] ?? 0);
        $type = (string)($data['type'] ?? 'url');
        $targetRef = trim((string)($data['target_ref'] ?? ''));

        if (in_array($type, ['page', 'plugin'], true) && $targetRef !== '' && $this->hasTargetInLocation($location, $type, $targetRef)) {
            return null;
        }

        $item = new MenuItem();
        $item->setLocation($location);
        $item->setParentId($parentId);
        $item->setPosition(isset($data['position']) ? (int)$data['position'] : $this->getNextPosition($location, $parentId));
        $item->setType($type);
        $item->setTargetRef($targetRef);
        $item->setLabel((string)($data['label'] ?? 'Nouvel élément'));
        $item->setIcon((string)($data['icon'] ?? ''));
        $item->setCssClass((string)($data['css_class'] ?? ''));
        $item->setTargetAttr((string)($data['target_attr'] ?? '_self'));
        $item->setVisibility(is_array($data['visibility'] ?? null) ? $data['visibility'] : ['mode' => 'public', 'roles' => []]);
        $item->setIsEnabled($data['is_enabled'] ?? 1);

        if ($item->save()) {
            NavigationService::getInstance()->reset();
            return $item;
        }
        return null;
    }

    public function updateItem(int|string $id, array $data): bool
    {
        $id = MenuItem::normalizeId($id);
        if ($id === 0) {
            return false;
        }
        $item = MenuItem::findPK($id);
        if (!$item) {
            return false;
        }

        if (isset($data['label'])) {
            $label = trim((string)$data['label']);
            if ($label !== '') {
                $item->setLabel($label);
            }
        }
        if (array_key_exists('icon', $data)) {
            $item->setIcon((string)$data['icon']);
        }
        if (array_key_exists('css_class', $data)) {
            $item->setCssClass((string)$data['css_class']);
        }
        if (array_key_exists('target_attr', $data)) {
            $item->setTargetAttr((string)$data['target_attr']);
        }
        if ($item->getType() === 'url' && isset($data['target_ref'])) {
            $item->setTargetRef((string)$data['target_ref']);
        }
        if (isset($data['visibility_mode'])) {
            $item->setVisibility([
                'mode'  => (string)$data['visibility_mode'],
                'roles' => is_array($data['visibility_roles'] ?? null) ? $data['visibility_roles'] : [],
            ]);
        }
        if (array_key_exists('is_enabled', $data)) {
            $item->setIsEnabled($data['is_enabled']);
        }

        $ok = (bool)$item->save();
        if ($ok) {
            NavigationService::getInstance()->reset();
        }
        return $ok;
    }

    public function deleteItem(int|string $id): bool
    {
        $id = MenuItem::normalizeId($id);
        if ($id === 0) {
            return false;
        }
        $item = MenuItem::findPK($id);
        if (!$item) {
            return false;
        }

        // Réattacher les éventuels enfants au parent de l'item supprimé (compatible JSON et SQL)
        $parentId = $item->getParentId();
        $idKey = (string)$id;
        foreach ($this->getLocationItems($item->getLocation()) as $child) {
            if ((string)$child->getParentId() === $idKey) {
                $child->setParentId($parentId);
                $child->save();
            }
        }

        $ok = (bool)$item->delete();
        if ($ok) {
            NavigationService::getInstance()->reset();
        }
        return $ok;
    }

    /**
     * Sauvegarde la hiérarchie et les positions d'un emplacement.
     *
     * @param array<int, array{id: int|string, parent_id: int|string, position: int}> $flatNodes
     */
    public function saveOrder(string $location, array $flatNodes): bool
    {
        $items = $this->getLocationItems($location);
        $byId = [];
        foreach ($items as $it) {
            $byId[(string)$it->getId()] = $it;
        }

        $success = true;
        foreach ($flatNodes as $node) {
            $id = MenuItem::normalizeId($node['id'] ?? 0);
            $idKey = (string)$id;
            if ($id === 0 || !isset($byId[$idKey])) {
                continue;
            }
            $parentId = MenuItem::normalizeId($node['parent_id'] ?? 0);
            $parentKey = (string)$parentId;
            if ($parentKey === $idKey || ($parentId !== 0 && !isset($byId[$parentKey]))) {
                $parentId = 0;
            }
            $position = (int)($node['position'] ?? 0);

            $item = $byId[$idKey];
            $item->setParentId($parentId);
            $item->setPosition($position);
            if (!$item->save()) {
                $success = false;
            }
        }

        NavigationService::getInstance()->reset();
        return $success;
    }
}
