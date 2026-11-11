<?php

namespace Core\Navigation\Controllers;

use Core\Controllers\AdminController;
use Core\Navigation\AccessPolicyEvaluator;
use Core\Navigation\ConditionContext;
use Core\Navigation\GenericResource;
use Core\Navigation\MenuItem;
use Core\Navigation\MenuService;
use Core\Page\Page;
use Core\Responses\AdminResponse;
use Core\Security\Csrf;
use Utils\Show;
use Core\Lang;

defined('ROOT') or exit('Access denied!');

/**
 * Contrôleur d'administration des menus (séparé de la gestion des pages).
 */
class MenuAdminController extends AdminController
{
    private MenuService $menuService;

    public function __construct()
    {
        parent::__construct();
        $evaluator = AccessPolicyEvaluator::getInstance();
        $ctx = new ConditionContext($this->user);
        if (!$evaluator->canAccess(new GenericResource('core:page:manage'), $ctx)) {
            Show::msg(Lang::get('permissions.denied'), 'error');
            $this->core->redirect($this->router->generate('admin'));
        }
        $this->menuService = MenuService::getInstance();
    }

    private function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function verifyApiCsrf(): void
    {
        $token = $this->request->post('_csrf') ?? null;
        if (!Csrf::validate($token, false)) {
            $this->jsonResponse(['success' => false, 'error' => 'Jeton CSRF invalide'], 400);
        }
    }

    public function index(): AdminResponse
    {
        $location = (string)($_GET['location'] ?? 'main');
        if (!in_array($location, MenuItem::VALID_LOCATIONS, true)) {
            $location = 'main';
        }

        $treeNodes = $this->menuService->getLocationTree($location);
        $counts = $this->menuService->getLocationCounts();
        $catalog = $this->menuService->getCatalog();

        $pageManager = new Page();
        $cmsPages = [];
        foreach ($pageManager->getItems() as $p) {
            if ($p->targetIs() !== 'page') {
                continue;
            }
            $cmsPages[] = [
                'id'         => MenuItem::normalizeId($p->getId()),
                'name'       => (string)$p->getName(),
                'isHomepage' => (bool)$p->getIsHomepage(),
                'isHidden'   => (bool)$p->getIsHidden(),
                'url'        => $pageManager->makeUrl($p),
            ];
        }

        $response = new AdminResponse();
        $response->setTitle('<i class="fa-solid fa-bars-staggered"></i> Gestion des Menus');
        $tpl = $response->createPluginTemplate('menu', 'index');
        $tpl->set('activeLocation', $location);
        $tpl->set('counts', $counts);
        $tpl->set('csrfToken', Csrf::token());
        $tpl->set('treeJson', json_encode($treeNodes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE));
        $tpl->set('catalogJson', json_encode($catalog, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE));
        $tpl->set('pagesJson', json_encode($cmsPages, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE));
        $response->addTemplate($tpl);

        return $response;
    }

