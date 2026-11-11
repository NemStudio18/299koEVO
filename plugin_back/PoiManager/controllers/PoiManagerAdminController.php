<?php

namespace PoiManager\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Responses\ApiResponse;
use PoiManager\Services\PoiManagerService;
use PoiManager\Services\PoiProposalService;
use PoiManager\Services\PoiSuggestionService;
use PoiManager\Services\PoiPhotoService;

defined('ROOT') or exit('Access denied!');

/**
 * PoiManagerAdminController
 *
 * Gestion admin complète des POIs :
 *  - Dashboard avec stats
 *  - Liste / Création / Édition directe
 *  - Validation des proposals (nouveaux POIs)
 *  - Validation des suggestions (corrections)
 *  - Upload de photos
 *  - Configuration du plugin
 */
class PoiManagerAdminController extends AdminController
{
    private PoiManagerService    $poiService;
    private PoiProposalService   $proposalService;
    private PoiSuggestionService $suggestionService;
    private PoiPhotoService      $photoService;

    private function initServices(): void
    {
        if (!isset($this->poiService)) {
            $this->poiService       = new PoiManagerService();
            $this->proposalService  = new PoiProposalService();
            $this->suggestionService = new PoiSuggestionService();
            $this->photoService     = new PoiPhotoService();
        }
    }


    // =========================================================
    // DASHBOARD
    // =========================================================

    public function dashboard()
    {
        $this->initServices();

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('PoiManager', 'admin_dashboard');

        $tpl->set('stats',              $this->poiService->getStats());
        $tpl->set('pendingProposals',   $this->proposalService->getPending());
        $tpl->set('pendingSuggestions', $this->suggestionService->getPending());

        $response->setTitle('POI Manager — Dashboard');
        $response->addTemplate($tpl);
        return $response;
    }

    // =========================================================
    // LISTE
    // =========================================================

    public function listPois(): AdminResponse
    {
        $this->initServices();

        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 30;
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['q'] ?? '');
        $ownerFilter  = $_GET['owner'] ?? 'all';

        if (!in_array($ownerFilter, ['all', 'claimed', 'unclaimed'])) {
            $ownerFilter = 'all';
        }

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('PoiManager', 'admin_list');

        $tpl->set('pois',      $this->poiService->getList($limit, $offset, $search, $ownerFilter));
        $tpl->set('search',    $search);
        $tpl->set('ownerFilter',$ownerFilter);
        $tpl->set('page',      $page);
        $tpl->set('total',     $this->poiService->countAll($search, $ownerFilter));
        $pages = max(1, (int)ceil($this->poiService->countAll($search, $ownerFilter) / $limit));
        $tpl->set('pages',     $pages);
        $tpl->set('pageRange', range(1, $pages)); // 1..pages non supporté dans le moteur tpl avec variable
        $tpl->set('_csrfToken', \Core\Security\Csrf::token());

