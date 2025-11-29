<?php

namespace Core\Telemetry;

use Core\Core;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 *
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

/**
 * Hook appelé avant chaque exécution de plugin
 * Vérifie si une synchronisation de télémétrie est nécessaire
 */
function telemetryCheckSync()
{
    try {
        $service = new TelemetryService();
        // Vérifier et envoyer si nécessaire (ne bloque pas si échec)
        $service->checkAndSend();
    } catch (\Exception $e) {
        // Ne pas bloquer le CMS en cas d'erreur
        Core::getInstance()->getLogger()->warning('Telemetry hook error: ' . $e->getMessage());
    }
}

