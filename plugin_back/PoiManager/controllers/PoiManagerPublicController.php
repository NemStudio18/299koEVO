<?php

namespace PoiManager\Controllers;

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Core\Responses\ApiResponse;
use PoiManager\Services\PoiManagerService;
use PoiManager\Services\PoiProposalService;
use PoiManager\Services\PoiSuggestionService;
use PoiManager\Services\PoiPhotoService;
use PoiManager\Services\OwnerAccessService;

defined('ROOT') or exit('Access denied!');

/**
 * PoiManagerPublicController
 *
 * Interface publique :
 *  - Propriétaire : voir ses POIs, les modifier, uploader des photos
 *  - Membre : proposer un nouveau POI (proposal), suggérer une correction (suggestion)
 */
class PoiManagerPublicController extends PublicController
{
    private PoiManagerService    $poiService;
    private PoiProposalService   $proposalService;
    private PoiSuggestionService $suggestionService;
    private PoiPhotoService      $photoService;
    private OwnerAccessService   $accessService;

    private function initServices(): void
    {
        if (!isset($this->poiService)) {
            $this->poiService        = new PoiManagerService();
            $this->proposalService   = new PoiProposalService();
            $this->suggestionService = new PoiSuggestionService();
            $this->photoService      = new PoiPhotoService();
            $this->accessService     = new OwnerAccessService();
        }
    }