        $response->setTitle('POI Manager — Liste des POIs');
        $response->addTemplate($tpl);
        return $response;
    }

    // =========================================================
    // CRÉATION
    // =========================================================

    private function getCategoryData(): array
    {
        if (!class_exists('\SearchEngine\Services\Search\Category\CategoryMappingService')) {
            require_once PLUGINS . 'SearchEngine/services/Search/Category/CategoryMappingService.php';
        }
        if (!class_exists('\PoiData\Services\CategoryLabelTranslator')) {
            require_once PLUGINS . 'PoiData/services/CategoryLabelTranslator.php';
        }
        \Core\Lang::loadLanguageFile(PLUGINS . 'SearchUi/langs/');

        $mappingService = new \SearchEngine\Services\Search\Category\CategoryMappingService();
        $families = $mappingService->getFamilies();
        $translator = new \PoiData\Services\CategoryLabelTranslator();

        $familiesForSelect = [];
        $specialtiesByFamily = [];

        foreach ($families as $key => $f) {
            $familiesForSelect[] = [
                'id' => $key,
                'slug' => $key,
                'label' => \Core\Lang::get($f['label'])
            ];

            $specs = $mappingService->getFiltresForFamily($key);
            $translatedSpecs = [];
            foreach ($specs as $spec) {
                $translatedSpecs[] = [
                    'key' => $spec['key'],
                    'label' => $translator->translate($spec['key'])
                ];
            }
            usort($translatedSpecs, function($a, $b) {
                return strcmp($a['label'], $b['label']);
            });
            $specialtiesByFamily[$key] = $translatedSpecs;
        }

        return [
            'familiesForSelect' => $familiesForSelect,
            'specialtiesByFamilyJson' => json_encode($specialtiesByFamily, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ];
    }

    public function create()
    {
        $this->initServices();
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('PoiManager', 'admin_create');

        $catData = $this->getCategoryData();
        $tpl->set('familiesForSelect', $catData['familiesForSelect']);
        $tpl->set('specialtiesByFamilyJson', $catData['specialtiesByFamilyJson']);

        $tpl->set('poi',        []);
        $tpl->set('poiOpeningHours', []);
        $tpl->set('photos',     []);
        $tpl->set('links',      []);
        $tpl->set('description', null);
        $tpl->set('tags',       []);
        $tpl->set('daysList', [
            ['id' => 1, 'label' => 'Lundi'],
            ['id' => 2, 'label' => 'Mardi'],
            ['id' => 3, 'label' => 'Mercredi'],
            ['id' => 4, 'label' => 'Jeudi'],
            ['id' => 5, 'label' => 'Vendredi'],
            ['id' => 6, 'label' => 'Samedi'],
            ['id' => 7, 'label' => 'Dimanche']
        ]);

        $response->setTitle('POI Manager — Nouveau POI');
        $response->addTemplate($tpl);
        return $response;
    }

    public function createSave()
    {
        $this->initServices();

        $userId = (int)($this->user->attributes['id'] ?? 0);
        $data   = $this->extractPoiFormData($_POST);

        $poiId = $this->poiService->create($data, $userId);
        if (!$poiId) {
            \Utils\Show::msg('Erreur lors de la création du POI.', 'error');
            $this->core->redirect($this->router->generate('admin-poimanager-create'));
            return;
        }

        if (!empty($_POST['description_content'])) {
            $this->poiService->saveDescription($poiId, $_POST['description_content'], $userId);
        }
        if (!empty($_POST['links']) && is_array($_POST['links'])) {
            $this->poiService->saveLinks($poiId, $_POST['links'], $userId);
        }
        if (!empty($_POST['tags']) && is_array($_POST['tags'])) {
            foreach ($_POST['tags'] as $key => $value) {
                if ($key !== '' && $value !== '') {
                    $this->poiService->setTag($poiId, $key, $value, $userId);
                }
            }
        }
                $hours = $_POST['hours'] ?? [];
        $this->poiService->setPoiOpeningHours($poiId, $hours);
        if (isset($_POST['contacts']) && is_array($_POST['contacts'])) {
            $this->poiService->saveContacts($poiId, $_POST['contacts'], $userId);
        }
        if (isset($_POST['features']) && is_array($_POST['features'])) {
            $this->poiService->saveFeatures($poiId, $_POST['features'], $userId);
        }
        if (isset($_POST['cuisines']) && is_array($_POST['cuisines'])) {
            $this->poiService->saveCuisines($poiId, $_POST['cuisines']);
        }

        // Synchronisation PoiSearch
        $this->poiService->syncPoiSearch($poiId);

        $this->core->redirect($this->router->generate('admin-poimanager-edit', ['id' => $poiId]));
    }

    // =========================================================
    // ÉDITION — le paramètre :id de la route est injecté comme argument
    // =========================================================

    public function edit(string $id = '')
    {
        $this->initServices();

        $poi = $this->poiService->getById((int)$id);
        if (!$poi) {
            $this->core->error404();
            return;
        }

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('PoiManager', 'admin_create'); // edit et create partagent le même template

        $catData = $this->getCategoryData();
        $tpl->set('familiesForSelect', $catData['familiesForSelect']);
        $tpl->set('specialtiesByFamilyJson', $catData['specialtiesByFamilyJson']);

        $tpl->set('poi',         $poi);
        $rawHours = $this->poiService->getPoiOpeningHours((int)$poi['id']);
        $poiOpeningHours = [];
        foreach ($rawHours as $idx => $h) {
            $h['uuid'] = 'db_' . $idx; // Garantit un ID unique pour le template
            $poiOpeningHours[] = $h;
        }
        error_log("FINAL HOURS FOR TEMPLATE: " . json_encode($poiOpeningHours));
        $tpl->set('poiOpeningHours', $poiOpeningHours);
        $tpl->set('poiOpeningHoursJson', json_encode($poiOpeningHours, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT));


        $tpl->set('photos',      $this->photoService->getPhotos((int)$poi['id']));
        $tpl->set('links',       $this->poiService->getLinks((int)$poi['id']));
        $tpl->set('description', $this->poiService->getDescription((int)$poi['id']));
        $tpl->set('tags',        $this->poiService->getTags((int)$poi['id']));
        $tpl->set('contacts',    $this->poiService->getContacts((int)$poi['id']));
        $tpl->set('features',    $this->poiService->getFeatures((int)$poi['id']));
        $tpl->set('cuisines',    $this->poiService->getCuisines((int)$poi['id']));
        $tpl->set('categories',  $this->getCategories());
        $tpl->set('daysList', [
            ['id' => 1, 'label' => 'Lundi'],
            ['id' => 2, 'label' => 'Mardi'],
            ['id' => 3, 'label' => 'Mercredi'],
            ['id' => 4, 'label' => 'Jeudi'],
            ['id' => 5, 'label' => 'Vendredi'],
            ['id' => 6, 'label' => 'Samedi'],
            ['id' => 7, 'label' => 'Dimanche']
        ]);

        $response->setTitle('POI Manager — Éditer : ' . htmlspecialchars($poi['name'] ?? ''));
        $response->addTemplate($tpl);
        return $response;
    }

    public function save()
    {
        $this->initServices();

        $userId = (int)($this->user->attributes['id'] ?? 0);
        $poiId  = (int)($_POST['poi_id'] ?? 0);
        $data   = $this->extractPoiFormData($_POST);

        if (!$this->poiService->update($poiId, $data)) {
            \Utils\Show::msg('Erreur lors de la sauvegarde.', 'error');
            $this->core->redirect($this->router->generate('admin-poimanager-edit', ['id' => $poiId]));
            return;
        }

        if (isset($_POST['description_content'])) {
            $this->poiService->saveDescription($poiId, $_POST['description_content'], $userId);
        }
        if (isset($_POST['links']) && is_array($_POST['links'])) {
            $this->poiService->saveLinks($poiId, $_POST['links'], $userId);
        }
        // Toujours vider les tags si on enregistre l'admin (pour permettre la suppression totale)
        $this->poiService->clearTags($poiId);
        if (isset($_POST['tags']) && is_array($_POST['tags'])) {
            $tagsVal = $_POST['tags_val'] ?? [];
            foreach ($_POST['tags'] as $key => $keyName) {
                $val = $tagsVal[$key] ?? '';
                if ($keyName !== '' && $val !== '') {
                    $this->poiService->setTag($poiId, trim($keyName), trim($val), $userId);
                }
            }
        }
                $hours = $_POST['hours'] ?? [];
        $this->poiService->setPoiOpeningHours($poiId, $hours);
        if (isset($_POST['contacts']) && is_array($_POST['contacts'])) {
            $this->poiService->saveContacts($poiId, $_POST['contacts'], $userId);
        }
        if (isset($_POST['features']) && is_array($_POST['features'])) {
            $this->poiService->saveFeatures($poiId, $_POST['features'], $userId);
        }

        $this->poiService->syncPoiSearch($poiId);

        \Utils\Show::msg('POI mis à jour avec succès.', 'success');
        $this->core->redirect($this->router->generate('admin-poimanager-edit', ['id' => $poiId]));
    }

    // =========================================================
    // SUPPRESSION
    // =========================================================

    public function delete(string $id = '')
    {
        $this->initServices();
        
        $poiId = (int)$id;
        if (!$poiId) {
            \Utils\Show::msg('ID de POI invalide.', 'error');
            $this->core->redirect($this->router->generate('admin-poimanager'));
            return;
        }

        if ($this->poiService->delete($poiId)) {
            \Utils\Show::msg('POI supprimé avec succès.', 'success');
        } else {
            \Utils\Show::msg('Erreur lors de la suppression du POI.', 'error');
        }

        $this->core->redirect($this->router->generate('admin-poimanager'));
    }

    // =========================================================
    // PHOTOS (admin)
    // =========================================================

    public function uploadPhoto()
    {
        $this->initServices();

        $poiId = (int)($_POST['poi_id'] ?? 0);
        if (!$poiId || empty($_FILES['photo'])) {
            $response = new ApiResponse();
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
            $response->body = ['success' => false, 'message' => 'Données manquantes.'];
            return $response;
        }

        $userId  = (int)($this->user->attributes['id'] ?? 0);
        $caption = trim($_POST['caption'] ?? '');
        $result  = $this->photoService->upload($poiId, $_FILES['photo'], $userId, $caption);

        $response = new ApiResponse();
        $response->status = ApiResponse::STATUS_SUCCESS;
        $response->body = $result;
        return $response;
    }

    public function deletePhoto(string $id = '')
    {
        $this->initServices();

        $photoId = (int)$id ?: (int)($_POST['photo_id'] ?? 0);
        $poiId   = (int)($_POST['poi_id'] ?? 0);

        $response = new ApiResponse();
        $response->status = ApiResponse::STATUS_SUCCESS;
        $response->body = ['success' => $this->photoService->delete($photoId, $poiId)];
        return $response;
    }

    public function updatePhotoCaption()
    {
        $this->initServices();

        $photoId = (int)($_POST['photo_id'] ?? 0);
        $poiId   = (int)($_POST['poi_id'] ?? 0);
        $caption = trim($_POST['caption'] ?? '');

        $response = new ApiResponse();
        $response->status = ApiResponse::STATUS_SUCCESS;
        $response->body = ['success' => $this->photoService->updateCaption($photoId, $poiId, $caption)];
        return $response;
    }

    // =========================================================
    // PROPOSALS
    // =========================================================

    public function proposals()
    {
        $this->initServices();

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('PoiManager', 'admin_proposals');

        $tpl->set('proposals', $this->proposalService->getAll());

        $response->setTitle('POI Manager — Propositions');
        $response->addTemplate($tpl);
        return $response;
    }

    public function decideProposal()
    {
        $this->initServices();

        $proposalId = (int)($_POST['proposal_id'] ?? 0);
        $decision   = $_POST['decision'] ?? '';
        $reason     = trim($_POST['reason'] ?? '');
        $adminId    = (int)($this->user->attributes['id'] ?? 0);

        if ($decision === 'approve') {
            $newPoiId = $this->proposalService->approve($proposalId, $adminId);
            $msg = $newPoiId
                ? 'Proposition approuvée. POI créé avec l\'ID ' . $newPoiId . '.'
                : 'Erreur lors de l\'approbation.';
        } elseif ($decision === 'reject') {
            $this->proposalService->reject($proposalId, $adminId, $reason);
            $msg = 'Proposition rejetée.';
        } else {
            $msg = 'Décision invalide.';
        }

        \Utils\Show::msg($msg, 'info');
        $this->core->redirect($this->router->generate('admin-poimanager-proposals'));
    }

    // =========================================================
    // SUGGESTIONS
    // =========================================================

    public function suggestions()
    {
        $this->initServices();

        $pending = $this->suggestionService->getPending();

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('PoiManager', 'admin_suggestions');

        $tpl->set('grouped', $this->suggestionService->getPendingGroupedByPoi());
        $tpl->set('count',   count($pending));

        $response->setTitle('POI Manager — Suggestions de corrections');
        $response->addTemplate($tpl);
        return $response;
    }

    public function decideSuggestion()
    {
        $this->initServices();

        $suggestionId = (int)($_POST['suggestion_id'] ?? 0);
        $poiId        = (int)($_POST['poi_id'] ?? 0);
        $decision     = $_POST['decision'] ?? '';
        $adminId      = (int)($this->user->attributes['id'] ?? 0);

        if ($decision === 'approve_all' && $poiId) {
            $count = $this->suggestionService->approveAllForPoi($poiId, $adminId);
            $msg = "$count correction(s) appliquée(s).";
        } elseif ($decision === 'approve' && $suggestionId) {
            $this->suggestionService->approve($suggestionId, $adminId);
            $msg = 'Correction appliquée.';
        } elseif ($decision === 'reject' && $suggestionId) {
            $this->suggestionService->reject($suggestionId, $adminId);
            $msg = 'Suggestion rejetée.';
        } else {
            $msg = 'Décision invalide.';
        }

        \Utils\Show::msg($msg, 'info');
        $this->core->redirect($this->router->generate('admin-poimanager-suggestions'));
    }

    // =========================================================
    // HISTORIQUE
    // =========================================================

    public function history()
    {
        $this->initServices();

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('PoiManager', 'admin_history');

        $tpl->set('recentProposals', $this->proposalService->getHistory(50));
        $tpl->set('recentSuggestions', $this->suggestionService->getHistory(50));

        $response->setTitle('POI Manager — Historique');
        $response->addTemplate($tpl);
        return $response;
    }

    // =========================================================
    // PHOTOS VALIDATION
    // =========================================================

    public function photosValidation()
    {
        $this->initServices();

        $pdo = \Core\Database::getInstance()->getPdo();
        // Récupérer les photos en attente avec info POI et User
        $stmt = $pdo->query('
            SELECT p.*, poi.name as poi_name, u.email as user_email
            FROM poi_photos p
            JOIN poi_public poi ON p.poi_id = poi.id
            LEFT JOIN users u ON p.uploaded_by = u.id
            WHERE p.status = "pending"
            ORDER BY p.created_at ASC
        ');
        $pending = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('PoiManager', 'admin_photos_validation');

        $tpl->set('photos', $pending);

        $response->setTitle('POI Manager — Validation Photos');
        $response->addTemplate($tpl);
        return $response;
    }

    public function decidePhoto()
    {
        $this->initServices();

        $photoId  = (int)($_POST['photo_id'] ?? 0);
        $decision = $_POST['decision'] ?? '';
        $adminId  = (int)($this->user->attributes['id'] ?? 0);

        if (!$photoId) {
            $this->core->error404();
            return;
        }

        $pdo = \Core\Database::getInstance()->getPdo();
        
        // Fetch to get user_id and poi_id for hooks
        $stmt = $pdo->prepare('SELECT * FROM poi_photos WHERE id = ?');
        $stmt->execute([$photoId]);
        $photo = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($decision === 'approve') {
            $pdo->prepare('UPDATE poi_photos SET status = "approved" WHERE id = ?')->execute([$photoId]);
            
            // Hook pour gamification
            \Core\Core::getInstance()->callHook('poi-edit:contribution-accepted', [
                'user_id' => $photo['uploaded_by'],
                'poi_id' => $photo['poi_id'],
                'type' => 'photo'
            ]);

            // Recalculate datascore
            $this->poiService->updateDatascore($photo['poi_id']);
            
            \Utils\Show::msg('Photo approuvée.', 'success');
        } elseif ($decision === 'reject') {
            // On peut soit supprimer, soit marquer rejeté. Ici on supprime pour nettoyer l'upload.
            $this->photoService->delete($photoId, (int)$photo['poi_id']);
            \Utils\Show::msg('Photo rejetée et supprimée.', 'info');
        }

        $this->core->redirect($this->router->generate('admin-poimanager-photos-validation'));
    }

    public function setPhotoAsCover()
    {
        $this->initServices();
        $photoId = (int)($_POST['photo_id'] ?? 0);
        $poiId   = (int)($_POST['poi_id'] ?? 0);

        if (!$photoId || !$poiId) {
            $response = new ApiResponse();
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
            $response->body = ['success' => false, 'message' => 'Paramètres invalides.'];
            return $response;
        }

        $success = $this->photoService->setAsCover($photoId, $poiId);

        $response = new ApiResponse();
        $response->body = ['success' => $success];
        return $response;
    }

    // =========================================================
    // CONFIGURATION
    // =========================================================

    public function config()
    {

        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('PoiManager');

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('PoiManager', 'admin_config');

        $tpl->set('limits',        $plugin ? ($plugin->getConfigVal('limits') ?? []) : []);
        $tpl->set('notifications', $plugin ? ($plugin->getConfigVal('notifications') ?? []) : []);

        $response->setTitle('POI Manager — Configuration');
        $response->addTemplate($tpl);
        return $response;
    }

    public function configSave()
    {
        \Utils\Show::msg('Configuration sauvegardée.', 'success');
        $this->core->redirect($this->router->generate('admin-poimanager-config'));
    }

    // =========================================================
    // HELPERS INTERNES
    // =========================================================

    private function extractPoiFormData(array $post): array
    {
        $rawSubcat = $post['subcategory'] ?? '';
        $subcatArray = is_array($rawSubcat) ? $rawSubcat : [$rawSubcat];
        $finalSubcats = [];

        foreach ($subcatArray as $s) {
            $s = trim($s);
            if ($s === '__custom__') {
                $custom = trim($post['subcategory_custom'] ?? '');
                if ($custom !== '') {
                    $finalSubcats[] = $custom;
                }
            } elseif ($s !== '') {
                $finalSubcats[] = $s;
            }
        }
        $subcat = implode(',', $finalSubcats);

        $data = [
            'name'             => trim($post['name'] ?? '') ?: null,
            'brand'            => trim($post['brand'] ?? '') ?: null,
            'desc_list'        => trim($post['desc_list'] ?? '') ?: null,
            'category'         => trim($post['category'] ?? '') ?: null,
            'subcategory'      => $subcat ?: null,
            'addr_housenumber' => trim($post['addr_housenumber'] ?? '') ?: null,
            'addr_street'      => trim($post['addr_street'] ?? '') ?: null,
            'addr_postcode'    => trim($post['addr_postcode'] ?? '') ?: null,
            'addr_city'        => trim($post['addr_city'] ?? '') ?: null,
            'status'           => in_array($post['status'] ?? '', ['public', 'draft', 'private']) ? $post['status'] : 'draft',
            'datascore'        => isset($post['datascore']) ? (int)$post['datascore'] : null,
            'lat'              => isset($post['lat']) && is_numeric($post['lat']) ? (float)$post['lat'] : null,
            'lon'              => isset($post['lon']) && is_numeric($post['lon']) ? (float)$post['lon'] : null,
        ];

        $data['is_closed'] = !empty($post['is_closed']) ? 1 : 0;
        if ($data['is_closed'] && !empty($post['closed_reason'])) {
            $data['closed_reason'] = $post['closed_reason'];
        }

        return $data;
    }

    private function getCategories(): array
    {
        try {
            $pdo  = \Core\Database::getInstance()->getPdo();
            $stmt = $pdo->query('SELECT DISTINCT category FROM poi_public WHERE category IS NOT NULL ORDER BY category ASC');
            return $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            return [];
        }
    }
}
