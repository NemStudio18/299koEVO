<?php

namespace Notifications\Services;

use Core\Logger;

defined('ROOT') or exit('Access denied!');

class NotificationService
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Envoie une ou plusieurs notifications
     * 
     * @param array $data [
     *   'user_id' => int|array|0, // Destinataire(s)
     *   'type' => string,          // e.g. 'forum.reply'
     *   'source_plugin' => string,
     *   'data' => [               // Payload JSON
     *      'key' => 'i18n_key',
     *      'params' => [],
     *      'url' => '',
     *      'actor' => ['id' => 1, 'name' => 'Jean'],
     *      'entity' => ['type' => 'topic', 'id' => 12]
     *   ],
     *   'external_id' => string|null // Pour déduplication
     * ]
     */
    public function send(array $data): bool
    {
        if (empty($data['type']) || empty($data['source_plugin']) || empty($data['data'])) {
            $this->logger->log(Logger::LEVEL_WARNING, "NotificationService::send - Données manquantes");
            return false;
        }

        try {
            require_once PLUGINS . 'notifications/entities/NotificationManager.php';
            $manager = new \Notifications\Entities\NotificationManager();

            $userIds = $data['user_id'] ?? 0;
            if ($userIds !== 0 && !is_array($userIds)) {
                $userIds = [$userIds];
            }

            // Filtrer l'acteur lui-même (expert advice #4)
            $actorId = $data['data']['actor']['id'] ?? null;
            
            // Configuration du type
            $typeConfig = $this->getTypeConfig($data['type']);
            
            // Flood protection pour auth.login_failed (expert advice #5)
            if ($data['type'] === 'auth.login_failed') {
                $data['external_id'] = 'auth.login_failed:' . ($userIds === 0 ? 'all' : implode(',', (array)$userIds)) . ':' . date('Y-m-d-H');
            }

            $notificationsToCreate = [];
            
            if ($userIds === 0) {
                // Broadcast logic (limited for MVP, but handled in manager)
                return $manager->create(0, $data['type'], $data['source_plugin'], $data['data'], $data['external_id'] ?? null);
            }

            foreach ($userIds as $uid) {
                // 1. Filtrer l'auto-notification
                if ($actorId !== null && $actorId == $uid) {
                    continue;
                }

                // 2. Vérifier les préférences utilisateur
                if (!$this->isUserNotifiable($uid, $data['type'], $typeConfig)) {
                    continue;
                }

                $notificationsToCreate[] = [
                    'user_id' => $uid,
                    'type' => $data['type'],
                    'source_plugin' => $data['source_plugin'],
                    'data' => $data['data'],
                    'external_id' => $data['external_id'] ?? null,
                    'priority' => $typeConfig['priority'] ?? 1
                ];
            }

            if (empty($notificationsToCreate)) {
                return true;
            }

            return $manager->createBulk($notificationsToCreate) > 0;

        } catch (\Throwable $e) {
            $this->logger->log(Logger::LEVEL_ERROR, "NotificationService::send - Erreur: " . $e->getMessage());
            return false;
        }
    }

    private function getTypeConfig(string $type): ?array
    {
        static $configs = null;
        if ($configs === null) {
            $file = PLUGINS . 'notifications/notification_types.json';
            if (file_exists($file)) {
                $configs = \Utils\Util::readJsonFile($file) ?: [];
            }
        }
        foreach ($configs as $cfg) {
            if ($cfg['type'] === $type) return $cfg;
        }
        return null;
    }

    private function isUserNotifiable($userId, string $type, ?array $typeConfig): bool
    {
        // Support pour les utilisateurs fictifs (Mode Démo)
        if ($userId >= 900000) {
            return true; // On considère qu'ils acceptent les notifications par défaut
        }

        $user = \Core\Auth\User::findPK($userId);
        if (!$user) {
            return false;
        }

        // Préférence globale
        if (!($user->attributes['allow_notifications'] ?? true)) {
            // Si c'est une notif de sécurité, on peut forcer (expert advice #3)
            if ($typeConfig && isset($typeConfig['can_disable']) && $typeConfig['can_disable'] === false) {
                return true;
            }
            return false;
        }

        // Préférence spécifique
        $prefKey = 'notif_type_' . str_replace('.', '_', $type);
        return (bool) ($user->attributes[$prefKey] ?? ($typeConfig['default_enabled'] ?? true));
    }

    public function getUnreadCount($userId): int
    {
        try {
            require_once PLUGINS . 'notifications/entities/NotificationManager.php';
            $manager = new \Notifications\Entities\NotificationManager();
            return $manager->getUnreadCount($userId);
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
