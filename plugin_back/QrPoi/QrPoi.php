<?php

namespace QrPoi {

use Core\Plugin\PluginsManager;
use Core\Core;

defined('ROOT') or exit('No direct script access allowed');

class Hooks
{
    public static function install(): void
    {
        $core   = Core::getInstance();
        $logger = $core->getLogger();
        $logger->log(\Core\Logger::LEVEL_DEBUG, "[qr-poi::install] Début installation (préparation répertoire data)");

        $dataDir = DATA_PLUGIN . 'QrPoi' . DS;
        if (!is_dir($dataDir)) {
            $logger->log(\Core\Logger::LEVEL_DEBUG, "[qr-poi::install] Création du répertoire '{$dataDir}'");
            @mkdir($dataDir, 0755, true);
        }

        $logger->log(\Core\Logger::LEVEL_DEBUG, "[qr-poi::install] Installation terminée");
    }
}

}

namespace {

    function qrPoiInstall(): void
    {
        \QrPoi\Hooks::install();
    }

    function qrPoiBeforeRunPlugin(): void
    {
        // Charger les services si nécessaire
        if (is_dir(PLUGINS . 'QrPoi/services')) {
            foreach (glob(PLUGINS . 'QrPoi/services/' . '*.php') as $file) {
                require_once $file;
            }
        }
        
        // Hooks are now declared in param/hooks.json
    }

    /**
     * Hook: poi:get-qr-display
     */
    function qrPoiGetQrDisplay(array $data): array
    {
        $poiId = (int)($data['poi_id'] ?? 0);
        $user = $data['user'] ?? \Core\Auth\UsersManager::getCurrentUser();
        
        if ($poiId <= 0) {
            return $data;
        }

        $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('qr-poi');
        if ($plugin && $plugin->isInstalled() && $plugin->getConfigVal('activate')) {
            try {
                // --- UNIVERSAL POLICY INTEGRATION (Phase 10) ---
                $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
                $ctx = new \Core\Navigation\ConditionContext($user, ['id' => $poiId, 'type' => 'poi']);
                
                // Flag pour indiquer que le plugin est actif (utilisé pour afficher le bouton Gérer)
                $data['is_active'] = true;
                
                // Vérifier si l'affichage est activé (soit globalement pour ce POI, soit par quelqu'un)
                require_once PLUGINS . 'QrPoi/services/QrDisplayService.php';
                $displayService = new \QrPoi\Services\QrDisplayService(\Core\Core::getInstance()->getLogger());
                $data['show_qr_code'] = $displayService->isAnyQrCodeDisplayEnabled($poiId);
                
                // Répertoire public pour les QR codes (pour éviter 403)
                $qrDir = UPLOAD . 'QrPoi' . DS . 'qrcodes' . DS;
                if (!is_dir($qrDir)) {
                    @mkdir($qrDir, 0755, true);
                }
                
                // Récupérer le style personnalisé
                require_once PLUGINS . 'QrPoi/services/QrStyleService.php';
                $styleService = new \QrPoi\Services\QrStyleService(\Core\Core::getInstance()->getLogger());
                $style = $styleService->getStyle($poiId) ?: [];
                
                $qrFile = "poi_{$poiId}.png";
                $qrPath = $qrDir . $qrFile;
                $qrUrl = \Utils\Util::urlBuild("data/upload/QrPoi/qrcodes/{$qrFile}");
                
                // Si le fichier n'existe pas, on le génère/télécharge avec le style
                if (!file_exists($qrPath)) {
                    try {
                        require_once PLUGINS . 'QrPoi/services/QrCodeService.php';
                        $options = [
                            'size' => $style['size'] ?? 300,
                            'color' => $style['color'] ?? '000000',
                            'bgcolor' => $style['bgcolor'] ?? 'FFFFFF',
                            'qzone' => $style['qzone'] ?? 1,
                            'custom_text' => $style['custom_text'] ?? null,
                            'text_color' => $style['text_color'] ?? '000000',
                            'logo_path' => $style['logo_path'] ?? null
                        ];
                        
                        $redirectUrl = \QrPoi\Services\QrCodeService::generatePoiRedirectUrl($poiId);
                        $imageData = \QrPoi\Services\QrCodeService::generateAdvancedQrCode($redirectUrl, $options);
                        
                        if ($imageData) {
                            @file_put_contents($qrPath, $imageData);
                        }
                    } catch (\Exception $e) {
                        // Failback vers l'URL externe basique si la génération avancée échoue
                        $qrUrl = \QrPoi\Services\QrCodeService::generatePoiQrCodeUrl($poiId, 300);
                    }
                }
                
                $data['qr_code_url'] = $qrUrl;
                
                // On utilise la politique pour déterminer si l'utilisateur est "propriétaire" ou a le droit de voir
                $data['is_owner'] = $evaluator->canAccess(new \Core\Navigation\GenericResource('poi:qr:view'), $ctx);
                
            } catch (\Exception $e) {
                // Ignorer
            }
        }

        return $data;
    }

    // Charger les services dès l'inclusion du fichier
    // Cela garantit qu'elles sont disponibles avant l'instanciation des contrôleurs
    if (is_dir(PLUGINS . 'QrPoi/services')) {
        foreach (glob(PLUGINS . 'QrPoi/services/' . '*.php') as $file) {
            require_once $file;
        }
    }
}

