<?php

namespace QrPoi\Controllers;

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Core\Auth\UsersManager;
use Core\Security\Csrf;
use Utils\Show;
use QrPoi\Services\ClaimService;
use QrPoi\Services\QrCodeService;
use QrPoi\Services\QrScanTrackingService;
use QrPoi\Services\QrDisplayService;
use QrPoi\Services\QrStyleService;

defined('ROOT') or exit('Access denied!');

class QrPoiPublicController extends PublicController
{
    private ClaimService $claimService;
    private QrScanTrackingService $scanTrackingService;
    private QrStyleService $qrStyleService;

    public function __construct()
    {
        parent::__construct();
        $this->claimService = new ClaimService($this->core->getLogger());
        $this->qrStyleService = new QrStyleService($this->core->getLogger());
        $this->scanTrackingService = new QrScanTrackingService($this->core->getLogger());
    }

    /**
     * Redirige vers la page de détails du POI et enregistre le scan
     */
    public function redirect()
    {
        $poiId = (int)$this->router->getParam('poi_id');
        
        if ($poiId < 1) {
            $this->core->redirect('/');
            return;
        }

        // Vérifier que le POI existe
        $poiInfo = $this->claimService->getPoiInfo($poiId);
        if (!$poiInfo) {
            $this->core->redirect('/');
            return;
        }

        // Enregistrer le scan du QR code
        $this->scanTrackingService->trackScan(
            $poiId,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $_SERVER['HTTP_REFERER'] ?? null
        );

        // Rediriger vers la page de détails du POI
        try {
            // Utiliser le SearchService pour générer l'URL courte
            $cfg = $this->core->getConfig(); // Fallback si nécessaire
            /** @var \Core\Plugin\Plugin|null $searchPlugin */
            $searchPlugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('SearchEngine');
            $searchConfig = $searchPlugin ? $searchPlugin->getConfig() : [];
            
            $searchService = new \SearchEngine\Services\Search\SearchService($searchConfig);
            $urlName = $searchService->generateUrlName($poiInfo['name'], $poiId);
            
            // Tenter d'utiliser le routeur si la route existe, sinon rediriger vers /slug
            try {
                $detailUrl = $this->router->generate('SearchUi-detail', ['type' => 'poi', 'name' => $urlName]);
            } catch (\Exception $e) {
                // Si la route nommée n'existe pas, on tente l'URL courte
                $detailUrl = '/' . $urlName;
            }
            
            $this->core->redirect($detailUrl);
        } catch (\Exception $e) {
            // Fallback ultime : rediriger vers la recherche avec l'ID
            $this->core->redirect('/search?q=' . urlencode($poiInfo['name']));
        }
    }

    /**
     * Page frontend de gestion du QR Code (pour le propriétaire du POI)
     */
    public function manage()
    {
        $poiId = (int)$this->router->getParam('poi_id');
        $user = UsersManager::getCurrentUser();
        
        if (!$user) {
            try {
                $loginUrl = $this->router->generate('login') . '?msg=qr-manage';
            } catch (\Exception $e) {
                $loginUrl = '/login?msg=qr-manage';
            }
            $this->core->redirect($loginUrl);
            return;
        }

        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user, ['id' => $poiId, 'type' => 'poi']);
        $isAdmin = $evaluator->isAdmin($ctx);

        // Vérifier si le user est propriétaire de ce POI
        $userEmail = $user->attributes['email'] ?? null;
        $username = $user->attributes['username'] ?? null;
        $userId = $this->claimService->getMySQLUserId($userEmail ?? '', $username ?? '');
        $isOwner = $userId && $this->claimService->isOwner($userId, $poiId);

        // Accès uniquement si admin ou owner
        if (!$isAdmin && !$isOwner) {
            Show::msg('Vous n\'avez pas les droits pour accéder à cette page.', 'error');
            $this->core->redirect('/');
            return;
        }

        $poiInfo = $this->claimService->getPoiInfo($poiId);
        if (!$poiInfo) {
            Show::msg('Ce POI est introuvable.', 'error');
            $this->core->redirect('/');
            return;
        }

