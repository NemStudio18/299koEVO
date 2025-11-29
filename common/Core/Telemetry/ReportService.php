<?php

namespace Core\Telemetry;

use Core\Core;
use Core\Logger;
use Core\Extensions\ExtensionsService;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 *
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

/**
 * Service pour envoyer des rapports de bugs et feedback
 */
class ReportService
{
    private Core $core;
    private Logger $logger;
    private ExtensionsService $extensionsService;
    private TelemetryService $telemetryService;

    public function __construct()
    {
        $this->core = Core::getInstance();
        $this->logger = $this->core->getLogger();
        $this->extensionsService = $this->core->extensions();
        $this->telemetryService = new TelemetryService();
    }

    /**
     * Envoie un rapport (bug, feature request, question, etc.)
     * 
     * @param string $type Type de rapport: 'bug', 'feature', 'question', 'other'
     * @param string $title Titre du rapport
     * @param string $description Description détaillée
     * @param string|null $email Email du webmaster (optionnel)
     * @param string|null $plugin Plugin concerné (optionnel)
     * @param string|null $screenshot Screenshot en base64 (optionnel)
     * @return array ['success' => bool, 'message' => string, 'report_id' => string|null]
     */
    public function sendReport(
        string $type,
        string $title,
        string $description,
        ?string $email = null,
        ?string $plugin = null,
        ?string $screenshot = null
    ): array {
        try {
            $installationId = $this->telemetryService->getInstallationId();
            $marketplaceUrl = $this->core->getConfigVal('marketplaceUrl');
            
            if (empty($marketplaceUrl)) {
                return [
                    'success' => false,
                    'message' => 'Marketplace URL not configured'
                ];
            }

            // Préparer les données
            $data = [
                'type' => $type,
                'title' => $title,
                'description' => $description,
                'cms_version' => VERSION,
                'php_version' => PHP_VERSION,
                'url' => $this->core->getConfigVal('siteUrl'),
            ];

            if ($email !== null) {
                $data['email'] = $email;
            }

            if ($plugin !== null) {
                $data['plugin'] = $plugin;
            }

            if ($screenshot !== null) {
                $data['screenshot'] = $screenshot;
            }

            // Envoyer à l'API
            $endpoint = rtrim($marketplaceUrl, '/') . '/repository/api/report';
            $curl = new \Core\Http\Curl($endpoint);
            $curl->post();
            $curl->sendAsJson = true;
            $curl->setDatas($data);
            
            // Ajouter l'installation_id comme token d'authentification
            $curl->addOption(CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $installationId,
                'Content-Type: application/json'
            ]);

            $resp = $curl->execute()->getResponse();

            if ($resp['code'] === 200) {
                $responseData = json_decode($resp['body'], true);
                $this->logger->info('Report sent successfully: ' . ($responseData['report_id'] ?? 'unknown'));
                return [
                    'success' => true,
                    'message' => 'Report submitted successfully',
                    'report_id' => $responseData['report_id'] ?? null
                ];
            } else {
                $errorMsg = 'Failed to send report - HTTP ' . $resp['code'];
                if (!empty($resp['body'])) {
                    $errorData = json_decode($resp['body'], true);
                    if (isset($errorData['error'])) {
                        $errorMsg = $errorData['error'];
                    }
                }
                $this->logger->error('Report: ' . $errorMsg);
                return [
                    'success' => false,
                    'message' => $errorMsg
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Report: Exception - ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while sending the report: ' . $e->getMessage()
            ];
        }
    }
}

