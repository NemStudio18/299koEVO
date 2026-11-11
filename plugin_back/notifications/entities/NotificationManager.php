<?php

namespace Notifications\Entities;

defined('ROOT') or exit('Access denied!');

class NotificationManager
{
    public function __construct()
    {
        // Plus besoin de PDO ici, le Storage s'en occupe via Notification::class
    }

    /**
     * Crée une ou plusieurs notifications en chunk
     * 
     * @param array $notifications Tableau de tableaux associatifs
     * @return int Nombre de notifications créées
     */
    public function createBulk(array $notifications): int
    {
        if (empty($notifications)) {
            return 0;
        }

        $count = 0;
        Notification::beginTransaction();
        try {
            foreach ($notifications as $notifData) {
                $notif = new Notification($notifData);
                if ($notif->save()) {
                    $count++;
                    // Dispatcher les notifications externes si nécessaire (Email/Push)
                    if (($notifData['user_id'] ?? 0) > 0) {
                        $this->dispatchNotification(
                            $notifData['user_id'], 
                            $notifData['type'], 
                            $notifData['data']
                        );
                    }
                }
            }
            Notification::commit();
        } catch (\Throwable $e) {
            Notification::rollBack();
            \Core\Core::getInstance()->getLogger()->error("NotificationManager::createBulk - Erreur: " . $e->getMessage());
        }

        return $count;
    }

    /**
     * Crée une notification unique
     */
    public function create(
        $userId,
        string $type,
        string $sourcePlugin,
        array $data,
        ?string $externalId = null
    ): bool {
        $notif = new Notification([
            'user_id' => $userId,
            'type' => $type,
            'source_plugin' => $sourcePlugin,
            'data' => $data,
            'external_id' => $externalId,
            'created_at' => date('Y-m-d H:i:s'),
            'priority' => 1,
            'is_read' => false
        ]);
        return $notif->save();
    }

    /**
     * Dispatch la notification vers les canaux externes (Email, Push)
     */
    private function dispatchNotification($userId, string $type, array $data): void
    {
        $user = \Core\Auth\User::findPK($userId);
        if (!$user || !($user->attributes['allow_notifications'] ?? true)) {
            return;
        }

        // Email
        if ($user->attributes['notif_email'] ?? false) {
            $this->sendEmailNotification($user, $type, $data);
        }

        // Push
        if ($user->attributes['notif_push'] ?? false) {
            $this->sendPushNotification($user, $type, $data);
        }
    }

    private function sendEmailNotification($user, string $type, array $data): void
    {
        $core = \Core\Core::getInstance();
        $siteName = $core->getConfigVal('siteName');
        $to = $user->attributes['email'];
        if (empty($to)) return;

        $title = \Core\Lang::get($data['key'] ?? $type, $data['params'] ?? []);
        $subject = "[$siteName] " . $title;
        $body = "Bonjour " . $user->attributes['username'] . ",\n\n" . $title . "\n\n";
        
        if (!empty($data['url'])) {
            $siteUrl = rtrim($core->getConfigVal('siteUrl'), '/');
            $url = (strpos($data['url'], 'http') === 0) ? $data['url'] : $siteUrl . '/' . ltrim($data['url'], '/');
            $body .= "Voir : " . $url . "\n\n";
        }
        
        $body .= "Cordialement,\nL'équipe " . $siteName;

        try {
            $from = 'noreply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost');
            \Utils\Util::sendEmail($from, $from, $to, $subject, nl2br($body));
        } catch (\Exception $e) {
            $core->getLogger()->error("NotificationManager: Error sending email: " . $e->getMessage());
        }
    }

    private function sendPushNotification($user, string $type, array $data): void
    {
        // Logique de push simplifiée ou déléguée
    }

    public function getUserNotifications($userId, bool $onlyUnread = false): array
    {
        $criteria = ['user_id' => $userId];
        if ($onlyUnread) {
            $criteria['is_read'] = false;
        }

        $results = Notification::query()
            ->where($criteria['user_id'] ? 'user_id' : '', '=', $criteria['user_id']) // Temporary hack for criteria array
            ->orderBy('priority', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->limit(50)
            ->get();
        
        $output = [];
        foreach ($results as $notif) {
            $attrs = $notif->attributes;
            // Normaliser les clés qui peuvent être absentes (read_at n'est pas défini à la création)
            $attrs['read_at']    = $attrs['read_at'] ?? null;
            $attrs['created_at'] = $attrs['created_at'] ?? null;
            $output[] = $attrs;
        }
        return $output;
    }

    public function getUnreadCount($userId): int
    {
        return Notification::query()->where('user_id', '=', $userId)->where('is_read', '=', false)->count();
    }

    public function markAsRead(int $notificationId, $userId): bool
    {
        $notif = Notification::findPK($notificationId);
        if ($notif && (int)$notif->user_id === (int)$userId) {
            $notif->is_read = true;
            $notif->read_at = date('Y-m-d H:i:s');
            return $notif->save();
        }
        return false;
    }

    public function markAllAsRead($userId): bool
    {
        $notifs = Notification::query()->where('user_id', '=', $userId)->where('is_read', '=', false)->get();
        Notification::beginTransaction();
        foreach ($notifs as $notif) {
            $notif->is_read = true;
            $notif->read_at = date('Y-m-d H:i:s');
            $notif->save();
        }
        return Notification::commit();
    }

    /**
     * Marque les notifications comme lues via un ID externe (ex: ID du message)
     */
    public function markAsReadByExternalId(int $userId, string $externalId): void
    {
        $notifs = Notification::query()
            ->where('user_id', '=', $userId)
            ->where('external_id', '=', $externalId)
            ->where('is_read', '=', false)
            ->get();
        
        foreach ($notifs as $notif) {
            $notif->is_read = true;
            $notif->read_at = date('Y-m-d H:i:s');
            $notif->save();
        }
    }
}
