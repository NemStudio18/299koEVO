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

    public static function formatTitle(string $type, array $data): string
    {
        $key = (string) ($data['key'] ?? $type);
        $params = is_array($data['params'] ?? null) ? array_values($data['params']) : [];
        $translated = \Core\Lang::get($key, $params);
        if ($translated !== $key) {
            return $translated;
        }
        $fallback = (string) ($data['fallback_title'] ?? $data['title'] ?? $key);
        if (!empty($params) && strpos($fallback, '%') !== false) {
            $formatted = @vsprintf($fallback, $params);
            if (is_string($formatted) && $formatted !== '') {
                return $formatted;
            }
        }
        return $fallback;
    }

    private function sendEmailNotification($user, string $type, array $data): void
    {
        $core = \Core\Core::getInstance();
        $siteName = $core->getConfigVal('siteName');
        $to = $user->attributes['email'] ?? ($user->email ?? '');
        if (empty($to)) return;

        $title = self::formatTitle($type, $data);
        $username = $user->attributes['username'] ?? ($user->username() ?? '');
        $subject = "[$siteName] " . $title;
        $body = "Bonjour " . $username . ",\n\n" . $title . "\n\n";
        
        if (!empty($data['url'])) {
            $siteUrl = rtrim((string) $core->getConfigVal('siteUrl'), '/');
            $url = (strpos($data['url'], 'http') === 0) ? $data['url'] : $siteUrl . '/' . ltrim($data['url'], '/');
            $body .= "Voir : " . $url . "\n\n";
        }
        
        $body .= "Cordialement,\nL'équipe " . $siteName;

        try {
            $host = parse_url((string) $core->getConfigVal('siteUrl'), PHP_URL_HOST) ?: ($_SERVER['SERVER_NAME'] ?? 'localhost');
            $host = preg_replace('/[^a-zA-Z0-9.-]/', '', (string) $host) ?: 'localhost';
            $from = 'noreply@' . $host;
            \Utils\Util::sendEmail($from, $from, $to, $subject, $body);
        } catch (\Exception $e) {
            $core->getLogger()->error("NotificationManager: Error sending email: " . $e->getMessage());
        }
    }

    private function sendPushNotification($user, string $type, array $data): void
    {
        // Logique de push simplifiée ou déléguée
    }

    public function getUserNotifications($userId, bool $onlyUnread = false, int $limit = 50): array
    {
        $results = Notification::query()
            ->where('user_id', '=', (int) $userId)
            ->orderBy('created_at', 'DESC')
            ->get();

        $output = [];
        foreach ($results as $notif) {
            $attrs = $notif->attributes;
            $isRead = !empty($attrs['is_read']) || !empty($attrs['read_at']);
            if ($onlyUnread && $isRead) {
                continue;
            }
            $data = is_array($attrs['data'] ?? null) ? $attrs['data'] : [];
            $type = (string) ($attrs['type'] ?? '');
            $attrs['id']         = $attrs['id'] ?? ($notif->id ?? '');
            $attrs['is_read']    = $isRead ? 1 : 0;
            $attrs['read_at']    = $attrs['read_at'] ?? null;
            $attrs['created_at'] = $attrs['created_at'] ?? null;
            $attrs['title']      = self::formatTitle($type, $data);
            $attrs['message']    = (string) ($data['message'] ?? '');
            $attrs['link_url']   = (string) ($data['url'] ?? '');
            $output[] = $attrs;
            if (count($output) >= $limit) {
                break;
            }
        }
        return $output;
    }

    public function getUnreadCount($userId): int
    {
        $results = Notification::query()
            ->where('user_id', '=', (int) $userId)
            ->get();
        $count = 0;
        foreach ($results as $notif) {
            $attrs = $notif->attributes;
            if (empty($attrs['is_read']) && empty($attrs['read_at'])) {
                $count++;
            }
        }
        return $count;
    }

    public function markAsRead(int|string $notificationId, $userId): bool
    {
        if ($notificationId === '' || $notificationId === 0 || $notificationId === '0') {
            return false;
        }
        $notif = Notification::findPK($notificationId);
        if ($notif && (int) ($notif->attributes['user_id'] ?? $notif->user_id ?? 0) === (int) $userId) {
            $notif->is_read = true;
            $notif->read_at = date('Y-m-d H:i:s');
            return $notif->save();
        }
        return false;
    }

    public function markAllAsRead($userId): bool
    {
        $notifs = Notification::query()->where('user_id', '=', (int) $userId)->get();
        Notification::beginTransaction();
        foreach ($notifs as $notif) {
            $attrs = $notif->attributes;
            if (!empty($attrs['is_read']) && !empty($attrs['read_at'])) {
                continue;
            }
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
