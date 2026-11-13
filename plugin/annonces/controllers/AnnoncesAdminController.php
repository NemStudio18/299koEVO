<?php

namespace Annonces\Controllers;

use Annonces\Entities\Annonce;
use Annonces\Entities\AnnonceReservation;
use Core\Controllers\AdminController;
use Core\Lang;
use Core\Navigation\AccessPolicyEvaluator;
use Core\Navigation\ConditionContext;
use Core\Navigation\GenericResource;
use Core\Responses\AdminResponse;
use Core\Security\Csrf;
use Utils\Show;

defined('ROOT') OR exit('Access denied!');

class AnnoncesAdminController extends AdminController
{
    private function checkAdminAccess(): bool
    {
        $evaluator = AccessPolicyEvaluator::getInstance();
        $ctx = new ConditionContext($this->user);
        if (!$evaluator->canAccess(new GenericResource('annonces:admin'), $ctx)) {
            $this->core->error404();
            return false;
        }
        return true;
    }

    public function home()
    {
        if (!$this->checkAdminAccess()) {
            return null;
        }

        $allAnnonces = Annonce::all();
        usort($allAnnonces, static function (Annonce $a, Annonce $b): int {
            return strcmp((string) ($b->attributes['created_at'] ?? ''), (string) ($a->attributes['created_at'] ?? ''));
        });

        $allReservations = AnnonceReservation::all();
        usort($allReservations, static function (AnnonceReservation $a, AnnonceReservation $b): int {
            return strcmp((string) ($b->attributes['created_at'] ?? ''), (string) ($a->attributes['created_at'] ?? ''));
        });

        $resCounts = [];
        $pendingCount = 0;
        $reservations = [];
        foreach ($allReservations as $res) {
            $aid = (string) ($res->attributes['annonce_id'] ?? '');
            $resCounts[$aid] = ($resCounts[$aid] ?? 0) + 1;
            $status = (string) ($res->attributes['status'] ?? 'pending');
            if ($status === 'pending') {
                $pendingCount++;
            }
            $reservations[] = [
                'id'                => $res->getId(),
                'annonce_id'        => $aid,
                'annonce_title'     => (string) ($res->attributes['annonce_title'] ?? ''),
                'requester_name'    => (string) ($res->attributes['requester_name'] ?? ''),
                'requester_email'   => (string) ($res->attributes['requester_email'] ?? ''),
                'requester_phone'   => (string) ($res->attributes['requester_phone'] ?? ''),
                'message'           => (string) ($res->attributes['message'] ?? ''),
                'status'            => $status,
                'status_label'      => $res->getStatusLabel(),
                'is_pending'        => ($status === 'pending'),
                'is_confirmed'      => ($status === 'confirmed'),
                'created_at'        => (string) ($res->attributes['created_at'] ?? ''),
                'status_url'        => $this->router->generate('admin-annonces-reservation-status', ['id' => $res->getId()]),
                'delete_url'        => $this->router->generate('admin-annonces-reservation-delete', ['id' => $res->getId(), 'token' => $this->user->token]),
            ];
        }

        $annonces = [];
        foreach ($allAnnonces as $item) {
            $aid = (string) $item->getId();
            $attrs = $item->attributes;
            $images = $item->getImages();
            $annonces[] = [
                'id'                 => $aid,
                'title'              => (string) ($attrs['title'] ?? ''),
                'category'           => (string) ($attrs['category'] ?? ''),
                'price_label'        => (string) ($attrs['price_label'] ?? ''),
                'location'           => (string) ($attrs['location'] ?? ''),
                'image'              => $item->getMainImage(),
                'images_count'       => count($images),
                'status'             => (string) ($attrs['status'] ?? 'available'),
                'status_label'       => $item->getStatusLabel(),
                'is_featured'        => !empty($attrs['is_featured']),
                'created_at'         => (string) ($attrs['created_at'] ?? ''),
                'reservations_count' => $resCounts[$aid] ?? 0,
                'public_url'         => $item->getPublicUrl(),
                'edit_url'           => $this->router->generate('admin-annonces-edit-id', ['id' => $aid]),
                'delete_url'         => $this->router->generate('admin-annonces-delete', ['id' => $aid, 'token' => $this->user->token]),
            ];
        }

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('annonces', 'admin-home');
        $tpl->set('annonces', $annonces);
        $tpl->set('reservations', $reservations);
        $tpl->set('pendingCount', $pendingCount);
        $tpl->set('createUrl', $this->router->generate('admin-annonces-edit'));
        $tpl->set('publicListUrl', $this->router->generate('annonces-home'));
        $tpl->set('saveConfUrl', $this->router->generate('admin-annonces-save-config'));
        $tpl->set('token', $this->user->token);
        $tpl->set('_csrfToken', Csrf::token());
        $tpl->set('cfgLabel', (string) ($this->runPlugin->getConfigVal('label') ?? 'Petites Annonces'));
        $tpl->set('cfgIntroText', (string) ($this->runPlugin->getConfigVal('introText') ?? ''));
        $tpl->set('cfgNotifyEmail', (string) ($this->runPlugin->getConfigVal('notifyEmail') ?? ''));
        $tpl->set('cfgOnReserveStatus', (string) ($this->runPlugin->getConfigVal('onReserveStatus') ?? 'mark_reserved'));
        $tpl->set('cfgReservationNotice', (string) ($this->runPlugin->getConfigVal('reservationNotice') ?? ''));
        $tpl->set('cfgRequireAntispam', (string) ($this->runPlugin->getConfigVal('requireAntispam') ?? '1') === '1');
        $response->addTemplate($tpl);
        return $response;
    }

