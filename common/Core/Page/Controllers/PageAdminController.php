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
        // Recherche des pages perdues
        $parents = [];
        $lost = '';
        foreach ($page->getItems() as $k => $v)
            if ((int) $v->getParent() == 0) {
                $parents[] = $v->getId();
            }
        foreach ($page->getItems() as $k => $v)
            if ((int) $v->getParent() > 0) {
                if (!in_array($v->getParent(), $parents))
                    $lost .= $v->getId() . ',';
            }
        // Suite...
        if (!$page->createHomepage() && $this->core->getConfigVal('defaultPlugin') === 'page') {
            Show::msg(Lang::get('page.no-homepage-defined'), 'warning');
        }

        $items = $page->getItems();
        
        // Filter out items targeting inactive plugins
        $pluginsManager = \Core\Plugin\PluginsManager::getInstance();
        $items = array_filter($items, function($item) use ($pluginsManager) {
            if ($item->targetIs() === 'plugin') {
                $targetPlugin = $item->getTarget();
                // Handle cases where target might be "plugin:name" or just "name"
                $pluginName = str_replace('plugin:', '', $targetPlugin);
                $p = $pluginsManager->getPlugin($pluginName);
                if (!$p || !$p->getConfigVal('activate')) {
                    return false;
                }
            }
            return true;
        });
        
        $totalPages = count($items);
        $visiblePages = 0;
        $hiddenPages = 0;
        $parentPages = 0;
        foreach ($items as $item) {
            if ($item->getIsHidden()) {
                $hiddenPages++;
            } else {
                $visiblePages++;
            }
            if ((int) $item->getParent() === 0) {
                $parentPages++;
            }
        }
        $childPages = max($totalPages - $parentPages, 0);
        $lostIds = array_filter(array_map('trim', explode(',', $lost)));
        $lostCount = count($lostIds);

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('page', 'list');

        // Passer les traductions en JSON pour éviter les problèmes d'échappement dans le JavaScript
        $translations = [
            'savingOrder' => Lang::get('page.saving-order'),
            'orderSaved' => Lang::get('page.order-saved'),
            'orderNotSaved' => Lang::get('page.order-not-saved'),
            'dragToReorder' => Lang::get('page.drag-to-reorder')
        ];
        $tpl->set('translationsJson', json_encode($translations, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE));

        // Récupérer aussi les items de navigation des plugins
        $navigationService = \Core\Navigation\NavigationService::getInstance();
        $navigationService->reset();
        
        // S'assurer que les pages sont chargées dans Core
        \Core\Page\Page::addToNavigation();
        
        // Construire l'arbre
        $navigationTree = $navigationService->build();
        $allNavItems = $navigationTree->getAllItems();
        
        // Groupage pour les onglets
        $accessibilityGroups = ['active' => [], 'inactive' => []];
        $sortingGroups = ['main' => [], 'avatar' => [], 'footer' => []];
        $listByType = ['pages' => [], 'links' => [], 'folders' => [], 'plugins' => []];
        
        $advancedStats = [
            'totalItems' => 0,
            'realPages' => 0,
            'links' => 0,
            'folders' => 0,
            'plugins' => 0,
            'hidden' => 0,
            'mainLevel' => 0,
            'subItems' => 0,
            'orphans' => $lostCount,
            'menus' => ['main' => 0, 'avatar' => 0, 'footer' => 0]
        ];

        // 1. Traiter les PageItems (CMS)
        $pluginsManager = \Core\Plugin\PluginsManager::getInstance();
        foreach ($page->getItems() as $item) {
            // Fix: Exclure les items ciblant des plugins inactifs
            if ($item->targetIs() === 'plugin' && !$pluginsManager->isActivePlugin($item->getTarget())) {
                continue;
            }

            $wrapper = ['type' => 'page', 'item' => $item];
            $advancedStats['totalItems']++;

            // Icône avec fallback
            $icon = $item->getIcon();
            if (empty($icon) && $item->targetIs() === 'plugin') {
                $p = $pluginsManager->getPlugin($item->getTarget());
                if ($p) $icon = $p->getInfoVal('icon');
            }
            $wrapper['icon'] = $icon ?: 'fa-solid fa-file';

            // Stats par Cible
            $targetType = $item->targetIs();
            if ($targetType === 'page') $advancedStats['realPages']++;
            elseif ($targetType === 'url') $advancedStats['links']++;
            elseif ($targetType === 'parent') $advancedStats['folders']++;
            elseif ($targetType === 'plugin') $advancedStats['plugins']++;

            // Stats Hiérarchie
            if ((int)$item->getParent() === 0) $advancedStats['mainLevel']++;
            else $advancedStats['subItems']++;

            if ($item->getIsHidden()) $advancedStats['hidden']++;

            // Groupage par Type pour "Gestion"
            if ($targetType === 'page') $listByType['pages'][] = $wrapper;
            elseif ($targetType === 'url') $listByType['links'][] = $wrapper;
            elseif ($targetType === 'parent') $listByType['folders'][] = $wrapper;
            elseif ($targetType === 'plugin') $listByType['plugins'][] = $wrapper;
            
            // Groupage Accessibilité
            if ($item->getIsHidden()) {
                $accessibilityGroups['inactive'][] = $wrapper;
            } else {
                $accessibilityGroups['active'][] = $wrapper;
            }

            // Groupage Tri & Stats Menus
            $contextsStr = $item->getNavContext() ?: 'main';
            $contexts = array_map('trim', explode(',', $contextsStr));
            foreach ($contexts as $context) {
                if ($context === 'auto' || $context === 'menu' || $context === 'default') $context = 'main';
                if (isset($advancedStats['menus'][$context])) {
                    $advancedStats['menus'][$context]++;
                }
                if (!$item->getIsHidden() && isset($sortingGroups[$context])) {
                    $sortingGroups[$context][] = $wrapper;
                }
            }
        }

        // Pre-calcul des comptes pour le template (car |count n'est pas supporté)
        $accessibilityGroups['activeCount'] = count($accessibilityGroups['active']);
        $accessibilityGroups['inactiveCount'] = count($accessibilityGroups['inactive']);
        
        $listByType['pagesCount'] = count($listByType['pages']);
        $listByType['linksCount'] = count($listByType['links']);
        $listByType['foldersCount'] = count($listByType['folders']);
        $listByType['pluginsCount'] = 0; // Sera calculé après

        // 2. Traiter les Plugin items
        foreach ($allNavItems as $navItem) {
            $source = $navItem->getSource();
            if (strpos($source, 'plugin:') === 0) {
                // Filter out disabled items (inactive plugins)
                if (!$navItem->isEnabled()) {
                    continue;
                }
                $wrapper = ['type' => 'plugin', 'item' => $navItem];
                $wrapper['icon'] = $navItem->getIcon() ?: 'fa-solid fa-puzzle-piece';
                $advancedStats['totalItems']++;
                $advancedStats['plugins']++;
                $listByType['plugins'][] = $wrapper;
                
                // Plugins: Accessibilité -> Actif par défaut
                $accessibilityGroups['active'][] = $wrapper;

                $context = $navItem->getNavContext() ?: 'main';
                if ($context === 'auto' || $context === 'menu' || $context === 'default') $context = 'main';
                if (isset($advancedStats['menus'][$context])) {
                    $advancedStats['menus'][$context]++;
                }
                if (isset($sortingGroups[$context])) {
                    $sortingGroups[$context][] = $wrapper;
                } else {
                    $sortingGroups['main'][] = $wrapper;
                }
            }
        }
        
        $listByType['pagesCount'] = count($listByType['pages']);
        $listByType['linksCount'] = count($listByType['links']);
        $listByType['foldersCount'] = count($listByType['folders']);
        $listByType['pluginsCount'] = count($listByType['plugins']);
        // Mettre à jour les comptes d'accessibilité car les plugins y sont ajoutés
        $accessibilityGroups['activeCount'] = count($accessibilityGroups['active']);

        // Types à afficher dans l'onglet "Gestion" (uniquement ceux qui ont des items)
        $managedGroups = [];
        $typeMeta = [
            'pages' => ['label' => 'Vraies Pages CMS', 'icon' => 'fa-solid fa-file'],
            'links' => ['label' => 'Liens Externes', 'icon' => 'fa-solid fa-link'],
            'folders' => ['label' => 'Dossiers / Catégories', 'icon' => 'fa-solid fa-folder'],
            'plugins' => ['label' => 'Éléments de Plugins', 'icon' => 'fa-solid fa-plug']
        ];
        foreach ($typeMeta as $tk => $meta) {
            if (count($listByType[$tk]) > 0) {
                // Trier les items par position
                usort($listByType[$tk], function($a, $b) {
                    return $a['item']->getPosition() <=> $b['item']->getPosition();
                });
                
                $managedGroups[] = [
                    'key' => $tk,
                    'label' => $meta['label'],
                    'icon' => $meta['icon'],
                    'items' => $listByType[$tk],
                    'count' => count($listByType[$tk])
                ];
            }
        }
        $tpl->set('managedGroups', $managedGroups);

        // Trier chaque groupe par position
        foreach ($sortingGroups as &$group) {
            usort($group, function($a, $b) {
                return $a['item']->getPosition() <=> $b['item']->getPosition();
            });
        }

        // Optimisation: Pré-indexer les items par Parent ID pour éviter le O(N^2)
        $itemsByParent = [];
        foreach ($page->getItems() as $item) {
            // Fix: Exclure aussi ici les items ciblant des plugins inactifs
            if ($item->targetIs() === 'plugin' && !$pluginsManager->isActivePlugin($item->getTarget())) {
                continue;
            }

            $pid = (string)$item->getParent();
            if ($pid === '0' || $pid === '') $pid = '0'; // Normalize root

            if (!isset($itemsByParent[$pid])) {
                $itemsByParent[$pid] = [];
            }
            $itemsByParent[$pid][] = $item;
        }

        // Fonction récursive optimisée
        $buildTree = function($parentId, $context, $depth = 0) use (&$buildTree, &$itemsByParent, $allNavItems, $pluginsManager) {
            if ($depth > 20) return []; // Safety guard

            $branch = [];
            
            // 1. Pages CMS pour ce parent et contexte
            // On récupère directement les enfants, plus de boucle sur tout le tableau
            $children = $itemsByParent[(string)$parentId] ?? [];
            
            foreach ($children as $item) {
                if (!$item->getIsHidden()) {
                    $contextsStr = $item->getNavContext() ?: 'main';
                    $contexts = array_map('trim', explode(',', $contextsStr));
                    
                    // Si contexte 'auto', 'menu', 'default' -> 'main'
                    $inContext = false;
                    foreach ($contexts as $ctx) {
                        if ($ctx === 'auto' || $ctx === 'menu' || $ctx === 'default') $ctx = 'main';
                        if ($ctx === $context) {
                            $inContext = true;
                            break;
                        }
                    }

                    if ($inContext) {
                        // Icône
                        $icon = $item->getIcon();
                        if (empty($icon) && $item->targetIs() === 'plugin') {
                            $p = $pluginsManager->getPlugin($item->getTarget());
                            if ($p) $icon = $p->getInfoVal('icon') ?: 'fa-solid fa-puzzle-piece';
                        }
                        
                        $node = [
                            'id' => $item->getId(),
                            'type' => 'page',
                            'label' => $item->getName() ?: $item->getLabel(),
                            'icon' => $icon ?: 'fa-solid fa-file',
                            'url' => $item->targetIs() == 'page' ? 'page/edit?id='.$item->getId() : $item->getTarget(), // URL simplifiée pour l'admin
                            'targetType' => $item->targetIs(),
                            'position' => $item->getPosition(),
                            'children' => $buildTree($item->getId(), $context, $depth + 1)
                        ];
                        
                        // Badge Type
                        if ($item->targetIs() === 'plugin') $node['badge'] = 'plugin';
                        elseif ($item->targetIs() === 'parent') $node['badge'] = 'folder';
                        elseif ($item->targetIs() === 'url') $node['badge'] = 'link';
                        else $node['badge'] = 'page';

                        $branch[] = $node;
                    }
                }
            }

            // 2. Plugins (Toujours au niveau racine pour l'instant)
            if ($parentId == 0) {
                foreach ($allNavItems as $navItem) {
                    $source = $navItem->getSource();
                    if (strpos($source, 'plugin:') === 0) {
                        $pContext = $navItem->getNavContext() ?: 'main';
                        if ($pContext === 'auto' || $pContext === 'menu' || $pContext === 'default') $pContext = 'main';
                        
                        if ($pContext === $context) {
                            $node = [
                                'id' => $navItem->getId(),
                                'type' => 'plugin',
                                'label' => $navItem->getLabel(),
                                'icon' => $navItem->getIcon() ?: 'fa-solid fa-puzzle-piece',
                                'url' => $navItem->getTarget(),
                                'targetType' => 'plugin',
                                'position' => $navItem->getPosition(),
                                'badge' => 'plugin',
                                'children' => []
                            ];
                            $branch[] = $node;
                        }
                    }
                }
            }

            // Trier par position
            usort($branch, function($a, $b) {
                return $a['position'] <=> $b['position'];
            });

            return $branch;
        };

        // Initialiser le tableau des arbres
        $sortingTrees = ['main' => [], 'avatar' => [], 'footer' => []];

        // Construire les arbres pour chaque contexte
        foreach (array_keys($sortingTrees) as $ctx) {
            $sortingTrees[$ctx] = $buildTree(0, $ctx, 0);
        }

        // Structure pour le template (similaire à avant mais avec 'tree' au lieu de 'items')
        $sortingGroupsFlat = [
            [
                'key' => 'main',
                'label' => 'Menu Principal',
                'icon' => 'fa-bars',
                'tree' => $sortingTrees['main'],
                'treeHtml' => $this->renderTreeHtml($sortingTrees['main']),
                'count' => count($sortingTrees['main'])
            ],
            [
                'key' => 'avatar',
                'label' => 'Menu Avatar',
                'icon' => 'fa-user-circle',
                'tree' => $sortingTrees['avatar'],
                'treeHtml' => $this->renderTreeHtml($sortingTrees['avatar']),
                'count' => count($sortingTrees['avatar'])
            ],
            [
                'key' => 'footer',
                'label' => 'Pied de page',
                'icon' => 'fa-shoe-prints',
                'tree' => $sortingTrees['footer'],
                'treeHtml' => $this->renderTreeHtml($sortingTrees['footer']),
                'count' => count($sortingTrees['footer'])
            ]
        ];

        $tpl->set('token', $this->user->token);
        $tpl->set('page', $page);
        $tpl->set('lost', $lost);
        $tpl->set('lostCount', $lostCount);
        $tpl->set('pageStats', $advancedStats);
        $tpl->set('accessibilityGroups', $accessibilityGroups);
        $tpl->set('listByType', $listByType);
        $tpl->set('sortingGroups', $sortingGroupsFlat);
        
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
        return $this->new();
    }

    public function newParent() {
        return $this->new();
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
        if ($pageId > 0) {
            $pageItem = $page->create($pageId);
            if (!$pageItem) {
                // La page n'existe pas, créer une nouvelle
                $pageItem = new PageItem();
            }
        } else {
            $pageItem = new PageItem();
        }
        
        // Définir toutes les propriétés
        $pageItem->setName($_POST['name'] ?? '');
        $pageItem->setPosition($_POST['position'] ?? '');
        $pageItem->setIsHomepage((isset($_POST['isHomepage'])) ? 1 : 0);
        $pageItem->setContent($contentEditor->getPostContent());
        $pageItem->setFile($_POST['file'] ?? '');
        $pageItem->setIsHidden((isset($_POST['isHidden']) && (int)$_POST['isHidden'] === 1) ? 1 : 0);
        $pageItem->setMainTitle($_POST['mainTitle'] ?? '');
        $pageItem->setMetaDescriptionTag($_POST['metaDescriptionTag'] ?? '');
        $pageItem->setMetaTitleTag($_POST['metaTitleTag'] ?? '');
        $pageItem->setTarget($_POST['target'] ?? '');
        $pageItem->setTargetAttr($_POST['targetAttr'] ?? '_self');
        $pageItem->setNoIndex((isset($_POST['noIndex'])) ? 1 : 0);
        $pageItem->setParent($_POST['parent'] ?? '');
        $pageItem->setCssClass($_POST['cssClass'] ?? '');
        
        // Navigation Context Engine
        $navContext = $_POST['navContext'] ?? 'main';
        $pageItem->setNavContext($navContext);
        
        // Sync legacy navPosition with navContext for backward compatibility
        $pageItem->setNavPosition($navContext === 'footer' ? 'footer' : 'menu');
        
        $pageItem->setLegalType($_POST['legalType'] ?? 'none');
        $pageItem->setShowIcon(isset($_POST['showIcon']) && (int)$_POST['showIcon'] === 1);
        $pageItem->setIcon($_POST['icon'] ?? '');
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