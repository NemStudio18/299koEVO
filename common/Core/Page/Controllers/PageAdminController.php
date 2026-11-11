<?php

namespace Core\Page\Controllers;

use Core\Controllers\AdminController;
use Core\Page\Page;
use Core\Page\PageItem;
use Core\Core;
use Utils\Show;
use Core\Lang;
use Core\Responses\AdminResponse;
use Content\Editor;
use Utils\Util;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class PageAdminController extends AdminController {
    
    public function __construct() {
        parent::__construct();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('core:page:manage'), $ctx)) {
            Show::msg(Lang::get('permissions.denied'), 'error');
            $this->core->redirect($this->router->generate('admin'));
        }
    }

    public function list() {
        $page = new Page();
        if (!$page->createHomepage() && $this->core->getConfigVal('defaultPlugin') === 'page') {
            Show::msg(Lang::get('page.no-homepage-defined'), 'warning');
        }

        $menuService = \Core\Navigation\MenuService::getInstance();
        $locationLabels = [
            'main'   => 'Menu principal',
            'avatar' => 'Menu Avatar',
            'footer' => 'Pied de page',
        ];

        $cmsPages = [];
        $editorialStats = [
            'totalPages'     => 0,
            'publishedPages' => 0,
            'draftPages'     => 0,
            'protectedPages' => 0,
        ];

        foreach ($page->getItems() as $item) {
            if ($item->targetIs() !== 'page') {
                continue;
            }

            $isDraft = (bool)$item->getIsHidden();
            $hasPassword = trim((string)$item->getPassword()) !== '';
            $editorialStats['totalPages']++;
            if ($isDraft) {
                $editorialStats['draftPages']++;
            } else {
                $editorialStats['publishedPages']++;
            }
            if ($hasPassword || $item->getNoIndex()) {
                $editorialStats['protectedPages']++;
            }

            $locs = $menuService->getMenuLocationsForPage((int)$item->getId());
            $locBadges = array_map(fn($loc) => $locationLabels[$loc] ?? $loc, $locs);

            $cmsPages[] = [
                'id'            => (int)$item->getId(),
                'name'          => (string)$item->getName(),
                'mainTitle'     => (string)$item->getMainTitle(),
                'url'           => $page->makeUrl($item),
                'isHomepage'    => (bool)$item->getIsHomepage(),
                'isHidden'      => $isDraft,
                'hasPassword'   => $hasPassword,
                'noIndex'       => (bool)$item->getNoIndex(),
                'menuLocations' => $locBadges,
            ];
        }

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('page', 'list');
        $tpl->set('token', $this->user->token);
        $tpl->set('page', $page);
        $tpl->set('cmsPages', $cmsPages);
        $tpl->set('editorialStats', $editorialStats);
        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Génère le HTML récursif de l'arbre de tri
     */
    private function renderTreeHtml(array $nodes): string
    {
        if (empty($nodes)) return '<ul class="nested-sortable"></ul>';
        
        $html = '<ul class="nested-sortable">';
        foreach ($nodes as $node) {
            $id = htmlspecialchars($node['id'] ?? '', ENT_QUOTES);
            $type = htmlspecialchars($node['type'] ?? '', ENT_QUOTES);
            $icon = htmlspecialchars($node['icon'] ?? 'fa-solid fa-file', ENT_QUOTES);
            $label = htmlspecialchars($node['label'] ?? '', ENT_QUOTES);
            $url = htmlspecialchars($node['url'] ?? '', ENT_QUOTES);
            $badge = $node['badge'] ?? 'page';
            
            $badgeHtml = match($badge) {
                'page' => '<span class="item-type badge-page">Page</span>',
                'folder' => '<span class="item-type badge-folder">Dossier</span>',
                'link' => '<span class="item-type badge-link">Lien</span>',
                'plugin' => '<span class="item-type badge-plugin">Plugin</span>',
                default => ''
            };
            
            $childrenHtml = '';
            if (!empty($node['children'])) {
                $childrenHtml = $this->renderTreeHtml($node['children']);
            }
            
            $html .= '<li class="sortable-item" data-id="' . $id . '" data-type="' . $type . '">';
            $html .= '<div class="item-content">';
            $html .= '<i class="fa-solid fa-grip-vertical drag-handle"></i>';
            $html .= '<div class="item-icon"><i class="' . $icon . '"></i></div>';
            $html .= '<strong style="flex-grow: 1;">' . $label . '</strong>';
            $html .= '<div class="item-url">' . $url . '</div>';
            $html .= $badgeHtml;
            $html .= '</div>';
            $html .= '<div class="children-container">' . $childrenHtml . '</div>';
            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    public function delete($id, $token) {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            return $this->list();
        }
        $page = new Page();
        $pageItem = $page->create($id);
        if ($page->del($pageItem)) {
            $linkedMenuItems = \Core\Navigation\MenuItem::queryBuilder()
                ->where('type', 'page')
                ->where('target_ref', (string)$id)
                ->get();
            $menuService = \Core\Navigation\MenuService::getInstance();
            foreach ($linkedMenuItems as $mi) {
                $menuService->deleteItem($mi->getId());
            }

            // Réinitialiser le cache de navigation après suppression
            $navigationService = \Core\Navigation\NavigationService::getInstance();
            $navigationService->reset();
            
            Show::msg(Lang::get('core-item-deleted'), 'success');
        } else {
            Show::msg(Lang::get('core-item-not-deleted'), 'error');
        }
        return $this->list();
    }

    public function maintenance($id, $token) {
        $page = new Page();
        $ids = explode(',', $id);
        foreach ($ids as $k => $v) {
            if ($v != '') {
                $pageItem = $page->create($v);
                $page->del($pageItem);
            }
        }
        // Réinitialiser le cache de navigation après maintenance
        $navigationService = \Core\Navigation\NavigationService::getInstance();
        $navigationService->reset();
        
        return $this->list();
    }

    public function pageUp($id, $token) {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            return $this->list();
        }
        $page = new Page();
        $pageItem = $page->create($id);
        $newPos = $pageItem->getPosition() - 1.5;
        $pageItem->setPosition($newPos);
        $page->save($pageItem);
        $page = new Page();
        return $this->list();
    }

    public function pageDown($id, $token) {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            return $this->list();
        }
        $page = new Page();
        $pageItem = $page->create($id);
        $newPos = $pageItem->getPosition() + 1.5;
        $pageItem->setPosition($newPos);
        $page->save($pageItem);
        $page = new Page();
        return $this->list();
    }

    /**
     * AJAX endpoint to list public routes for a specific plugin
     */
    public function listPluginRoutesAjax($plugin)
    {
        $pluginName = $plugin;
        if (empty($pluginName)) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Plugin name required']);
            exit;
        }

        $pluginObj = \Core\Plugin\PluginsManager::getInstance()->getPlugin($pluginName);
        if (!$pluginObj) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Plugin not found']);
            exit;
        }

        $routes = $pluginObj->getPublicRoutes();
        header('Content-Type: application/json');
        echo json_encode($routes);
        exit;
    }

    /**
     * AJAX endpoint to list all available condition providers and their metadata
     */
    public function listProvidersAjax()
    {
        header('Content-Type: application/json');
        
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $registry = \Core\Navigation\ConditionRegistry::getInstance();
        $providers = $registry->getAllProviders();

        $tableParam = $this->request->get('table');
        if ($tableParam) {
            Core::getInstance()->getLogger()->debug("listProvidersAjax: Requested columns for table '{$tableParam}'");
        }

        $metadata = [];
        foreach ($providers as $id => $provider) {
            $meta = $provider->getMetadata();
            
            // Injection dynamique des colonnes si une table est sélectionnée (pour SqlProvider)
            if ($id === 'sql' && $tableParam && method_exists($provider, 'getColumns')) {
                $cols = $provider->getColumns($tableParam);
                Core::getInstance()->getLogger()->debug("listProvidersAjax: Found " . count($cols) . " columns for '{$tableParam}'");
                $meta['fields']['column']['options'] = $cols;
            }
            
            $metadata[$id] = array_merge(
                $meta,
                ['cost' => $provider->getCost()]
            );
        }

        echo json_encode($metadata);
        exit;
    }

    /**
     * AJAX endpoint to explain how a policy would be evaluated for the current user
     */
    public function explainPolicyAjax()
    {
        header('Content-Type: application/json');

        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $conditionsJson = $this->request->post('conditions', '[]');
        $conditions = json_decode($conditionsJson, true);

        if (!is_array($conditions)) {
            echo json_encode(['error' => 'Invalid conditions format']);
            exit;
        }

        $ctx = new \Core\Navigation\ConditionContext(Core::getInstance()->auth()->getCurrentUser());
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $evaluator->clearTrace();
        
        $result = $evaluator->evaluateGroup([
            'logic' => 'AND',
            'conditions' => $conditions
        ], $ctx);

        echo json_encode([
            'allowed' => $result,
            'user_role' => $ctx->getUser() ? $ctx->getUser()->getRole() : 'guest',
            'trace' => $evaluator->getTrace(),
            'timestamp' => time()
        ]);
        exit;
    }

    public function new() {
        $page = new Page();
        $pageItem = new PageItem();
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('page', 'edit');

        $contentEditor = new Editor('pageContent', '', Lang::get('page.content'), true);

        // Active plugins for the "Plugin" type selector
        $plugins = \Core\Plugin\PluginsManager::getInstance()->getPlugins();
        $activePlugins = [];
        foreach ($plugins as $plugin) {
            // Strict check for activation
            if ($plugin->getConfigVal('activate') == 1) {
                $activePlugins[] = [
                    'id' => $plugin->getName(),
                    'name' => $plugin->getInfoVal('name') ?: $plugin->getName(),
                    'icon' => $plugin->getInfoVal('icon')
                ];
            }
        }

        // Hook for extra sections
        $extraData = $this->core->callHook('page:admin-edit-sections', [
            'pageItem' => $pageItem,
            'sections' => [],
            'hiddenFields' => ''
        ]);

        $tpl->set('page', $page);
        $tpl->set('token', $this->user->token);
        $tpl->set('pageItem', $pageItem);
        $tpl->set('contentEditor', $contentEditor);
        $tpl->set('activePlugins', $activePlugins);
        $tpl->set('extraSections', $extraData['sections'] ?? []);
        $tpl->set('extraHiddenFields', $extraData['hiddenFields'] ?? '');
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function newLink() {
        $this->core->redirect($this->router->generate('menu-admin-home'));
    }

    public function newParent() {
        $this->core->redirect($this->router->generate('menu-admin-home'));
    }

    public function edit($id) {
        $page = new Page();
        $pageItem = $page->create($id);
        if (!$pageItem) {
            return $this->list();
        }
        $response = new AdminResponse();
        if ($pageItem->targetIs() === 'url' || $pageItem->targetIs() === 'plugin') {
            $tpl = $response->createPluginTemplate('page', 'edit-link');
        } elseif ($pageItem->targetIs() === 'parent') {
            $tpl = $response->createPluginTemplate('page', 'edit-parent');
        } else {
            $tpl = $response->createPluginTemplate('page', 'edit');
            $contentEditor = new Editor('pageContent', $pageItem->getContent(), Lang::get('page.content'), true);
            $tpl->set('contentEditor', $contentEditor);
        }

        // Hook for extra sections
        $extraData = $this->core->callHook('page:admin-edit-sections', [
            'pageItem' => $pageItem,
            'sections' => [],
            'hiddenFields' => ''
        ]);

        // Récupérer l'icône par défaut du plugin si applicable
        $pluginIcon = '';
        if ($pageItem->targetIs() === 'plugin') {
            $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin($pageItem->getTarget());
            if ($plugin) {
                $pluginIcon = $plugin->getInfoVal('icon') ?: 'fa-solid fa-puzzle-piece';
            }
        }

        // Active plugins for the "Plugin" type selector
        $plugins = \Core\Plugin\PluginsManager::getInstance()->getPlugins();
        $activePlugins = [];
        foreach ($plugins as $plugin) {
            if ($plugin->getConfigVal('activate')) {
                $activePlugins[] = [
                    'id' => $plugin->getName(),
                    'name' => $plugin->getInfoVal('name') ?: $plugin->getName(),
                    'icon' => $plugin->getInfoVal('icon')
                ];
            }
        }

        $tpl->set('page', $page);
        $tpl->set('token', $this->user->token);
        $tpl->set('pageItem', $pageItem);
        $tpl->set('pluginIcon', $pluginIcon);
        $tpl->set('activePlugins', $activePlugins);
        $tpl->set('extraSections', $extraData['sections'] ?? []);
        $tpl->set('extraHiddenFields', $extraData['hiddenFields'] ?? '');
        
        $response->addTemplate($tpl);
        return $response;
    }

    public function saveOrder() {
        // Cette route est une API, toujours retourner du JSON
        header('Content-Type: application/json');
        
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            die();
        }
        
        // Vérifier le token CSRF (ne pas régénérer pour cette route non-critique)
        $csrfToken = $this->request->post('_csrf') ?? null;
        if (!\Core\Security\Csrf::validate($csrfToken, false)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            die();
        }
        
        $fullOrder = json_decode($this->request->post('fullOrder', ''), true);
        \Core\Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_INFO, "PageAdminController::saveOrder - fullOrder received: " . json_encode($fullOrder));
        
        if (!is_array($fullOrder)) {
            $pageOrder = json_decode($this->request->post('pageOrder', ''), true);
            $navOrder = json_decode($this->request->post('navOrder', ''), true);
            $fullOrder = ['main' => []];
            if (is_array($pageOrder)) {
                foreach ($pageOrder as $it) $fullOrder['main'][] = array_merge($it, ['type' => 'page']);
            }
            if (is_array($navOrder)) {
                foreach ($navOrder as $it) $fullOrder['main'][] = array_merge($it, ['type' => 'plugin']);
            }
        }
        
        $success = true;
        $errors = [];
        
        $pagePositions = [];
        $navPositionsMap = [];
        
        $navPositionsFile = DATA_CORE . 'navigation_positions.json';
        if (file_exists($navPositionsFile)) {
            $navPositionsMap = \Utils\Util::readJsonFile($navPositionsFile, true) ?: [];
        }

        $page = new Page();
        $pagesById = [];
        foreach ($page->getItems() as $p) $pagesById[$p->getId()] = $p;

        $processItems = function($items, $parentId = 0) use (&$processItems, &$pagePositions, &$navPositionsMap) {
            foreach ($items as $item) {
                $id = $item['id'];
                $type = $item['type'] ?? 'page';
                $pos = (int)$item['position']; // Position relative dans le niveau actuel
                
                if ($type === 'page') {
                    // Pour les pages, on sauvegarde la position ET le parent
                    $pagePositions[$id] = [
                        'position' => $pos,
                        'parent' => (int)$parentId
                    ];
                } else {
                    // Pour les plugins, on sauvegarde juste la position (parenting non supporté pour l'instant)
                    $navPositionsMap[$id] = $pos;
                }
                
                // Traitement récursif des enfants
                if (!empty($item['children'])) {
                    $processItems($item['children'], $id);
                }
            }
        };

        foreach ($fullOrder as $context => $items) {
            // Le contexte (main, avatar, footer) n'influence pas le parentID ici (toujours 0 à la racine du contexte)
            // Sauf si on veut stocker le contexte ? Non, le contexte est géré par les flags MP/MA/PP.
            // Le tri change juste l'ordre.
            // NOTE: Si un item change de contexte (drag d'un onglet à l'autre), cela devrait être géré par le frontend
            // qui update les flags. Mais ici on traite l'ordre.
            // Si le frontend permet le drag entre contextes, il faut aussi mettre à jour le contexte.
            // Pour l'instant, on suppose que le drag est intra-contexte ou que le frontend gère les appels updateFlags.
            
            $processItems($items, 0);
        }

        // 1. Sauvegarder les positions des pages CMS
        if (!empty($pagePositions)) {
            if (!$page->savePositions($pagePositions)) {
                $success = false;
                $errors[] = 'Failed to save page positions';
            }
        }
        
        // 2. Sauvegarder les positions des items de plugins
        if (!empty($navPositionsMap)) {
            \Core\Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_INFO, "Saving plugin positions to " . $navPositionsFile . ": " . json_encode($navPositionsMap));
            if (!is_dir(DATA_CORE)) {
                @mkdir(DATA_CORE, 0755, true);
            }
            \Utils\Util::writeJsonFile($navPositionsFile, $navPositionsMap);
        } else {
            \Core\Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_WARNING, "navPositionsMap is empty - no plugin positions to save. NavOrder was: " . json_encode($navOrder ?? 'null'));
        }
        
        // Réinitialiser le cache de navigation pour que le nouveau système prenne en compte le nouvel ordre
        $navigationService = \Core\Navigation\NavigationService::getInstance();
        $navigationService->reset();
        
        if ($success) {
            http_response_code(200);
            echo json_encode([
                'success' => true, 
                'message' => Lang::get('page.order-saved'),
                'csrfToken' => \Core\Security\Csrf::token()
            ]);
            die();
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => Lang::get('page.order-not-saved') . ': ' . implode(', ', $errors)]);
            die();
        }
    }

    public function updateVisibilityFlags()
    {
        header('Content-Type: application/json');
        
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            die();
        }
        
        $csrfToken = $this->request->post('_csrf') ?? null;
        if (!\Core\Security\Csrf::validate($csrfToken, false)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            die();
        }
        
        $idRaw = $this->request->post('id', '');
        // Fix: Ne pas forcer le cast en int pour supporter les IDs string (ex: plugins)
        $id = $idRaw;
        
        $page = new \Core\Page\Page();
        // Try to find as a Page first
        $pageItem = $page->create($id);
        
        // If Page::create returns false, OR if the idRaw implies it's not a standard integer page ID (e.g. alphanumeric) but create() happened to match 0 or something
        // Actually Page::create checks ID equality. If $id is 1 and we pass '1', it works.
        // If $idRaw is '699...' and Page::create fails, then $pageItem is false.
        
        $isPluginItem = ($pageItem === false);
        
        if (!$isPluginItem) {
            // It's a Page, $pageItem is loaded
        } else {
            // It might be a plugin item
            // Check if we really want to treat it as such or if it was a genuine missing page
            // But for updateVisibilityFlags, we assume if it's not a Page, it's a Nav Item
        }
        
        // Helper to get bool from post
        $getBool = function($key) {
            $val = $this->request->post($key);
            if ($val === null) return null;
            return (bool)$val;
        };

        $flags = [
            'MP' => $getBool('MP'),
            'MA' => $getBool('MA'),
            'PP' => $getBool('PP'),
            'AUTO' => $getBool('AUTO'),
            'MEM' => $getBool('MEM'),
            'ADM' => $getBool('ADM'),
            'PUB' => $getBool('PUB'),
        ];

        if ($isPluginItem) {
            // Handle plugin item override
            $navService = \Core\Navigation\NavigationService::getInstance();
            $tree = $navService->getTree();
            $navItem = $tree->getItemById($idRaw);
            
            if (!$navItem) {
                $availableIds = array_map(fn($i) => $i->getId(), $tree->getAllItems());
                \Core\Logger::getInstance()->log(\Core\Logger::LEVEL_ERROR, "updateVisibilityFlags: Navigation item not found for ID '{$idRaw}'. Available items: " . implode(', ', $availableIds));
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Navigation item not found']);
                die();
            }
            
            // Generate updated flags based on input + current values
            $currentFlags = $navItem->getVisibilityFlags();
            foreach ($flags as $k => $v) {
                if ($v !== null) $currentFlags[$k] = $v;
            }
            
            // Save override via NavigationService
            if ($navService->saveOverride($idRaw, $currentFlags)) {
                $navService->reset();

                // Calculate isHidden for frontend UI update (0 = Active, 1 = Hidden)
                // Item is ACTIVE if at least one of PUB, MEM, or ADM is checked
                $isActive = ($currentFlags['PUB'] ?? false) || ($currentFlags['MEM'] ?? false) || ($currentFlags['ADM'] ?? false);
                $isHidden = $isActive ? 0 : 1;

                echo json_encode([
                    'success' => true,
                    'isHidden' => $isHidden,
                    'flags' => $currentFlags,
                    'csrfToken' => \Core\Security\Csrf::token()
                ]);
            } else {
                \Core\Logger::getInstance()->log(\Core\Logger::LEVEL_ERROR, "updateVisibilityFlags: Failed to save override for '{$idRaw}'");
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Failed to save navigation override']);
            }
            die();
        }

        // Current state to merge (for PageItem)
        $currentFlags = [
            'MP' => $pageItem->getVisibilityFlag('MP'),
            'MA' => $pageItem->getVisibilityFlag('MA'),
            'PP' => $pageItem->getVisibilityFlag('PP'),
            'AUTO' => $pageItem->getVisibilityFlag('AUTO'),
            'PUB' => $pageItem->getVisibilityFlag('PUB'),
            'MEM' => $pageItem->getVisibilityFlag('MEM'),
            'ADM' => $pageItem->getVisibilityFlag('ADM'),
        ];

        // Merge only provided flags
        foreach ($flags as $k => $v) {
            if ($v !== null) $currentFlags[$k] = $v;
        }

        $pageItem->updateVisibilityFlags($currentFlags);
        
        if ($page->save($pageItem)) {
            // Réinitialiser le cache de navigation
            \Core\Navigation\NavigationService::getInstance()->reset();
            
            echo json_encode([
                'success' => true, 
                'isHidden' => $pageItem->getIsHidden(),
                'flags' => $pageItem->getVisibilityFlags(),
                'csrfToken' => \Core\Security\Csrf::token()
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to save page']);
        }
        die();
    }

    public function toggleGlobalIcons()
    {
        header('Content-Type: application/json');
        
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            die();
        }
        
        $csrfToken = $this->request->post('_csrf') ?? null;
        if (!\Core\Security\Csrf::validate($csrfToken, false)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            die();
        }
        
        $showIcons = intval($this->request->post('showIcons', 0));
        
        $config = $this->core->getconfig();
        $config['showPageIcons'] = ($showIcons > 0);
        
        if ($this->core->saveConfig($config)) {
            // Réinitialiser le cache de navigation
            \Core\Navigation\NavigationService::getInstance()->reset();
            
            echo json_encode([
                'success' => true, 
                'showIcons' => $config['showPageIcons'],
                'csrfToken' => \Core\Security\Csrf::token()
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to save config']);
        }
        die();
    }

    public function save() {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            return $this->list();
        }
        
        // Vérifier le token CSRF
        $csrfToken = $this->request->post('_csrf') ?? null;
        if (!\Core\Security\Csrf::validate($csrfToken)) {
            Show::msg(Lang::get('core-csrf-invalid'), 'error');
            return $this->list();
        }
        
        $page = new Page();
        $contentEditor = new Editor('pageContent', '', Lang::get('page.content'), true);
        $imgId = (isset($_POST['delImg'])) ? '' : ($_REQUEST['imgId'] ?? '');
        // Hook pour le traitement de l'image mise en avant (ex: via plugin galerie)
        $imageData = [
            'pageName' => $_POST['name'] ?? '',
            'file' => $_FILES['file'] ?? null,
            'currentImgId' => $imgId
        ];
        $imageData = $this->core->callHook('page:save-featured-image', $imageData);
        $imgId = $imageData['currentImgId'] ?? $imgId;
        // Récupérer ou créer la page
        $pageId = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $isNewPage = true;
        if ($pageId > 0) {
            $pageItem = $page->create($pageId);
            if ($pageItem) {
                $isNewPage = false;
            } else {
                // La page n'existe pas, créer une nouvelle
                $pageItem = new PageItem();
            }
        } else {
            $pageItem = new PageItem();
        }
        
        // Définir toutes les propriétés éditoriales (la navigation est gérée dans /admin/menus)
        $pageItem->setName($_POST['name'] ?? '');
        if (isset($_POST['position']) && $_POST['position'] !== '') {
            $pageItem->setPosition($_POST['position']);
        } elseif ($isNewPage) {
            $pageItem->setPosition($page->makePosition());
        }
        $pageItem->setIsHomepage((isset($_POST['isHomepage'])) ? 1 : 0);
        $pageItem->setContent($contentEditor->getPostContent());
        $pageItem->setFile($_POST['file'] ?? '');
        $pageItem->setIsHidden((isset($_POST['isHidden']) && (int)$_POST['isHidden'] === 1) ? 1 : 0);
        $pageItem->setMainTitle($_POST['mainTitle'] ?? '');
        $pageItem->setMetaDescriptionTag($_POST['metaDescriptionTag'] ?? '');
        $pageItem->setMetaTitleTag($_POST['metaTitleTag'] ?? '');
        if (isset($_POST['target'])) {
            $pageItem->setTarget($_POST['target']);
        }
        if (isset($_POST['targetAttr'])) {
            $pageItem->setTargetAttr($_POST['targetAttr']);
        }
        $pageItem->setNoIndex((isset($_POST['noIndex'])) ? 1 : 0);
        if (isset($_POST['parent'])) {
            $pageItem->setParent($_POST['parent']);
        }
        if (isset($_POST['cssClass'])) {
            $pageItem->setCssClass($_POST['cssClass']);
        }
        
        // Conserver navContext existant si transmis, sinon défaut 'main'
        if (isset($_POST['navContext'])) {
            $navContext = $_POST['navContext'];
            $pageItem->setNavContext($navContext);
            $pageItem->setNavPosition($navContext === 'footer' ? 'footer' : 'menu');
        }
        
        $pageItem->setLegalType($_POST['legalType'] ?? 'none');
        if (isset($_POST['showIcon'])) {
            $pageItem->setShowIcon((int)$_POST['showIcon'] === 1);
        }
        if (isset($_POST['icon'])) {
            $pageItem->setIcon($_POST['icon']);
        }
        $pageItem->setImg($imgId);
        $conditionsJson = $_POST['conditions'] ?? '[]';
        $conditions = json_decode($conditionsJson, true);
        if (!is_array($conditions)) {
            $conditions = [];
        }
        $pageItem->setConditions($conditions);
        
        if (isset($_POST['_password']) && $_POST['_password'] != '')
            $pageItem->setPassword($_POST['_password']);
        if (isset($_POST['resetPassword']))
            $pageItem->setPassword('');
        
        $saveResult = $page->save($pageItem);
        
        if ($saveResult) {
            if ($isNewPage && $pageItem->targetIs() === 'page' && !empty($_POST['addToMainMenu'])) {
                \Core\Navigation\MenuService::getInstance()->createItem([
                    'location'   => 'main',
                    'type'       => 'page',
                    'target_ref' => (string)$pageItem->getId(),
                    'label'      => $pageItem->getName(),
                    'is_enabled' => 1,
                ]);
            }

            // Réinitialiser le cache de navigation pour que le nouveau système prenne en compte les changements
            $navigationService = \Core\Navigation\NavigationService::getInstance();
            $navigationService->reset();
            
            Show::msg(Lang::get('core-changes-saved'), 'success');
            // Rediriger vers l'édition de la page avec le router
            $this->core->redirect($this->router->generate('page-admin-edit', ['id' => $pageItem->getId()]));
        } else {
            Show::msg(Lang::get('core-changes-not-saved'), 'error');
            // Vérifier les logs pour plus de détails
            $this->logger->error('Page save failed. Check logs for details.');
            // Rediriger vers la liste en cas d'erreur
            $this->core->redirect($this->router->generate('page-admin-home'));
        }
        die();
    }

}