    public function edit()
    {
        return $this->renderEditForm(null);
    }

    public function editId(string $id)
    {
        return $this->renderEditForm($id);
    }

    private function renderEditForm(?string $id)
    {
        if (!$this->checkAdminAccess()) {
            return null;
        }

        $item = $id !== null ? Annonce::findPK($id) : null;
        if ($id !== null && $item === null) {
            Show::msg(Lang::get('annonces.not-found') ?: 'Annonce introuvable.', 'error');
            $this->core->redirect($this->router->generate('admin-annonces-home'));
            return null;
        }

        $attrs = $item ? $item->attributes : [];
        $images = $item ? $item->getImages() : [];
        $annonceData = [
            'id'             => $item ? $item->getId() : '',
            'title'          => (string) ($attrs['title'] ?? ''),
            'category'       => (string) ($attrs['category'] ?? ''),
            'price_label'    => (string) ($attrs['price_label'] ?? ''),
            'location'       => (string) ($attrs['location'] ?? ''),
            'item_condition' => (string) ($attrs['item_condition'] ?? ''),
            'image'          => $item ? $item->getMainImage() : '',
            'images'         => $images,
            'description'    => (string) ($attrs['description'] ?? ''),
            'contact_info'   => (string) ($attrs['contact_info'] ?? ''),
            'status'         => (string) ($attrs['status'] ?? 'available'),
            'is_featured'    => !empty($attrs['is_featured']),
        ];

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('annonces', 'admin-edit');
        $tpl->set('annonce', $annonceData);
        $tpl->set('isEdit', $item !== null);
        $tpl->set('saveUrl', $this->router->generate('admin-annonces-save'));
        $tpl->set('backUrl', $this->router->generate('admin-annonces-home'));
        $tpl->set('token', $this->user->token);
        $tpl->set('_csrfToken', Csrf::token());
        $response->addTemplate($tpl);
        return $response;
    }

    public function save()
    {
        if (!$this->checkAdminAccess()) {
            return null;
        }
        if ($this->user->token !== ($_POST['token'] ?? '') && !Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg(Lang::get('core-not-authorized') ?: 'Action non autorisée.', 'error');
            $this->core->redirect($this->router->generate('admin-annonces-home'));
            return null;
        }

        $id = trim((string) ($_POST['id'] ?? ''));
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            Show::msg('Le titre de l\'annonce est obligatoire.', 'error');
            $target = $id !== '' ? $this->router->generate('admin-annonces-edit-id', ['id' => $id]) : $this->router->generate('admin-annonces-edit');
            $this->core->redirect($target);
            return null;
        }

        $item = ($id !== '') ? (Annonce::findPK($id) ?: new Annonce()) : new Annonce();
        $isNew = ($item->getId() === '');

        $status = trim((string) ($_POST['status'] ?? 'available'));
        if (!in_array($status, Annonce::VALID_STATUSES, true)) {
            $status = 'available';
        }

        // 1. Photos existantes conservées (dans l'ordre défini par l'administrateur)
        $existingImages = $_POST['existing_images'] ?? [];
        if (!is_array($existingImages)) {
            $existingImages = [];
        }
        $imagesList = [];
        foreach ($existingImages as $img) {
            $img = trim((string) $img);
            if ($img !== '') {
                $imagesList[] = $img;
            }
        }

