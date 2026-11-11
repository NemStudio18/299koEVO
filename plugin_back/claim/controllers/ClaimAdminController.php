<?php

namespace Claim\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Auth\User;
use Utils\Show;
use Claim\Services\ClaimService;
use PDO;

defined('ROOT') or exit('Access denied!');

class ClaimAdminController extends AdminController
{
    private function getPdo(): PDO
    {
        try {
            return \Core\Database::getInstance()->getPdo();
        } catch (\PDOException $e) {
            throw new \RuntimeException('Erreur de connexion à la base de données : ' . $e->getMessage());
        }
    }

    private function getClaimService(): ClaimService
    {
        // S'assurer que le service est chargé
        if (!class_exists('\Claim\Services\ClaimService')) {
            $serviceFile = PLUGINS . 'claim/services/ClaimService.php';
            if (file_exists($serviceFile)) {
                require_once $serviceFile;
            }
        }
        return new \Claim\Services\ClaimService();
    }

    private function resolveUserName(?int $userId): string
    {
        if ($userId === null) {
            return 'N/A';
        }
        $u = User::findPK($userId);
        if ($u) {
            $name = $u->attributes['username'] ?? '';
            if ($name === '' && isset($u->attributes['email'])) {
                $name = (string) $u->attributes['email'];
            }
            if ($name !== '') return $name;
        }
        return 'user#' . $userId;
    }

    public function proof(string $id)
    {
        $claimId = trim($id);
        if (empty($claimId)) {
            http_response_code(404);
            exit;
        }
        
        $service = $this->getClaimService();
        $claim = $service->getClaimById($claimId);
        
        if (!$claim || empty($claim->getProofPath())) {
            http_response_code(404);
            exit;
        }
        
        $path = $claim->getProofPath();
        
        // Normaliser le chemin (convertir tous les séparateurs en DIRECTORY_SEPARATOR)
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        
        // Si le chemin n'est pas absolu (ne commence pas par une lettre de lecteur ou /), le construire depuis ROOT
        if (!preg_match('/^([A-Z]:|\\\\|\/)/i', $path)) {
            // Chemin relatif, le construire depuis ROOT
            $path = ROOT . ltrim($path, DIRECTORY_SEPARATOR);
        }
        
        // Vérifier que le fichier existe
        if (!is_file($path)) {
            // Essayer aussi avec DATA_PLUGIN si le chemin contient "plugin/claim"
            if (strpos($path, 'plugin' . DIRECTORY_SEPARATOR . 'claim') !== false) {
                $relativePath = preg_replace('/.*plugin' . preg_quote(DIRECTORY_SEPARATOR, '/') . 'claim' . preg_quote(DIRECTORY_SEPARATOR, '/') . '/', '', $path);
                $alternativePath = DATA_PLUGIN . 'claim' . DIRECTORY_SEPARATOR . $relativePath;
                if (is_file($alternativePath)) {
                    $path = $alternativePath;
                }
            }
        }
        
        // Vérifier que le fichier existe et est lisible
        if (!is_file($path) || !is_readable($path)) {
            error_log("Claim proof file not found or not readable. Claim ID: {$claimId}");
            error_log("Original proof_path from DB: " . $claim->getProofPath());
            error_log("Resolved path: " . $path);
            error_log("DATA_PLUGIN: " . DATA_PLUGIN);
            error_log("ROOT: " . ROOT);
            http_response_code(404);
            exit;
        }
        
        // Déterminer le type MIME
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
        ];
        $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
        
        // Si mime_content_type est disponible, l'utiliser en priorité
        if (function_exists('mime_content_type')) {
            $detectedMime = @mime_content_type($path);
            if ($detectedMime && $detectedMime !== 'application/octet-stream') {
                $mime = $detectedMime;
            }
        }
        
        // Nettoyer tous les buffers de sortie pour éviter que le framework n'ajoute du contenu
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Envoyer les headers
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . basename($path) . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, max-age=3600');
        header('X-Content-Type-Options: nosniff');
        
        // Lire et envoyer le fichier
        @readfile($path);
        exit;
    }

    public function dashboard()
    {
        try {
            $service = $this->getClaimService();
            $claims = $service->getAllClaims();
            
            // Récupérer les statistiques
            $stats = $service->getStats();
            
            // Enrichir avec les noms d'utilisateurs et POI
            foreach ($claims as $c) {
                $userId = $c->getUserId();
                $poiId = $c->getPoiId();

                // Résoudre le nom d'utilisateur
                $c->attributes['display_user'] = $this->resolveUserName($userId);

                // Résoudre le nom du POI
                $poi = class_exists('\PoiData\Entities\Poi') ? \PoiData\Entities\Poi::findPK($poiId) : null;
                $c->attributes['poi_name'] = $poi ? ($poi->name ?? 'POI #' . $poiId) : 'POI #' . $poiId;
                $c->attributes['title'] = $c->attributes['poi_name'];

                // Extraire le nom de fichier depuis proof_path pour l'affichage
                $proofPath = $c->getProofPath();
                $c->attributes['proof_filename'] = !empty($proofPath) ? basename($proofPath) : null;
            }
            
            $response = new AdminResponse();
            $tpl = $response->createPluginTemplate('claim', 'admin');
            $tpl->set('claims', $claims);
            $tpl->set('stats', $stats);
            $response->addTemplate($tpl);
            return $response;
        } catch (\Exception $e) {
            Show::msg('Erreur de configuration : ' . $e->getMessage(), 'error');
            $response = new AdminResponse();
            $response->setTitle('Erreur Claim');
            return $response;
        }
    }

    public function decide()
    {
        $claimId = trim((string) $this->request->post('id', ''));
        $decision = trim((string) $this->request->post('decision', ''));
        
        if (empty($claimId) || !in_array($decision, ['approved', 'rejected'], true)) {
            $this->core->error404();
            return null;
        }

        $service = $this->getClaimService();
        $claim = $service->getClaimById($claimId);
        
        if (!$claim) {
            Show::msg('Revendication introuvable.', 'error');
            $this->core->redirect($this->router->generate('admin-claim'));
            return null;
        }

        $approvedBy = $this->user ? ($this->user->attributes['id'] ?? null) : null;
        
        // Mettre à jour le statut
        $success = $service->updateClaimStatus($claimId, $decision, $approvedBy);
        
        if ($success) {
            // Fire hooks for approved claims (gamification, notifications)
            if ($decision === 'approved') {
                $this->core->callHook('claim:validated', [
                    'user_id'  => $claim->getUserId(),
                    'poi_id'   => $claim->getPoiId(),
                    'claim_id' => $claimId,
                ]);
            }

            // Supprimer le justificatif après décision
            if (!empty($claim->getProofPath()) && is_file($claim->getProofPath())) {
                @unlink($claim->getProofPath());
            }
            Show::msg('Décision enregistrée.', 'success');
        } else {
            Show::msg('Erreur lors de l\'enregistrement de la décision.', 'error');
        }
        
        $this->core->redirect($this->router->generate('admin-claim'));
        return null;
    }
}

