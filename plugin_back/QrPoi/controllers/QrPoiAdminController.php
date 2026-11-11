<?php

namespace QrPoi\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Utils\Show;
use Core\Lang;
use Core\Security\Csrf;
use Core\Logger;
use QrPoi\Services\ClaimService;
use QrPoi\Services\QrCodeService;
use QrPoi\Services\QrDisplayService;
use QrPoi\Services\QrStyleService;
use QrPoi\Services\QrScanTrackingService;
use Claim\Services\ClaimService as ClaimClaimService;
use PDO;

defined('ROOT') or exit('Access denied!');

class QrPoiAdminController extends AdminController
{
    private ClaimService $claimService;
    private QrDisplayService $qrDisplayService;
    private QrStyleService $qrStyleService;
    private QrScanTrackingService $scanTrackingService;
    private ?ClaimClaimService $claimClaimService = null;

    public function __construct()
    {
        parent::__construct();
        /** @var Logger $logger */
        $logger = $this->core->getLogger();
        $this->claimService = new ClaimService($logger);
        $this->qrDisplayService = new QrDisplayService($logger);
        $this->qrStyleService = new QrStyleService($logger);
        $this->scanTrackingService = new QrScanTrackingService($logger);
        
        // Initialiser le service Claim pour récupérer tous les POI réclamés
        try {
            require_once PLUGINS . 'claim/services/ClaimService.php';
            $pdo = $this->getPdo();
            $dbPrefix = $this->getDbPrefix();
            if ($pdo && $dbPrefix) {
                $this->claimClaimService = new ClaimClaimService($pdo, $dbPrefix);
            }
        } catch (\Exception $e) {
            $this->core->getLogger()->error('QrPoiAdminController - Erreur initialisation ClaimService: ' . $e->getMessage());
        }
    }

    /**
     * Récupère la configuration de la base de données
     */
    private function getDbConfig(): array
    {
        $file = DATA_PLUGIN . 'search' . DS . 'search_conf.json';
        $cfg = \Utils\Util::readJsonFile($file, true) ?: [];
        
        $defaults = [
            'sql_host' => 'localhost',
            'sql_db' => 'vivrebourges',
            'sql_user' => '',
            'sql_pass' => '',
        ];
        
        return array_merge($defaults, $cfg);
    }