        // 2. Nouvelles photos téléversées (multiples ou fichier unique)
        $uploaded = $this->handleMultipleUploads();
        foreach ($uploaded as $up) {
            $imagesList[] = $up;
        }

        // 3. Image optionnelle saisie par URL directe ou filemanager
        $singleUrl = trim((string) ($_POST['image'] ?? ''));
        if ($singleUrl !== '' && !in_array($singleUrl, $imagesList, true)) {
            $imagesList[] = $singleUrl;
        }

        $imagesList = array_values(array_unique(array_filter($imagesList)));
        $mainImage = $imagesList[0] ?? '';

        $now = date('Y-m-d H:i:s');
        $item->title = $title;
        $item->slug = $this->slugify($title);
        $item->category = trim((string) ($_POST['category'] ?? ''));
        $item->price_label = trim((string) ($_POST['price_label'] ?? ''));
        $item->location = trim((string) ($_POST['location'] ?? ''));
        $item->item_condition = trim((string) ($_POST['item_condition'] ?? ''));
        $item->image = $mainImage;
        $item->images = $imagesList;
        $item->description = (string) ($_POST['description'] ?? '');
        $item->contact_info = trim((string) ($_POST['contact_info'] ?? ''));
        $item->status = $status;
        $item->is_featured = isset($_POST['is_featured']) ? 1 : 0;
        if ($isNew) {
            $item->created_at = $now;
        }
        $item->updated_at = $now;
        $item->save();

