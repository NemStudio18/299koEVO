<?php

namespace Core\Storage;

/**
 * Logger d'urgence pour le Hub de données.
 * Écrit directement dans data/debug_hub.log sans dépendances.
 */
class EmergencyLogger
{
    public static function log(string $message): void
    {
        // On délègue au nouveau Logger robuste
        // Les traces de routine (STORAGE_HUB, DB_QUERY, AUTH_LOGIN...) passent en DEBUG
        // (visibles uniquement avec "debug": true dans config.json).
        // Les erreurs restent toujours journalisées.
        $logger = \Core\Logger::getInstance();
        if (preg_match('/^(ERROR|CRITICAL)\b|_ERROR\b/', $message)) {
            $logger->error("[Hub] " . $message);
        } else {
            $logger->debug("[Hub] " . $message);
        }
    }
}