    public function addItem(): void
    {
        $this->verifyApiCsrf();

        $location = (string)($this->request->post('location', 'main'));
        if (!in_array($location, MenuItem::VALID_LOCATIONS, true)) {
            $location = 'main';
        }

        $itemsPayload = json_decode((string)$this->request->post('items', ''), true);
        if (!is_array($itemsPayload)) {
            $itemsPayload = [[
                'type'        => (string)$this->request->post('type', 'url'),
                'target_ref'  => (string)$this->request->post('target_ref', ''),
                'label'       => (string)$this->request->post('label', ''),
                'icon'        => (string)$this->request->post('icon', ''),
                'target_attr' => (string)$this->request->post('target_attr', '_self'),
                'parent_id'   => MenuItem::normalizeId($this->request->post('parent_id', 0)),
            ]];
        }

        $createdIds = [];
        $skippedCount = 0;
        foreach ($itemsPayload as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $label = trim((string)($entry['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $type = (string)($entry['type'] ?? 'url');
            $targetRef = trim((string)($entry['target_ref'] ?? ''));
            if (in_array($type, ['page', 'plugin'], true) && $targetRef !== '' && $this->menuService->hasTargetInLocation($location, $type, $targetRef)) {
                $skippedCount++;
                continue;
            }
            $created = $this->menuService->createItem([
                'location'    => $location,
                'parent_id'   => MenuItem::normalizeId($entry['parent_id'] ?? 0),
                'type'        => $type,
                'target_ref'  => $targetRef,
                'label'       => $label,
                'icon'        => (string)($entry['icon'] ?? ''),
                'target_attr' => (string)($entry['target_attr'] ?? '_self'),
                'visibility'  => ['mode' => 'public', 'roles' => []],
                'is_enabled'  => 1,
            ]);
            if ($created) {
                $createdIds[] = $created->getId();
            }
        }

        if (empty($createdIds)) {
            if ($skippedCount > 0) {
                $this->jsonResponse([
                    'success'      => false,
                    'error'        => 'Cet élément est déjà présent dans ce menu.',
                    'skippedCount' => $skippedCount,
                ], 409);
            }
            $this->jsonResponse(['success' => false, 'error' => 'Aucun élément valide à ajouter'], 422);
        }

        $this->jsonResponse([
            'success'      => true,
            'createdIds'   => $createdIds,
            'skippedCount' => $skippedCount,
            'tree'         => $this->menuService->getLocationTree($location),
            'counts'       => $this->menuService->getLocationCounts(),
            'csrfToken'    => Csrf::token(),
        ]);
    }

    public function updateItem(): void
    {
        $this->verifyApiCsrf();

        $id = MenuItem::normalizeId($this->request->post('id', 0));
        $item = $id !== 0 ? \Core\Navigation\MenuItem::findPK($id) : null;
        if (!$item) {
            $this->jsonResponse(['success' => false, 'error' => 'Élément introuvable'], 404);
        }

        $data = [
            'label'           => (string)$this->request->post('label', $item->getLabel()),
            'icon'            => (string)$this->request->post('icon', ''),
            'css_class'       => (string)$this->request->post('css_class', ''),
            'target_attr'     => (string)$this->request->post('target_attr', '_self'),
            'visibility_mode' => (string)$this->request->post('visibility_mode', 'public'),
            'is_enabled'      => (int)$this->request->post('is_enabled', 1),
        ];

        if ($item->getType() === 'url') {
            $data['target_ref'] = (string)$this->request->post('target_ref', $item->getTargetRef());
        }

        if (!$this->menuService->updateItem($id, $data)) {
            $this->jsonResponse(['success' => false, 'error' => 'Erreur lors de la mise à jour'], 500);
        }

        $location = $item->getLocation();
        $this->jsonResponse([
            'success'   => true,
            'tree'      => $this->menuService->getLocationTree($location),
            'counts'    => $this->menuService->getLocationCounts(),
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function deleteItem(): void
    {
        $this->verifyApiCsrf();

        $id = MenuItem::normalizeId($this->request->post('id', 0));
        $item = $id !== 0 ? \Core\Navigation\MenuItem::findPK($id) : null;
        if (!$item) {
            $this->jsonResponse(['success' => false, 'error' => 'Élément introuvable'], 404);
        }

        $location = $item->getLocation();
        if (!$this->menuService->deleteItem($id)) {
            $this->jsonResponse(['success' => false, 'error' => 'Suppression impossible'], 500);
        }

        $this->jsonResponse([
            'success'   => true,
            'tree'      => $this->menuService->getLocationTree($location),
            'counts'    => $this->menuService->getLocationCounts(),
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function saveOrder(): void
    {
        $this->verifyApiCsrf();

        $location = (string)$this->request->post('location', 'main');
        if (!in_array($location, MenuItem::VALID_LOCATIONS, true)) {
            $this->jsonResponse(['success' => false, 'error' => 'Emplacement invalide'], 400);
        }

        $order = json_decode((string)$this->request->post('order', '[]'), true);
        if (!is_array($order)) {
            $this->jsonResponse(['success' => false, 'error' => 'Format de tri invalide'], 400);
        }

        if (!$this->menuService->saveOrder($location, $order)) {
            $this->jsonResponse(['success' => false, 'error' => 'Erreur lors de la sauvegarde'], 500);
        }

        $this->jsonResponse([
            'success'   => true,
            'tree'      => $this->menuService->getLocationTree($location),
            'counts'    => $this->menuService->getLocationCounts(),
            'csrfToken' => Csrf::token(),
        ]);
    }
}
