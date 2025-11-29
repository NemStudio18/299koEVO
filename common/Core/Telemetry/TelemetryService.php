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
 * Service de télémétrie pour envoyer des métriques au marketplace
 */
class TelemetryService
{
    private Core $core;
    private Logger $logger;
    private TelemetryCollector $collector;
    private ExtensionsService $extensionsService;
    private string $stateFile;

    public function __construct()
    {
        $this->core = Core::getInstance();
        $this->logger = $this->core->getLogger();
        $this->collector = new TelemetryCollector();
        $this->extensionsService = $this->core->extensions();
        $this->stateFile = DATA . 'telemetry_state.json';
    }

    /**
     * Génère et stocke un UUID unique pour cette installation
     * 
     * @return string
     */
    public function generateInstallationId(): string
    {
        // Générer un UUID v4
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variant bits
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

        // Stocker dans la config
        $config = $this->core->getConfig();
        $config['telemetry_installation_id'] = $uuid;
        $this->core->saveConfig($config, $config);

        return $uuid;
    }

    /**
     * Récupère l'installation_id, le génère si nécessaire
     * 
     * @return string
     */
    public function getInstallationId(): string
    {
        $config = $this->core->getConfig();
        if (empty($config['telemetry_installation_id'])) {
            return $this->generateInstallationId();
        }
        return $config['telemetry_installation_id'];
    }

    /**
     * Vérifie si une synchronisation est nécessaire et l'envoie si besoin
     * 
     * @param bool $force Force l'envoi même si le délai n'est pas écoulé
     * @return bool
     */
    public function checkAndSend(bool $force = false): bool
    {
        $config = $this->core->getConfig();
        $level = (int) ($config['telemetry_level'] ?? 0);

        // Même en mode 0, on doit envoyer au moins une fois lors de l'installation
        $state = $this->readState();
        $isFirstInstall = !isset($state['first_sent']);

        if ($level === 0 && !$isFirstInstall && !$force) {
            // Mode 0 et déjà envoyé une fois, ne rien faire
            return false;
        }

        // Vérifier le timestamp pour les envois périodiques
        if (!$force && !$isFirstInstall) {
            $lastSent = $state['last_sent'] ?? 0;
            $interval = 24 * 60 * 60; // 24 heures en secondes

            if (time() - $lastSent < $interval) {
                // Pas encore le moment
                return false;
            }
        }

        // Envoyer les données
        return $this->send($level, $isFirstInstall);
    }

    /**
     * Envoie les données de télémétrie à l'API
     * 
     * @param int $level Niveau de collecte (0, 1, 2)
     * @param bool $isFirstInstall Est-ce le premier envoi (installation)
     * @return bool
     */
    public function send(int $level, bool $isFirstInstall = false): bool
    {
        try {
            $installationId = $this->getInstallationId();

            // Collecter les données selon le niveau
            if ($level === 0 || $isFirstInstall) {
                // Même en mode 0, on envoie les données minimales lors de l'installation
                $data = $this->collector->collectMinimal();
            } elseif ($level === 1) {
                $data = $this->collector->collectBasic();
            } else {
                $data = $this->collector->collectExtended();
            }

            // Ajouter le niveau et le type d'envoi
            $data['level'] = $level;
            $data['sync_type'] = $isFirstInstall ? 'install' : 'periodic';

            // Envoyer à l'API
            $marketplaceUrl = $this->core->getConfigVal('marketplaceUrl');
            if (empty($marketplaceUrl)) {
                $this->logger->warning('Telemetry: marketplaceUrl not configured');
                return false;
            }

            $endpoint = rtrim($marketplaceUrl, '/') . '/repository/api/telemetry';
            // Utiliser directement Curl pour éviter les ajouts automatiques de MarketPlaceCurl
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
                // Mettre à jour l'état
                $this->updateState($isFirstInstall);
                $this->logger->info('Telemetry: Data sent successfully');
                return true;
            } else {
                $this->logger->error('Telemetry: Failed to send data - HTTP ' . $resp['code']);
                if (!empty($resp['body'])) {
                    $this->logger->error('Telemetry: ' . $resp['body']);
                }
                return false;
            }
        } catch (\Exception $e) {
            $this->logger->error('Telemetry: Exception - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Lit l'état de la télémétrie
     * 
     * @return array
     */
    private function readState(): array
    {
        if (!file_exists($this->stateFile)) {
            return [];
        }
        $content = file_get_contents($this->stateFile);
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Met à jour l'état de la télémétrie
     * 
     * @param bool $isFirstInstall
     * @return void
     */
    private function updateState(bool $isFirstInstall): void
    {
        $state = $this->readState();
        $state['last_sent'] = time();
        if ($isFirstInstall) {
            $state['first_sent'] = time();
        }
        file_put_contents($this->stateFile, json_encode($state, JSON_PRETTY_PRINT));
    }

    /**
     * Force l'envoi des données (utilisé depuis l'interface admin)
     * 
     * @return bool
     */
    public function forceSend(): bool
    {
        $config = $this->core->getConfig();
        $level = (int) ($config['telemetry_level'] ?? 0);
        $state = $this->readState();
        $isFirstInstall = !isset($state['first_sent']);
        return $this->send($level, $isFirstInstall);
    }

    /**
     * Récupère le timestamp de la dernière synchronisation
     * 
     * @return int|null Timestamp Unix ou null si jamais synchronisé
     */
    public function getLastSync(): ?int
    {
        $state = $this->readState();
        return $state['last_sent'] ?? null;
    }

    /**
     * Vérifie si une synchronisation initiale a déjà été effectuée
     * 
     * @return bool
     */
    public function hasSynced(): bool
    {
        $state = $this->readState();
        return isset($state['first_sent']);
    }
}