        Show::msg(Lang::get('annonces.saved') ?: 'Annonce enregistrée avec succès.', 'success');
        $this->core->redirect($this->router->generate('admin-annonces-home'));
        return null;
    }

    public function delete(string $id, string $token)
    {
        if (!$this->checkAdminAccess()) {
            return null;
        }
        if ($this->user->token !== $token) {
            Show::msg(Lang::get('core-not-authorized') ?: 'Action non autorisée.', 'error');
            $this->core->redirect($this->router->generate('admin-annonces-home'));
            return null;
        }

        $item = Annonce::findPK($id);
        if ($item !== null) {
            $item->delete();
            foreach (AnnonceReservation::all() as $res) {
                if ((string) ($res->attributes['annonce_id'] ?? '') === (string) $id) {
                    $res->delete();
                }
            }
            Show::msg(Lang::get('annonces.deleted') ?: 'Annonce supprimée.', 'success');
        }
        $this->core->redirect($this->router->generate('admin-annonces-home'));
        return null;
    }

    public function updateReservationStatus(string $id)
    {
        if (!$this->checkAdminAccess()) {
            return null;
        }
        if ($this->user->token !== ($_POST['token'] ?? '') && !Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg(Lang::get('core-not-authorized') ?: 'Action non autorisée.', 'error');
            $this->core->redirect($this->router->generate('admin-annonces-home') . '?tab=reservations');
            return null;
        }

        $res = AnnonceReservation::findPK($id);
        $newStatus = trim((string) ($_POST['status'] ?? 'pending'));
        if ($res !== null && in_array($newStatus, AnnonceReservation::VALID_STATUSES, true)) {
            $res->status = $newStatus;
            $res->save();

            // Synchroniser automatiquement le statut de l'annonce associée
            $annonce = Annonce::findPK((string) ($res->attributes['annonce_id'] ?? ''));
            if ($annonce !== null) {
                if ($newStatus === 'confirmed') {
                    $annonce->status = 'reserved';
                    $annonce->updated_at = date('Y-m-d H:i:s');
                    $annonce->save();
                } elseif ($newStatus === 'cancelled' && $annonce->isReserved()) {
                    $annonce->status = 'available';
                    $annonce->updated_at = date('Y-m-d H:i:s');
                    $annonce->save();
                }
            }
            Show::msg('Statut de la réservation mis à jour.', 'success');
        }

        $this->core->redirect($this->router->generate('admin-annonces-home') . '?tab=reservations');
        return null;
    }

    public function deleteReservation(string $id, string $token)
    {
        if (!$this->checkAdminAccess()) {
            return null;
        }
        if ($this->user->token !== $token) {
            Show::msg(Lang::get('core-not-authorized') ?: 'Action non autorisée.', 'error');
            $this->core->redirect($this->router->generate('admin-annonces-home') . '?tab=reservations');
            return null;
        }

        $res = AnnonceReservation::findPK($id);
        if ($res !== null) {
            $res->delete();
            Show::msg('Réservation supprimée.', 'success');
        }
        $this->core->redirect($this->router->generate('admin-annonces-home') . '?tab=reservations');
        return null;
    }

    public function saveConf()
    {
        if (!$this->checkAdminAccess()) {
            return null;
        }
        if ($this->user->token !== ($_POST['token'] ?? '') && !Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg(Lang::get('core-not-authorized') ?: 'Action non autorisée.', 'error');
            $this->core->redirect($this->router->generate('admin-annonces-home'));
            return null;
        }

        $onReserve = ($_POST['onReserveStatus'] ?? 'mark_reserved') === 'keep_available' ? 'keep_available' : 'mark_reserved';
        $this->runPlugin->setConfigVal('label', trim((string) ($_POST['label'] ?? 'Petites Annonces')) ?: 'Petites Annonces');
        $this->runPlugin->setConfigVal('introText', (string) ($_POST['introText'] ?? ''));
        $this->runPlugin->setConfigVal('notifyEmail', trim((string) ($_POST['notifyEmail'] ?? '')));
        $this->runPlugin->setConfigVal('onReserveStatus', $onReserve);
        $this->runPlugin->setConfigVal('reservationNotice', trim((string) ($_POST['reservationNotice'] ?? '')));
        $this->runPlugin->setConfigVal('requireAntispam', isset($_POST['requireAntispam']) ? '1' : '0');
        $this->pluginsManager->savePluginConfig($this->runPlugin);

        Show::msg(Lang::get('annonces.config-saved') ?: 'Paramètres enregistrés.', 'success');
        $this->core->redirect($this->router->generate('admin-annonces-home') . '?tab=settings');
        return null;
    }

    /**
     * Traite un ensemble de fichiers téléversés dans $_FILES['image_files'] ou $_FILES['image_file'].
     *
     * @return string[]
     */
    private function handleMultipleUploads(): array
    {
        $uploadedPaths = [];

        // Cas 1 : multi-fichiers image_files[]
        if (!empty($_FILES['image_files']) && is_array($_FILES['image_files']['name'])) {
            $names = $_FILES['image_files']['name'];
            $tmpNames = $_FILES['image_files']['tmp_name'];
            $errors = $_FILES['image_files']['error'];
            $sizes = $_FILES['image_files']['size'];

            $targetDir = UPLOAD . 'annonces' . DS;
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $hasOversized = false;

            foreach ($names as $idx => $rawName) {
                if (($errors[$idx] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    if (($errors[$idx] ?? 0) === UPLOAD_ERR_INI_SIZE || ($errors[$idx] ?? 0) === UPLOAD_ERR_FORM_SIZE) {
                        $hasOversized = true;
                    }
                    continue;
                }
                if (($sizes[$idx] ?? 0) > 8 * 1024 * 1024) {
                    $hasOversized = true;
                    continue;
                }
                $ext = strtolower(pathinfo((string) $rawName, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed, true)) {
                    continue;
                }
                $tmp = $tmpNames[$idx] ?? '';
                if (!is_uploaded_file($tmp)) {
                    continue;
                }
                $filename = 'annonce_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($tmp, $targetDir . $filename)) {
                    $uploadedPaths[] = '/data/upload/annonces/' . $filename;
                }
            }

            if ($hasOversized) {
                Show::msg('Certaines photos dépassaient la limite autorisée (8 Mo max par photo) et n\'ont pas pu être ajoutées.', 'warning');
            }
        }

        // Cas 2 : fichier unique image_file (compatibilité)
        $single = $this->handleImageUpload();
        if ($single !== null && !in_array($single, $uploadedPaths, true)) {
            $uploadedPaths[] = $single;
        }

        return $uploadedPaths;
    }

    private function handleImageUpload(): ?string
    {
        if (empty($_FILES['image_file']) || !is_uploaded_file($_FILES['image_file']['tmp_name'] ?? '')) {
            return null;
        }
        $file = $_FILES['image_file'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 5 * 1024 * 1024) {
            return null;
        }
        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            return null;
        }
        $targetDir = UPLOAD . 'annonces' . DS;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }
        $filename = 'annonce_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $targetDir . $filename)) {
            return '/data/upload/annonces/' . $filename;
        }
        return null;
    }

    private function slugify(string $text): string
    {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if (is_string($converted) && $converted !== '') {
            $text = $converted;
        }
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        $text = trim($text, '-');
        return $text !== '' ? $text : 'annonce';
    }
}