    private function requireAuth(): void
    {
        if (!$this->user) {
            $siteUrl = rtrim($this->core->getConfigVal('siteUrl'), '/');
            $this->core->redirect($siteUrl . '/login?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/'));
        }
    }

    private function getUserId(): int
    {
        return (int)($this->user->attributes['id'] ?? 0);
    }

    // =========================================================
    // DASHBOARD PROPRIÉTAIRE
    // =========================================================

    public function myPois()
    {
        $this->requireAuth();
        $this->initServices();

        $userId = $this->getUserId();
        $plan   = $this->accessService->getPlan($userId);

        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('PoiManager', 'public_my_pois');

        $tpl->set('pois', $this->poiService->getByOwner($userId));
        $tpl->set('plan', $plan);
        $tpl->set('user', $this->user);

        $response->setTitle('Mes fiches POI');
        $response->addTemplate($tpl);
        return $response;
    }

    // =========================================================
    // ÉDITION PROPRIÉTAIRE — :id injecté comme argument
    // =========================================================

    public function ownerEdit($id = '')
    {
        // Robust ID extraction
        if (is_string($id) && strpos($id, '/') !== false) {
            $parts = explode('/', trim($id, '/'));
            $id = end($parts);
        }
        $id = (int)$id;

        $this->requireAuth();
        $this->initServices();

        $poi    = $this->poiService->getById((int)$id);
        $userId = $this->getUserId();

        if (!$poi || !$this->accessService->isPrimaryOwner($userId, (int)$poi['id'])) {
            $this->core->error404();
            return;
        }

        $quota = $this->accessService->getQuotaSummary($userId, (int)$poi['id']);

        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('PoiManager', 'public_edit');

        $tpl->set('poi',         $poi);
        $rawHours = $this->poiService->getPoiOpeningHours((int)$poi['id']);
        $poiOpeningHours = [];
        foreach ($rawHours as $idx => $h) {
            $h['uuid'] = 'db_' . $idx;
            $poiOpeningHours[] = $h;
        }
        $tpl->set('poiOpeningHours', $poiOpeningHours);
        $tpl->set('poiOpeningHoursJson', json_encode($poiOpeningHours, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT));
        $tpl->set('photos',      $this->photoService->getPhotos((int)$poi['id']));
        $tpl->set('links',       $this->poiService->getLinks((int)$poi['id']));
        $tpl->set('description', $this->poiService->getDescription((int)$poi['id']));
        $tpl->set('contacts',    $this->poiService->getContacts((int)$poi['id']));
        $tpl->set('features',    $this->poiService->getFeatures((int)$poi['id']));
        $tpl->set('quota',       $quota);

        $response->setTitle('Modifier ma fiche : ' . htmlspecialchars($poi['name'] ?? ''));
        $response->addTemplate($tpl);
        return $response;
    }

    public function ownerSave()
    {
        $this->requireAuth();
        $this->initServices();

        $userId = $this->getUserId();
        $poiId  = (int)($_POST['poi_id'] ?? 0);

        if (!$this->accessService->isPrimaryOwner($userId, $poiId)) {
            $this->core->error404();
            return;
        }

        $plan = $this->accessService->getPlan($userId);
        $shortLimit = $this->accessService->getMaxShortDescLimit();
        $longLimit  = $this->accessService->getMaxLongDescLimit($plan);

        // Champs éditables par le propriétaire: contacts, horaires, équipements et micro-description
        $hours = $_POST['hours'] ?? [];
        $this->poiService->setPoiOpeningHours($poiId, $hours);
        if (isset($_POST['contacts']) && is_array($_POST['contacts'])) {
            $this->poiService->saveContacts($poiId, $_POST['contacts'], $userId);
        }
        if (isset($_POST['features']) && is_array($_POST['features'])) {
            $allowedFeatures = array_intersect_key($_POST['features'], array_flip(['wheelchair', 'internet_access', 'parking', 'fee']));
            $this->poiService->saveFeatures($poiId, $allowedFeatures, $userId);
        }
        
        $poiData = [];
        if (isset($_POST['desc_list'])) {
            $shortDesc = trim($_POST['desc_list']);
            $poiData['desc_list'] = mb_substr($shortDesc, 0, $shortLimit);
        }

        // Description longue (si fournie)
        if (isset($_POST['description'])) {
            $longDesc = trim($_POST['description']);
            if ($longLimit > 0) {
                $longDesc = mb_substr($longDesc, 0, $longLimit);
            }
            $this->poiService->saveDescription($poiId, $longDesc, $userId);
        }

        // Update updated_at AND sync to search
        $this->poiService->update($poiId, $poiData);
        $this->poiService->syncPoiSearch($poiId);

        // Liens dans la limite du quota
        if (!empty($_POST['links']) && is_array($_POST['links'])) {
            $plan  = $this->accessService->getPlan($userId);
            $max   = $this->accessService->getMaxLinks($plan);
            $links = array_slice($_POST['links'], 0, $max);
            $this->poiService->saveLinks($poiId, $links, $userId);
        }

        \Utils\Show::msg('Fiche mise à jour.', 'success');
        $this->core->redirect($this->router->generate('poimanager-owner-edit', ['id' => $poiId]));
    }

    // =========================================================
    // UPLOAD PHOTO (propriétaire)
    // =========================================================

    public function ownerUploadPhoto()
    {
        $this->requireAuth();
        $this->initServices();

        $userId = $this->getUserId();
        $poiId  = (int)($_POST['poi_id'] ?? 0);

        if (!$this->accessService->isPrimaryOwner($userId, $poiId)) {
            $response = new ApiResponse();
            $response->status = ApiResponse::STATUS_FORBIDDEN;
            $response->body = ['success' => false, 'message' => 'Accès refusé.'];
            return $response;
        }

        if (!$this->accessService->canUploadPhoto($userId, $poiId)) {
            $plan = $this->accessService->getPlan($userId);
            $max  = $this->accessService->getMaxPhotos($plan);
            $response = new ApiResponse();
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
            $response->body = ['success' => false, 'message' => "Quota atteint. Plan " . strtoupper($plan) . " : $max photos max."];
            return $response;
        }

        if (empty($_FILES['photo'])) {
            $response = new ApiResponse();
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
            $response->body = ['success' => false, 'message' => 'Aucune photo reçue.'];
            return $response;
        }

        $caption = trim($_POST['caption'] ?? '');
        $result  = $this->photoService->upload($poiId, $_FILES['photo'], $userId, $caption);

        $response = new ApiResponse();
        $response->status = ApiResponse::STATUS_SUCCESS;
        $response->body = $result;
        return $response;
    }

    public function ownerSetPhotoAsCover()
    {
        $this->requireAuth();
        $this->initServices();

        $userId  = $this->getUserId();
        $photoId = (int)($_POST['photo_id'] ?? 0);
        $poiId   = (int)($_POST['poi_id'] ?? 0);

        if (!$this->accessService->isPrimaryOwner($userId, $poiId)) {
            $response = new ApiResponse();
            $response->status = ApiResponse::STATUS_FORBIDDEN;
            $response->body = ['success' => false, 'message' => 'Accès refusé.'];
            return $response;
        }

        $success = $this->photoService->setAsCover($photoId, $poiId);

        $response = new ApiResponse();
        $response->body = ['success' => $success];
        return $response;
    }

    public function ownerDeletePhoto()
    {
        $this->requireAuth();
        $this->initServices();

        $userId  = $this->getUserId();
        $photoId = (int)($_POST['photo_id'] ?? 0);
        $poiId   = (int)($_POST['poi_id'] ?? 0);

        if (!$this->accessService->isPrimaryOwner($userId, $poiId)) {
            $response = new ApiResponse();
            $response->status = ApiResponse::STATUS_FORBIDDEN;
            $response->body = ['success' => false, 'message' => 'Accès refusé.'];
            return $response;
        }

        $success = $this->photoService->delete($photoId, $poiId);

        $response = new ApiResponse();
        $response->body = ['success' => $success];
        return $response;
    }

    public function suggestPhotoUpload()
    {
        $this->requireAuth();
        $this->initServices();

        $userId = $this->getUserId();
        $poiId  = (int)($_POST['poi_id'] ?? 0);

        if (!$poiId) {
            $response = new ApiResponse();
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
            $response->body = ['success' => false, 'message' => 'POI invalide.'];
            return $response;
        }

        if (empty($_FILES['photo'])) {
            $response = new ApiResponse();
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
            $response->body = ['success' => false, 'message' => 'Aucune photo reçue.'];
            return $response;
        }

        // Un membre classique peut uploader X photos selon son niveau de gamification (ou son plan si proprio)
        $limit = $this->accessService->getMemberPhotoLimit($userId, $poiId);
        
        if ($limit <= 0) {
            $response = new ApiResponse();
            $response->status = ApiResponse::STATUS_FORBIDDEN;
            $response->body = ['success' => false, 'message' => "Votre niveau de progression ne vous permet pas encore d'ajouter des photos."];
            return $response;
        }

        // Compter les photos déjà envoyées (pending ou approved) par cet utilisateur pour ce POI
        $pdo = \Core\Database::getInstance()->getPdo();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM poi_photos WHERE poi_id = ? AND uploaded_by = ?');
        $stmt->execute([$poiId, $userId]);
        $currentCount = (int)$stmt->fetchColumn();

        if ($currentCount >= $limit) {
            $response = new ApiResponse();
            $response->status = ApiResponse::STATUS_BAD_REQUEST;
            $response->body = ['success' => false, 'message' => "Vous avez atteint votre limite de $limit photo(s) pour cet établissement."];
            return $response;
        }

        $caption = trim($_POST['caption'] ?? '');
        // Upload avec status 'pending'
        $result = $this->photoService->upload($poiId, $_FILES['photo'], $userId, $caption, 'pending');

        if ($result['success']) {
            $result['message'] = "Votre photo a été envoyée. Elle sera visible après validation par un administrateur.";
            
            // Hook pour gamification
            \Core\Core::getInstance()->callHook('poi:suggestion-submitted', [
                'user_id' => $userId,
                'poi_id' => $poiId,
                'count' => 1,
                'type' => 'photo'
            ]);
        }

        $response = new ApiResponse();
        $response->status = ApiResponse::STATUS_SUCCESS;
        $response->body = $result;
        return $response;
    }

    // =========================================================
    // PROPOSAL : proposer un nouveau POI (membre)
    // =========================================================

    public function proposeForm()
    {
        $this->requireAuth();
        $this->initServices();

        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('PoiManager', 'public_propose');

        $tpl->set('categories', $this->getCategories());

        $response->setTitle('Proposer un nouveau POI');
        $response->addTemplate($tpl);
        return $response;
    }

    public function proposeSave()
    {
        $this->requireAuth();
        $this->initServices();

        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            \Utils\Show::msg('Le nom est obligatoire.', 'error');
            $this->core->redirect($this->router->generate('poimanager-propose'));
            return;
        }

        // Gestion du justificatif de propriété
        $ownerProofPath = null;
        if (!empty($_FILES['owner_proof']['name'])) {
            $uploadDir = UPLOAD . 'proposals' . DS;
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            $ext = pathinfo($_FILES['owner_proof']['name'], PATHINFO_EXTENSION);
            $filename = 'proof_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['owner_proof']['tmp_name'], $uploadDir . $filename)) {
                $ownerProofPath = 'data/upload/proposals/' . $filename;
            }
        }

        $data = $_POST;
        $data['owner_proof'] = $ownerProofPath;
        $id = $this->proposalService->submit($data, $this->getUserId());

        if ($id) {
            \Utils\Show::msg('Votre proposition a été envoyée. Un administrateur la validera prochainement. Merci !', 'success');
        } else {
            \Utils\Show::msg('Une erreur est survenue, veuillez réessayer.', 'error');
        }
        $this->core->redirect($this->router->generate('poimanager-propose'));
    }

    // =========================================================
    // SUGGESTION : corriger un POI existant (membre) — :id injecté
    // =========================================================

    public function suggestForm($id = '')
    {
        // Robust ID extraction
        if (is_string($id) && strpos($id, '/') !== false) {
            $parts = explode('/', trim($id, '/'));
            $id = end($parts);
        }
        $id = (int)$id;

        $this->requireAuth();
        $this->initServices();
        $userId = $this->getUserId();

        $poi = $this->poiService->getById((int)$id);
        if (!$poi) {
            $this->core->error404();
            return;
        }

        // --- Category & Specialty Data ---
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

        // --- Opening Hours Data ---
        $rawHours = $this->poiService->getPoiOpeningHours((int)$poi['id']);
        $poiOpeningHours = [];
        foreach ($rawHours as $idx => $h) {
            $h['uuid'] = 'db_' . $idx;
            $poiOpeningHours[] = $h;
        }

        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('PoiManager', 'public_suggest');

        $tpl->set('poi', $poi);
        $tpl->set('familiesForSelect', $familiesForSelect);
        $tpl->set('specialtiesByFamilyJson', json_encode($specialtiesByFamily, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $tpl->set('poiOpeningHours', $poiOpeningHours);
        $tpl->set('poiOpeningHoursJson', json_encode($poiOpeningHours));
        
        // Quotas pour suggestions membres
        $tpl->set('shortDescLimit', $this->accessService->getMaxShortDescLimit());
        $tpl->set('isOwner', $this->accessService->isPrimaryOwner($userId, (int)$poi['id']));
        $tpl->set('photoLimit', $this->accessService->getMemberPhotoLimit($userId));

        $response->setTitle('Suggérer une correction : ' . htmlspecialchars($poi['name'] ?? ''));
        $response->addTemplate($tpl);
        return $response;
    }

    public function suggestSave()
    {
        $this->requireAuth();
        $this->initServices();

        if (!\Core\Security\Csrf::validate($_POST['csrf_token'] ?? '')) {
            \Utils\Show::msg('Session expirée ou invalide. Veuillez réessayer.', 'error');
            $this->core->redirect($this->router->generate('home'));
            return;
        }
        $poiId  = (int)($_POST['poi_id'] ?? 0);
        $userId = $this->getUserId();

        // Filtrer les champs prefixés par 'field_'
        $fields = [];
        $shortLimit = $this->accessService->getMaxShortDescLimit();

        foreach ($_POST as $key => $value) {
            if (str_starts_with($key, 'field_') && $value !== '' && $key !== 'field_opening_hours_struct') {
                $fieldKey = substr($key, 6);
                
                // Restriction pour les non-propriétaires : uniquement description courte
                if (!$this->accessService->isPrimaryOwner($userId, $poiId)) {
                    if (!in_array($fieldKey, ['desc_list', 'name', 'contact_phone', 'contact_email', 'website', 'addr_street', 'addr_city', 'opening_hours'])) {
                        continue;
                    }
                }

                if ($fieldKey === 'desc_list') {
                    $value = mb_substr(trim($value), 0, $shortLimit);
                }

                $fields[$fieldKey] = $value;
            }
        }

        // Handle structured opening hours
        if (isset($_POST['field_opening_hours_struct']) && is_array($_POST['field_opening_hours_struct'])) {
            $structured = $_POST['field_opening_hours_struct'];
            $flat = [];
            foreach ($structured as $dayId => $slots) {
                foreach ($slots as $slot) {
                    $flat[] = [
                        'day_of_week' => (int)$dayId - 1,
                        'open_time'   => $slot['open_time'] ?? null,
                        'close_time'  => $slot['close_time'] ?? null,
                        'is_closed'   => ($slot['is_closed'] ?? '0') === '1'
                    ];
                }
            }
            
            require_once PLUGINS . 'PoiData/services/OpeningHoursFormatter.php';
            $formatter = new \PoiData\Services\OpeningHoursFormatter();
            $formattedArr = $formatter->format($flat);
            $fields['opening_hours'] = implode(', ', $formattedArr);
        }

        if (empty($fields)) {
            \Utils\Show::msg('Aucune modification détectée.', 'error');
            $this->core->redirect($this->router->generate('poimanager-suggest', ['id' => $poiId]));
            return;
        }

        $count = $this->suggestionService->submit($poiId, $fields, $userId);

        if ($count > 0) {
            \Utils\Show::msg("$count suggestion(s) envoyée(s). Merci pour votre contribution !", 'success');
        } else {
            \Utils\Show::msg('Aucune modification valide détectée.', 'error');
        }
        $this->core->redirect($this->router->generate('poimanager-suggest', ['id' => $poiId]));
    }

    // =========================================================
    // HELPERS
    // =========================================================

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
