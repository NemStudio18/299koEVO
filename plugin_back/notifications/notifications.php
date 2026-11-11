<?php

namespace Notifications {

    use Core\Plugin\PluginsManager;
    use Core\Core;

    defined('ROOT') or exit('No direct script access allowed');

    class Hooks
    {
        public static function install(): void
        {
            // La création de la structure est gérée par Notification::query() (via JsonActiveRecord et FallbackStorage)
            // lors de la première utilisation ou du scan.
            \Core\Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_INFO, "[notifications::install] Notification entity will handle schema.");
        }
    }
}

namespace {

    function notificationsInstall(): void
    {
        \Notifications\Hooks::install();
    }

    function notificationsBeforeRunPlugin(): void
    {
        // Charger services
        if (is_dir(PLUGINS . 'notifications/services')) {
            foreach (glob(PLUGINS . 'notifications/services/*.php') as $file) {
                require_once $file;
            }
        }

        if (is_dir(PLUGINS . 'notifications/entities')) {
            foreach (glob(PLUGINS . 'notifications/entities/*.php') as $file) {
                require_once $file;
            }
        }

        // Hooks are now declared in param/hooks.json
    }

    function notificationsEndFrontHead()
    {
        echo '<link rel="stylesheet" href="' . \Utils\Util::urlBuild(PLUGINS . 'notifications/template/public.css') . '">';
    }