        // QR Code URLs
        $qrCodeUrl = QrCodeService::generatePoiQrCodeUrl($poiId, 400);
        $redirectUrl = QrCodeService::generatePoiRedirectUrl($poiId);

        // URLs actions
        try {
            $toggleUrl = $this->router->generate('qr-poi-toggle-display', ['poi_id' => $poiId]);
        } catch (\Exception $e) {
            $toggleUrl = '/qr-poi/toggle-display/' . $poiId;
        }

        try {
            $downloadUrl = $this->router->generate('admin-qr-poi-download', ['poi_id' => $poiId]);
        } catch (\Exception $e) {
            $downloadUrl = '/admin/qr-poi/download/' . $poiId;
        }

        // Vérifier si QR code est activé pour ce POI
        $qrDisplayService = new QrDisplayService($this->core->getLogger());
        $isDisplayEnabled = $qrDisplayService->isQrCodeDisplayEnabled($poiId, $userId);

        // URL de retour vers la fiche publique
        try {
            $searchPlugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('SearchEngine');
            $searchConfig = $searchPlugin ? $searchPlugin->getConfig() : [];
            $searchService = new \SearchEngine\Services\Search\SearchService($searchConfig);
            $backUrl = '/' . $searchService->generateUrlName($poiInfo['name'], $poiId);
        } catch (\Exception $e) {
            $backUrl = '/';
        }

        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('QrPoi', 'manage');
        
        $tpl->set('poi', $poiInfo);
        $tpl->set('qrCodeUrl', $qrCodeUrl);
        $tpl->set('redirectUrl', $redirectUrl);
        $tpl->set('downloadUrl', $downloadUrl);
        $tpl->set('toggleUrl', $toggleUrl);
        $tpl->set('isDisplayEnabled', $isDisplayEnabled);
        $tpl->set('backUrl', $backUrl);
        $tpl->set('csrfToken', Csrf::token());
        $tpl->set('isAdmin', $isAdmin);

        $response->setTitle('QR Code — ' . ($poiInfo['name'] ?? 'Fiche POI'));
        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Toggle l'affichage du QR code sur la fiche publique (route publique pour propriétaires)
     */
    public function toggleDisplay()
    {
        if (!Csrf::validate($_POST['token'] ?? null, false)) {
            $this->core->redirect('/');
            return;
        }

        $poiId = (int)$this->router->getParam('poi_id');
        $user = UsersManager::getCurrentUser();

        if (!$user) {
            $this->core->redirect('/login');
            return;
        }

        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);
        $isAdmin = $evaluator->isAdmin($ctx);

        $userEmail = $user->attributes['email'] ?? null;
        $username = $user->attributes['username'] ?? null;
        $userId = $this->claimService->getMySQLUserId($userEmail ?? '', $username ?? '');
        $isOwner = $userId && $this->claimService->isOwner($userId, $poiId);

        if (!$isAdmin && !$isOwner) {
            Show::msg('Vous n\'avez pas les droits pour effectuer cette action.', 'error');
            $this->core->redirect('/');
            return;
        }

        $qrDisplayService = new QrDisplayService($this->core->getLogger());
        $currentStatus = $qrDisplayService->isQrCodeDisplayEnabled($poiId, $userId);
        $newStatus = isset($_POST['show_qr_code']) ? (bool)$_POST['show_qr_code'] : !$currentStatus;
        
        $qrDisplayService->toggleQrCodeDisplay($poiId, $userId, $newStatus);