    /**
     * Récupère la connexion PDO
     */
    private function getPdo(): ?PDO
    {
        try {
            $cfg = $this->getDbConfig();
            
            // Si pas de config spécifique, on tente d'utiliser la connexion du CMS
            if (empty($cfg['sql_host']) || empty($cfg['sql_db'])) {
                try {
                    return \Core\Database::getInstance()->getPdo();
                } catch (\Exception $e) {
                    return null;
                }
            }
            
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $cfg['sql_host'], $cfg['sql_db']);
            return new PDO($dsn, $cfg['sql_user'] ?? '', $cfg['sql_pass'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (\Exception $e) {
            // Fallback ultime sur le PDO du CMS si possible
            try {
                return \Core\Database::getInstance()->getPdo();
            } catch (\Exception $ex) {
                $this->core->getLogger()->error('QrPoiAdminController::getPdo - Erreur: ' . $e->getMessage());
                return null;
            }
        }
    }

    /**
     * Récupère le préfixe des tables
     */
    private function getDbPrefix(): string
    {
        $cfg = $this->getDbConfig();
        return $cfg['db_table_prefix'] ?? '';
    }

    /**
     * Tableau de bord admin avec statistiques globales des QR codes
     */
    public function index()
    {
        // Vérifier que l'utilisateur est admin via politique
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('qrpoi:admin'), $ctx)) {
            Show::msg('Permissions insuffisantes.', 'error');
            return $this->core->redirect($this->router->generate('admin-home'));
        }

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('QrPoi', 'admin-stats');
        
        // Récupérer toutes les statistiques
        $totalScans = $this->scanTrackingService->getTotalScans();
        $poisWithScans = $this->scanTrackingService->getPoisWithQrCodes();
        $recentScansRaw = $this->scanTrackingService->getRecentScans(20);
        $statsByDay = $this->scanTrackingService->getStatsByPeriod('day');
        
        // Formater les dates des scans récents
        $recentScans = [];
        foreach ($recentScansRaw as $scan) {
            $scannedAt = $scan['scanned_at'] ?? '';
            if ($scannedAt) {
                try {
                    $scannedAt = date('d/m/Y H:i:s', strtotime($scannedAt));
                } catch (\Exception $e) {
                    // Garder la date originale si erreur
                }
            }
            $scan['scanned_at'] = $scannedAt;
            $recentScans[] = $scan;
        }
        
        // Récupérer tous les POI réclamés pour avoir le nombre total de QR codes possibles
        $allClaimedPois = [];
        if ($this->claimClaimService) {
            try {
                // Récupérer tous les POI réclamés (méthode à adapter selon ClaimService)
                // Pour l'instant, on va utiliser les POI qui ont des scans
                $allClaimedPois = $poisWithScans;
            } catch (\Exception $e) {
                $this->core->getLogger()->error('QrPoiAdminController::list - Erreur récupération POI: ' . $e->getMessage());
            }
        }
        
        // Enrichir les données avec les informations des POI
        $poisWithDetails = [];
        foreach ($poisWithScans as $poiData) {
            $poiId = (int)$poiData['poi_id'];
            $poiInfo = $this->claimService->getPoiInfo($poiId);
            
            // Formater les dates
            $firstScan = $poiData['first_scan'] ?? '';
            $lastScan = $poiData['last_scan'] ?? '';
            if ($firstScan) {
                try {
                    $firstScan = date('d/m/Y H:i', strtotime($firstScan));
                } catch (\Exception $e) {
                    // Garder la date originale si erreur
                }
            }
            if ($lastScan) {
                try {
                    $lastScan = date('d/m/Y H:i', strtotime($lastScan));
                } catch (\Exception $e) {
                    // Garder la date originale si erreur
                }
            }
            
            $poisWithDetails[] = [
                'poi_id' => $poiId,
                'poi_name' => $poiInfo['name'] ?? 'POI #' . $poiId,
                'scan_count' => (int)$poiData['scan_count'],
                'first_scan' => $firstScan,
                'last_scan' => $lastScan,
                'qr_code_url' => QrCodeService::generatePoiQrCodeUrl($poiId),
                'redirect_url' => QrCodeService::generatePoiRedirectUrl($poiId),
            ];
        }
        
        // Trier par nombre de scans décroissant
        usort($poisWithDetails, function($a, $b) {
            return $b['scan_count'] - $a['scan_count'];
        });
        
        // Préparer les données pour les graphiques
        $chartLabels = [];
        $chartData = [];
        foreach (array_reverse($statsByDay) as $stat) {
            $chartLabels[] = "'" . $stat['period'] . "'";
            $chartData[] = (int)$stat['scan_count'];
        }
        
        // Calculer la moyenne de scans par QR code
        $avgScansPerQr = count($poisWithDetails) > 0 
            ? round($totalScans / count($poisWithDetails), 1) 
            : 0;
        
        $tpl->set('totalScans', $totalScans);
        $tpl->set('totalPoisWithQrCodes', count($poisWithDetails));
        $tpl->set('avgScansPerQr', $avgScansPerQr);
        $tpl->set('poisWithDetails', $poisWithDetails);
        $tpl->set('recentScans', $recentScans);
        $tpl->set('chartLabels', implode(',', $chartLabels));
        $tpl->set('chartData', implode(',', $chartData));
        
        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Génère et affiche le QR code pour un POI
     */
    public function generate($poi_id = null)
    {
        $poiId = (int)($poi_id ?? 0);
        
        $userEmail = $this->user->attributes['email'] ?? null;
        $username = $this->user->attributes['username'] ?? null;
        $userId = $this->claimService->getMySQLUserId($userEmail ?? '', $username ?? '');
        $isOwner = $userId && $this->claimService->isOwner($userId, $poiId);

        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user, ['is_owner' => $isOwner]);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('qrpoi:manage'), $ctx)) {
            Show::msg('Permissions insuffisantes.', 'error');
            return $this->core->redirect($this->router->generate('admin-home'));
        }

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('QrPoi', 'admin-generate');
        
        $poiInfo = $this->claimService->getPoiInfo($poiId);
        if (!$poiInfo) {
            Show::msg('POI introuvable.', 'error');
            return $this->core->redirect('/admin/qr-poi');
        }

        $qrCodeUrl = QrCodeService::generatePoiQrCodeUrl($poiId, 500);
        $redirectUrl = QrCodeService::generatePoiRedirectUrl($poiId);
        $downloadUrl = $this->router->generate('admin-qr-poi-download', ['poi_id' => $poiId]);
        $toggleUrl = $this->router->generate('admin-qr-poi-toggle-display', ['poi_id' => $poiId]);
        $customizeUrl = '/admin/qr-poi/customize/' . $poiId;

        $isDisplayEnabled = $this->qrDisplayService->isQrCodeDisplayEnabled($poiId, $userId);
        
        $tpl->set('poi', $poiInfo);
        $tpl->set('qrCodeUrl', $qrCodeUrl);
        $tpl->set('redirectUrl', $redirectUrl);
        $tpl->set('downloadUrl', $downloadUrl);
        $tpl->set('toggleUrl', $toggleUrl);
        $tpl->set('customizeUrl', $customizeUrl);
        $tpl->set('isDisplayEnabled', $isDisplayEnabled);
        $tpl->set('csrfToken', Csrf::token());
        $tpl->set('backUrl', $this->router->generate('admin-qr-poi-list'));
        
        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Télécharge le QR code en tant qu'image PNG
     */
    public function download($poi_id = null)
    {
        $poiId = (int)($poi_id ?? 0);
        
        $userEmail = $this->user->attributes['email'] ?? null;
        $username = $this->user->attributes['username'] ?? null;
        $userId = $this->claimService->getMySQLUserId($userEmail ?? '', $username ?? '');
        $isOwner = $userId && $this->claimService->isOwner($userId, $poiId);

        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user, ['is_owner' => $isOwner]);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('qrpoi:manage'), $ctx)) {
            Show::msg('Permissions insuffisantes.', 'error');
            return $this->core->redirect($this->router->generate('admin-home'));
        }

        $poiInfo = $this->claimService->getPoiInfo($poiId);
        if (!$poiInfo) {
            Show::msg('POI introuvable.', 'error');
            return $this->core->redirect('/admin/qr-poi');
        }

        // Récupérer le style
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

        // Générer le QR code
        $redirectUrl = QrCodeService::generatePoiRedirectUrl($poiId);
        $imageData = QrCodeService::generateAdvancedQrCode($redirectUrl, $options);
        
        if (!$imageData) {
            Show::msg('Erreur lors de la génération du QR code.', 'error');
            return $this->core->redirect('/admin/qr-poi');
        }

        // Envoyer le fichier
        $filename = 'qr-poi-' . $poiId . '-' . preg_replace('/[^a-z0-9]/i', '-', strtolower($poiInfo['name'])) . '.png';
        
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($imageData));
        echo $imageData;
        exit;
    }

    /**
     * Active ou désactive l'affichage du QR code sur la page publique
     */
    public function toggleDisplay($poi_id = null)
    {
        // Valider le token CSRF
        if (!Csrf::validate($_POST['token'] ?? null, false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect($this->router->generate('admin-home'));
        }

        $poiId = (int)($poi_id ?? $_POST['poi_id'] ?? 0);
        
        $userEmail = $this->user->attributes['email'] ?? null;
        $username = $this->user->attributes['username'] ?? null;
        $userId = $this->claimService->getMySQLUserId($userEmail ?? '', $username ?? '');
        $isOwner = $userId && $this->claimService->isOwner($userId, $poiId);

        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user, ['is_owner' => $isOwner]);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('qrpoi:manage'), $ctx)) {
            Show::msg('Permissions insuffisantes.', 'error');
            return $this->core->redirect($this->router->generate('admin-home'));
        }

        // Récupérer l'état actuel
        $currentStatus = $this->qrDisplayService->isQrCodeDisplayEnabled($poiId, $userId);
        
        // Inverser l'état (ou utiliser la valeur de la checkbox si fournie)
        $newStatus = isset($_POST['show_qr_code']) ? (bool)$_POST['show_qr_code'] : !$currentStatus;
        
        // Sauvegarder
        if ($this->qrDisplayService->toggleQrCodeDisplay($poiId, $userId, $newStatus)) {
            Show::msg(
                $newStatus 
                    ? 'Le QR code sera maintenant affiché sur la page publique de votre fiche.' 
                    : 'Le QR code ne sera plus affiché sur la page publique.',
                'success'
            );
        } else {
            Show::msg('Erreur lors de la mise à jour des paramètres.', 'error');
        }
        
        // Rediriger vers la page de génération pour ce POI spécifique
        return $this->core->redirect('/admin/qr-poi/generate/' . $poiId);
    }

    /**
     * Page de personnalisation du QR Code
     */
    public function customize($poi_id = null)
    {
        $poiId = (int)($poi_id ?? 0);
        
        $userEmail = $this->user->attributes['email'] ?? null;
        $username = $this->user->attributes['username'] ?? null;
        $userId = $this->claimService->getMySQLUserId($userEmail ?? '', $username ?? '');
        $isOwner = $userId && $this->claimService->isOwner($userId, $poiId);

        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user, ['is_owner' => $isOwner]);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('qrpoi:manage'), $ctx)) {
            Show::msg('Permissions insuffisantes.', 'error');
            return $this->core->redirect($this->router->generate('admin-home'));
        }

        $poiInfo = $this->claimService->getPoiInfo($poiId);
        if (!$poiInfo) {
            Show::msg('POI introuvable.', 'error');
            return $this->core->redirect('/admin/qr-poi');
        }

        $response = new AdminResponse();
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
        $tpl->set('qrPreviewUrl', '/admin/qr-poi/preview/' . $poiId);
        $tpl->set('removeLogoUrl', '/admin/qr-poi/remove-logo/' . $poiId);
        $tpl->set('saveUrl', '/admin/qr-poi/customize/' . $poiId);
        $tpl->set('backUrl', '/admin/qr-poi/generate/' . $poiId);
        
        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Sauvegarde la personnalisation
     */
    public function saveCustomization($poi_id = null)
    {
        if (!Csrf::validate($_POST['token'] ?? null, false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect($this->router->generate('admin-home'));
        }

        $poiId = (int)($poi_id ?? $_POST['poi_id'] ?? 0);
        $poiInfo = $this->claimService->getPoiInfo($poiId);
        if (!$poiInfo) {
            Show::msg('POI introuvable.', 'error');
            return $this->core->redirect('/admin/qr-poi');
        }

        // Vérification des droits (identique à customize)
        $userEmail = $this->user->attributes['email'] ?? null;
        $username = $this->user->attributes['username'] ?? null;
        $userId = $this->claimService->getMySQLUserId($userEmail ?? '', $username ?? '');
        $isOwner = $userId && $this->claimService->isOwner($userId, $poiId);
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user, ['is_owner' => $isOwner]);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('qrpoi:manage'), $ctx)) {
            Show::msg('Permissions insuffisantes.', 'error');
            return $this->core->redirect($this->router->generate('admin-home'));
        }

        $style = [
            'color' => $_POST['color'] ?? '000000',
            'bgcolor' => $_POST['bgcolor'] ?? 'FFFFFF',
            'size' => (int)($_POST['size'] ?? 300),
            'qzone' => (int)($_POST['qzone'] ?? 1),
            'custom_text' => $_POST['custom_text'] ?? null,
            'text_color' => $_POST['text_color'] ?? '000000',
        ];

        // Gestion du logo
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
            // Garder l'ancien logo si pas de nouvel upload
            $oldStyle = $this->qrStyleService->getStyle($poiId);
            $style['logo_path'] = $oldStyle['logo_path'] ?? null;
        }

        if ($this->qrStyleService->saveStyle($poiId, $userId, $style)) {
            // Invalider le cache de l'image QR Code
            $qrPath = UPLOAD . 'QrPoi' . DS . 'qrcodes' . DS . "poi_{$poiId}.png";
            if (file_exists($qrPath)) {
                @unlink($qrPath);
            }
            Show::msg('Personnalisation sauvegardée.', 'success');
        } else {
            Show::msg('Erreur lors de la sauvegarde.', 'error');
        }

        return $this->core->redirect('/admin/qr-poi/customize/' . $poiId);
    }

    /**
     * Supprime le logo
     */
    public function removeLogo($poi_id = null)
    {
        if (!Csrf::validate($_POST['token'] ?? null, false)) {
            Show::msg(Lang::get('core.bad-token'), 'error');
            return $this->core->redirect($this->router->generate('admin-home'));
        }

        $poiId = (int)($poi_id ?? 0);
        if ($this->qrStyleService->removeLogo($poiId)) {
            // Invalider le cache de l'image QR Code
            $qrPath = UPLOAD . 'QrPoi' . DS . 'qrcodes' . DS . "poi_{$poiId}.png";
            if (file_exists($qrPath)) {
                @unlink($qrPath);
            }
            Show::msg('Logo supprimé.', 'success');
        } else {
            Show::msg('Erreur lors de la suppression.', 'error');
        }

        return $this->core->redirect('/admin/qr-poi/customize/' . $poiId);
    }

    /**
     * Preview dynamique (image)
     */
    public function preview($poi_id = null)
    {
        $poiId = (int)($poi_id ?? 0);
        $options = [
            'size' => (int)($_GET['size'] ?? 300),
            'color' => $_GET['color'] ?? '000000',
            'bgcolor' => $_GET['bgcolor'] ?? 'FFFFFF',
            'qzone' => (int)($_GET['qzone'] ?? 1),
            'custom_text' => $_GET['custom_text'] ?? null,
            'text_color' => $_GET['text_color'] ?? '000000',
        ];

        // Charger le logo actuel si pas spécifié dans GET (preview en live change seulement couleurs/texte généralement)
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
}