    /**
     * Hook: user:profile-settings
     */
    function notificationsUserProfileSettings($user): string
    {
        $allowNotifications = (bool) ($user->attributes['allow_notifications'] ?? true);
        $notifPush = (bool) ($user->attributes['notif_push'] ?? false);
        $notifEmail = (bool) ($user->attributes['notif_email'] ?? false);
        
        $core = \Core\Core::getInstance();
        $pwaEnabled = \Core\Plugin\PluginsManager::isActivePlugin('pwa');
        
        $html = '<div class="notification-settings-group">';
        
        // Main Toggle
        $html .= '
        <div class="setting-item">
            <label class="toggle-switch">
                <input type="checkbox" class="toggle-input" data-setting="allow_notifications" ' . ($allowNotifications ? 'checked' : '') . '>
                <span class="toggle-slider"></span>
            </label>
            <div class="setting-info">
                <span class="setting-label">Recevoir les notifications</span>
                <span class="setting-description">Activez pour recevoir des alertes sur votre compte.</span>
            </div>
        </div>';

        if ($allowNotifications) {
            $html .= '<div id="notif-prefs-container" style="margin-top: 1.5rem;">';
            
            // Channels Flex (previously grid)
            $html .= '<div class="settings-list">';
            
            if ($pwaEnabled) {
                $html .= '
                <div class="setting-item">
                    <label class="toggle-switch">
                        <input type="checkbox" class="toggle-input" data-setting="notif_push" ' . ($notifPush ? 'checked' : '') . '>
                        <span class="toggle-slider"></span>
                    </label>
                    <div class="setting-info">
                        <span class="setting-label">Notifications Push</span>
                        <span class="setting-description">Mobile et Desktop (PWA)</span>
                    </div>
                </div>';
            }

            $html .= '
            <div class="setting-item">
                <label class="toggle-switch">
                    <input type="checkbox" class="toggle-input" data-setting="notif_email" ' . ($notifEmail ? 'checked' : '') . '>
                    <span class="toggle-slider"></span>
                </label>
                <div class="setting-info">
                    <span class="setting-label">Notifications par Email</span>
                    <span class="setting-description">Rapport par mail.</span>
                </div>
            </div>';
            
            $html .= '</div>'; // End settings-list

            // Specific Subscriptions Grid
            $typesFile = PLUGINS . 'notifications/notification_types.json';
            if (file_exists($typesFile)) {
                $types = \Utils\Util::readJsonFile($typesFile) ?: [];
                $html .= '<div class="notif-types-group" style="margin-top: 2rem;">';
                $html .= '<h3 style="font-size: 0.9rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 1rem; border-bottom: 1px solid var(--panel-border); padding-bottom: 0.5rem;">Vos abonnements spécifiques</h3>';
                $html .= '<div class="notif-types-grid">';
                
                foreach ($types as $type) {
                    if (!($type['can_disable'] ?? true)) continue;
                    
                    $prefKey = 'notif_type_' . str_replace('.', '_', $type['type']);
                    $enabled = (bool) ($user->attributes[$prefKey] ?? ($type['default_enabled'] ?? true));
                    
                    $html .= '
                    <div class="notif-type-card">
                        <label class="toggle-switch">
                            <input type="checkbox" class="toggle-input" data-setting="' . $prefKey . '" ' . ($enabled ? 'checked' : '') . '>
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="toggle-label">' . ($type['description'] ?? $type['type']) . '</span>
                    </div>';
                }
                $html .= '</div>'; // End notif-types-grid
                $html .= '</div>'; // End notif-types-group
            }
            
            $html .= '</div>'; // End notif-prefs-container
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Hook: user:profile-allowed-settings
     */
    function notificationsUserProfileAllowedSettings(array $allowed): array
    {
        $allowed[] = 'allow_notifications';
        $allowed[] = 'notif_push';
        $allowed[] = 'notif_email';

        // Ajouter les types dynamiques depuis le fichier JSON
        $typesFile = PLUGINS . 'notifications/notification_types.json';
        if (file_exists($typesFile)) {
            $types = \Utils\Util::readJsonFile($typesFile) ?: [];
            foreach ($types as $type) {
                $allowed[] = 'notif_type_' . str_replace('.', '_', $type['type']);
            }
        }

        return $allowed;
    }

    function notificationsEndAdminHead()
    {
        echo '<link rel="stylesheet" href="' . \Utils\Util::urlBuild(PLUGINS . 'notifications/template/public.css') . '">';
    }

    /**
     * Hook callback: Envoie une notification
     * 
     * @param array $data [
     *   'user_id' => int|array,  // ID utilisateur OU array d'IDs OU 0 pour broadcast
     *   'source_plugin' => string,
     *   'entity_type' => string,
     *   'entity_id' => int,
     *   'notification_type' => string,
     *   'title' => string,
     *   'message' => string,
     *   'link_url' => string
     * ]
     * @return bool
     */
    function notificationsSend(array $data): bool
    {
        try {
            if (!class_exists('\Notifications\Services\NotificationService')) {
                return false;
            }

            $logger = \Core\Core::getInstance()->getLogger();
            $service = new \Notifications\Services\NotificationService($logger);

            return $service->send($data);
        } catch (\Throwable $e) {
            \Core\Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_ERROR, "notificationsSend Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Hook: notifications:collect
     */
    function notificationsCollect(array $data): array
    {
        try {
            if (!class_exists('\Notifications\Services\NotificationService')) {
                return $data;
            }

            $logger = \Core\Core::getInstance()->getLogger();
            $service = new \Notifications\Services\NotificationService($logger);
            
            $userId = (int) ($data['userId'] ?? 0);
            if ($userId <= 0) return $data;

            $unreadCount = $service->getUnreadCount($userId);
            
            $data['totalUnread'] += $unreadCount;
            if ($unreadCount > 0) {
                $data['breakdown']['notifications'] = $unreadCount;
            }

            return $data;
        } catch (\Exception $e) {
            return $data;
        }
    }

    /**
     * Hook: user:avatar-menu
     */
    function notificationsUserAvatarMenu(array $data): array
    {
        $items = $data['items'] ?? [];
        $user = \Core\Auth\UsersManager::getCurrentUser();
        if (!$user) return $data;

        // Si l'utilisateur a désactivé les notifications, on ne montre rien
        if (!($user->attributes['allow_notifications'] ?? true)) {
            return $data;
        }

        $logger = \Core\Core::getInstance()->getLogger();
        $service = new \Notifications\Services\NotificationService($logger);
        $count = $service->getUnreadCount((int)$user->id);

        $router = \Core\Router\Router::getInstance();
        try {
            $url = $router->generate('notifications-list');
        } catch (\Exception $e) {
            $url = '/notifications';
        }

        $items[] = [
            'label' => 'Notifications',
            'url' => $router->generate('profile') . '?tab=notifications',
            'icon' => 'fa-solid fa-bell',
            'order' => 20,
            'badge' => $count > 0 ? $count : null
        ];

        $data['items'] = $items;
        return $data;
    }

    /**
     * Hook: user:avatar-overlay
     */
    function notificationsUserAvatarOverlay(): string
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext(\Core\Auth\UsersManager::getCurrentUser());
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('notifications:access'), $ctx)) return '';
        
        $user = \Core\Auth\UsersManager::getCurrentUser();
        if (!($user->attributes['allow_notifications'] ?? true)) return '';

        $logger = \Core\Core::getInstance()->getLogger();
        $service = new \Notifications\Services\NotificationService($logger);
        $count = $service->getUnreadCount((int)$user->id);

        try {
            $url = \Core\Router\Router::getInstance()->generate('notifications-list');
        } catch (\Exception $e) {
            $url = '/notifications';
        }

        $html = '<div class="nav-avatar-overlay-bell">';
        $html .= '<i class="fa-solid fa-bell"></i>';
        if ($count > 0) {
            $html .= '<span class="nav-badge">' . $count . '</span>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Hook: user:profile-tabs
     */
    function notificationsUserProfileTabs($user): string
    {
        if (!is_object($user) || !($user->attributes['allow_notifications'] ?? true)) return '';

        $logger = \Core\Core::getInstance()->getLogger();
        $service = new \Notifications\Services\NotificationService($logger);
        $count = $service->getUnreadCount((int)$user->id);

        $badgeHtml = '';
        if ($count > 0) {
            $badgeHtml = ' <span class="nav-badge" style="margin-left: 5px;">' . $count . '</span>';
        }

        return '
        <button type="button" class="profile-tab-button" role="tab" aria-selected="false" aria-controls="tab-notifications" id="tab-button-notifications" data-tab="notifications">
            <i class="fa-solid fa-bell"></i> <span>Notifications' . $badgeHtml . '</span>
        </button>';
    }

    /**
     * Hook: user:profile-tabs-content
     */
    function notificationsUserProfileTabsContent($user): string
    {
        if (!is_object($user) || !($user->attributes['allow_notifications'] ?? true)) return '';

        require_once PLUGINS . 'notifications/entities/NotificationManager.php';
        $manager = new \Notifications\Entities\NotificationManager();
        $notifications = $manager->getUserNotifications($user->id);
        $unreadCount = $manager->getUnreadCount($user->id);

        $html = '<div class="profile-tab-content" id="tab-notifications" role="tabpanel" aria-labelledby="tab-button-notifications">';
        $html .= '<div class="profile-stats">';
        $html .= '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">';
        $html .= '<h2 style="margin: 0; font-size: 1.5rem;">Mes Notifications</h2>';
        
        if ($unreadCount > 0) {
            $html .= '<form method="POST" action="/notifications/mark-all-read" style="margin: 0;">';
            $html .= '<input type="hidden" name="token" value="' . \Core\Security\Csrf::token() . '" />';
            $html .= '<button type="submit" class="btn-mark-all" style="padding: 8px 15px; font-size: 0.85rem;">Tout marquer comme lu</button>';
            $html .= '</form>';
        }
        $html .= '</div>';

        if (empty($notifications)) {
            $html .= '<div style="padding: 3rem; text-align: center; background: var(--panel-bg); border-radius: var(--panel-radius); border: 1px dashed var(--panel-border);">';
            $html .= '<i class="fa-solid fa-bell-slash" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>';
            $html .= '<p style="color: #6c757d;">Aucune notification pour le moment.</p>';
            $html .= '</div>';
        } else {
            $html .= '<div class="notifications-list" style="display: flex; flex-direction: column; gap: 0.75rem;">';
            foreach ($notifications as $notif) {
                $unreadClass = (($notif['read_at'] ?? null) === null) ? 'unread' : '';
                $data = $notif['data'] ?? [];
                
                // Rendu i18n dynamique
                $title = \Core\Lang::get($data['key'] ?? $notif['type'], $data['params'] ?? []);
                $actorName = $data['actor']['name'] ?? 'Système';
                $actorId = $data['actor']['id'] ?? null;
                
                $html .= '<div class="notification ' . $unreadClass . '" id="profile-notif-' . $notif['id'] . '" style="background: var(--panel-bg); border: 1px solid var(--panel-border); border-radius: 8px; padding: 1rem; display: grid; grid-template-columns: auto 1fr auto; gap: 1rem; align-items: start;">';
                
                // Badge source
                $html .= '<div class="notif-source" style="font-size: 0.7rem; text-transform: uppercase; color: var(--text-muted); background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 4px; font-weight: 700;">' . htmlspecialchars($notif['source_plugin']) . '</div>';
                
                $html .= '<div class="notif-content">';
                $html .= '<strong style="display: block; margin-bottom: 0.25rem; color: var(--primary-color);">' . htmlspecialchars($title) . '</strong>';
                
                if (!empty($data['url'])) {
                    $html .= '<a href="' . htmlspecialchars($data['url']) . '" class="notif-link" style="display: inline-block; margin-top: 0.5rem; font-size: 0.85rem; font-weight: 600; color: var(--primary-color);">Détails <i class="fa-solid fa-external-link" style="font-size: 0.75rem;"></i></a>';
                }
                $html .= '</div>';
                
                $html .= '<div class="notif-meta" style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem;">';
                $createdAt = $notif['created_at'] ?? null;
                $html .= '<span style="font-size: 0.75rem; color: var(--text-muted);">' . ($createdAt ? date('d/m/Y H:i', strtotime($createdAt)) : '—') . '</span>';

                
                if (($notif['read_at'] ?? null) === null) {
                    $html .= '<button class="mark-read" data-id="' . $notif['id'] . '" data-token="' . \Core\Security\Csrf::token() . '" style="background: none; border: 1px solid var(--primary-color); color: var(--primary-color); padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; cursor: pointer;">Lue</button>';
                }
                $html .= '</div>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        $html .= '</div></div>';

        // Add AJAX script for "Mark as read" buttons
        $html .= '<script>
        (function() {
            document.querySelectorAll("#tab-notifications .mark-read").forEach(btn => {
                btn.addEventListener("click", function(e) {
                    e.preventDefault();
                    const id = this.dataset.id;
                    const token = this.dataset.token;
                    const card = document.getElementById("profile-notif-" + id);
                    
                    if (card) {
                        card.classList.remove("unread");
                        card.style.opacity = "0.7";
                        this.remove();
                    }
                    
                    const formData = new FormData();
                    formData.append("id", id);
                    formData.append("token", token);
                    
                    fetch("/notifications/mark-read/" + id, {
                        method: "POST",
                        headers: { "X-Requested-With": "XMLHttpRequest" },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) console.error("Error marking notification:", data.error);
                    })
                    .catch(error => console.error("AJAX Error:", error));
                });
            });
        })();
        </script>';

        return $html;
    }

    /**
     * Hook: notifications:mark-as-read-external
     */
    function notificationsMarkAsReadExternal(array $data): void
    {
        $userId = $data['user_id'] ?? 0;
        $externalId = $data['external_id'] ?? '';
        
        if ($userId > 0 && !empty($externalId)) {
            require_once PLUGINS . 'notifications/entities/NotificationManager.php';
            $manager = new \Notifications\Entities\NotificationManager();
            $manager->markAsReadByExternalId((int)$userId, (string)$externalId);
        }
    }
}