        try {
            $backUrl = $this->router->generate('qr-poi-manage', ['poi_id' => $poiId]);
        } catch (\Exception $e) {
            $backUrl = '/qr-poi/manage/' . $poiId;
        }
        $this->core->redirect($backUrl);
    }

    /**
     * Page de personnalisation pour les propriétaires
     */
    public function customize()
    {
        $poiId = (int)$this->router->getParam('poi_id');
        $user = UsersManager::getCurrentUser();
        
        if (!$user) {
            $this->core->redirect('/login?msg=qr-manage');
            return;
        }

        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);
        $isAdmin = $evaluator->isAdmin($ctx);

        $userEmail = $user->attributes['email'] ?? null;
        $username = $user->attributes['username'] ?? null;
        $userId = $this->claimService->getMySQLUserId($userEmail ?? '', $username ?? '');
        $isOwner = $userId && $this->claimService->isOwner($userId, $poiId);

        if (!$isAdmin && !$isOwner) {
            Show::msg('Permissions insuffisantes.', 'error');
            $this->core->redirect('/');
            return;
        }

        // --- CHECK PREMIUM GATING ---
        $isPremium = false;
        if (\Core\Plugin\PluginsManager::isActivePlugin('offers')) {
            require_once PLUGINS . 'offers/services/SubscriptionService.php';
            $subService = new \Offers\Services\SubscriptionService($this->core->getLogger());
            $isPremium = $subService->hasActiveSubscription((int)$user->getId(), $user->attributes['email'] ?? null, $user->attributes['username'] ?? null);
        }


        $poiInfo = $this->claimService->getPoiInfo($poiId);
        if (!$poiInfo) {
            Show::msg('POI introuvable.', 'error');
            $this->core->redirect('/');
            return;
        }

        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('QrPoi', 'customize');
        
        $style = $this->qrStyleService->getStyle($poiId) ?: [
            'color' => '000000',
            'bgcolor' => 'FFFFFF',
            'size' => 300,
            'qzone' => 1,
            'text_color' => '000000',
            'custom_text' => null
        ];

        $tpl->set('poi', $poiInfo);
        $tpl->set('style', $style);
        $tpl->set('csrfToken', Csrf::token());
        $tpl->set('qrPreviewUrl', '/qr-poi/preview/' . $poiId);
        $tpl->set('removeLogoUrl', '/qr-poi/remove-logo/' . $poiId);
        $tpl->set('saveUrl', '/qr-poi/customize/' . $poiId);
        $tpl->set('backUrl', '/qr-poi/manage/' . $poiId);
        $tpl->set('isPremium', $isPremium || $isAdmin);

        
        $response->setTitle('Personnaliser QR Code — ' . $poiInfo['name']);
        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Sauvegarde la personnalisation
     */
    public function saveCustomization()
    {
        if (!Csrf::validate($_POST['token'] ?? null, false)) {
            $this->core->redirect('/');
            return;
        }

        $poiId = (int)$this->router->getParam('poi_id');
        $user = UsersManager::getCurrentUser();
        if (!$user) {
            $this->core->redirect('/login');
            return;
        }

        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);
        $isAdmin = $evaluator->isAdmin($ctx);

        $userEmail = $user->attributes['email'] ?? null;
        $username = $user->attributes['username'] ?? null;
        $userId = $this->claimService->getMySQLUserId($userEmail ?? '', $username ?? '');
        $isOwner = $userId && $this->claimService->isOwner($userId, $poiId);

        if (!$isAdmin && !$isOwner) {
            Show::msg('Permissions insuffisantes.', 'error');
            $this->core->redirect('/');
            return;
        }

        // --- CHECK PREMIUM GATING FOR SAVE ---
        $isPremium = false;
        if (\Core\Plugin\PluginsManager::isActivePlugin('offers')) {
            require_once PLUGINS . 'offers/services/SubscriptionService.php';
            $subService = new \Offers\Services\SubscriptionService($this->core->getLogger());
            $isPremium = $subService->hasActiveSubscription((int)$user->getId(), $user->attributes['email'] ?? null, $user->attributes['username'] ?? null);
        }

        if (!$isAdmin && !$isPremium) {
            Show::msg('La personnalisation du QR Code est réservée aux abonnés Premium.', 'warning');
            $this->core->redirect('/qr-poi/customize/' . $poiId);
            return;
        }


        $style = [
            'color' => $_POST['color'] ?? '000000',
            'bgcolor' => $_POST['bgcolor'] ?? 'FFFFFF',
            'size' => (int)($_POST['size'] ?? 300),
            'qzone' => (int)($_POST['qzone'] ?? 1),
            'custom_text' => $_POST['custom_text'] ?? null,
            'text_color' => $_POST['text_color'] ?? '000000',
        ];

        if (!empty($_FILES['logo']['tmp_name'])) {
            $uploadDir = UPLOAD . 'qr-logos' . DS;
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $filename = 'logo-' . $poiId . '-' . time() . '.' . $ext;
            $target = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target)) {
                $style['logo_path'] = $target;
            }
        } else {
            $oldStyle = $this->qrStyleService->getStyle($poiId);
            $style['logo_path'] = $oldStyle['logo_path'] ?? null;
        }

        if ($this->qrStyleService->saveStyle($poiId, $userId, $style)) {
            Show::msg('Personnalisation sauvegardée.', 'success');
        } else {
            Show::msg('Erreur lors de la sauvegarde.', 'error');
        }

        $this->core->redirect('/qr-poi/customize/' . $poiId);
    }

    /**
     * Preview image
     */
    public function preview()
    {
        $poiId = (int)$this->router->getParam('poi_id');
        
        $options = [
            'size' => (int)($_GET['size'] ?? 300),
            'color' => $_GET['color'] ?? '000000',
            'bgcolor' => $_GET['bgcolor'] ?? 'FFFFFF',
            'qzone' => (int)($_GET['qzone'] ?? 1),
            'custom_text' => $_GET['custom_text'] ?? null,
            'text_color' => $_GET['text_color'] ?? '000000',
        ];

        $style = $this->qrStyleService->getStyle($poiId);
        $options['logo_path'] = $style['logo_path'] ?? null;

        $redirectUrl = QrCodeService::generatePoiRedirectUrl($poiId);
        $imgData = QrCodeService::generateAdvancedQrCode($redirectUrl, $options);

        if ($imgData) {
            header('Content-Type: image/png');
            echo $imgData;
        } else {
            header('HTTP/1.0 404 Not Found');
        }
        exit;
    }

    /**
     * Supprimmer logo
     */
    public function removeLogo()
    {
        if (!Csrf::validate($_POST['token'] ?? null, false)) {
            $this->core->redirect('/');
            return;
        }

        $poiId = (int)$this->router->getParam('poi_id');
        if ($this->qrStyleService->removeLogo($poiId)) {
            Show::msg('Logo supprimé.', 'success');
        } else {
            Show::msg('Erreur lors de la suppression.', 'error');
        }

        $this->core->redirect('/qr-poi/customize/' . $poiId);
    }

    /**
     * Télécharge le QR code
     */
    public function download()
    {
        $poiId = (int)$this->router->getParam('poi_id');
        $user = UsersManager::getCurrentUser();
        if (!$user) {
            $this->core->redirect('/login');
            return;
        }

        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);
        $isAdmin = $evaluator->isAdmin($ctx);

        $userEmail = $user->attributes['email'] ?? null;
        $username = $user->attributes['username'] ?? null;
        $userId = $this->claimService->getMySQLUserId($userEmail ?? '', $username ?? '');
        $isOwner = $userId && $this->claimService->isOwner($userId, $poiId);

        if (!$isAdmin && !$isOwner) {
            Show::msg('Permissions insuffisantes.', 'error');
            $this->core->redirect('/');
            return;
        }

        $poiInfo = $this->claimService->getPoiInfo($poiId);
        if (!$poiInfo) {
            Show::msg('POI introuvable.', 'error');
            $this->core->redirect('/');
            return;
        }

        $style = $this->qrStyleService->getStyle($poiId) ?: [];
        $options = [
            'size' => 1000,
            'color' => $style['color'] ?? '000000',
            'bgcolor' => $style['bgcolor'] ?? 'FFFFFF',
            'qzone' => $style['qzone'] ?? 1,
            'logo_path' => $style['logo_path'] ?? null,
            'custom_text' => $style['custom_text'] ?? null,
            'text_color' => $style['text_color'] ?? '000000',
        ];

        $redirectUrl = QrCodeService::generatePoiRedirectUrl($poiId);
        $imageData = QrCodeService::generateAdvancedQrCode($redirectUrl, $options);
        
        if (!$imageData) {
            Show::msg('Erreur lors de la génération du QR code.', 'error');
            $this->core->redirect('/qr-poi/manage/' . $poiId);
        }

        $filename = 'qr-poi-' . $poiId . '-' . preg_replace('/[^a-z0-9]/i', '-', strtolower($poiInfo['name'])) . '.png';
        
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($imageData));
        echo $imageData;
        exit;
    }
}
