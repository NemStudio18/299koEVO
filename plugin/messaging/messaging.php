<?php

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('No direct script access allowed');

function messagingBeforeRunPlugin()
{
    $core   = \Core\Core::getInstance();
    $logger = $core->getLogger();
    $logger->log(\Core\Logger::LEVEL_DEBUG, "[messagingBeforeRunPlugin] Chargement du plugin 'messaging' (controllers)");

    require_once PLUGINS . 'messaging/controllers/MessagingPublicController.php';
    
    // Hooks are now registered in hooks.json

    // Auto-injection pour le profil/dashboard
    if (class_exists('\Template\Template')) {
        \Template\Template::addGlobal('messagingEnabled', true);
        \Template\Template::addGlobal('emailEnabled', true);
    }
}

/**
 * Hook: notifications:collect
 */
function messagingNotificationsCollect(array $data): array
{
    if (!isset($data['userId'])) {
        return $data;
    }

    try {
        $messagingService = new \Messaging\Services\MessagingService();
        $messagingUnread = $messagingService->getUnreadCount((int)$data['userId']);
        
        $data['breakdown']['messaging'] = $messagingUnread;
        $data['totalUnread'] += $messagingUnread;
    } catch (\Exception $e) {
        // Silencieux
    }

    return $data;
}

/**
 * Injection du CSS du plugin dans le header
 */
function messagingEndFrontHead($param = ''): string
{
    $output = (string)$param;
    $cssPath = PLUGINS . 'messaging/template/public.css';
    if (file_exists($cssPath)) {
        $output .= '<link rel="stylesheet" href="' . \Utils\Util::urlBuild($cssPath) . '" type="text/css"/>' . "\n";
    }
    return $output;
}

/**
 * Hook pour la recherche globale : cherche dans les messages privés de l'utilisateur
 */
function messagingSearchResultsHook(array $data): array
{
    $query = $data['query'];
    $results = [];
    $user = \Core\Auth\UsersManager::getCurrentUser();
    if (!$user || !isset($user->attributes['id'])) {
        return $data;
    }

    try {
        $service = new \Messaging\Services\MessagingService();
        $userId = (int)$user->attributes['id'];
        
        // On récupère reçus et envoyés
        $inbox = $service->getInboxMessages($userId);
        $sent = $service->getSentMessages($userId);
        $all = array_merge($inbox, $sent);

        foreach ($all as $msg) {
            if (stripos($msg['subject'], $query) !== false || stripos($msg['content'], $query) !== false) {
                $results[] = [
                    'title' => 'MP: ' . $msg['subject'],
                    'excerpt' => substr(strip_tags($msg['content']), 0, 150) . '...',
                    'url' => \Core\Router\Router::getInstance()->generate('messaging-read', ['id' => $msg['id']]),
                    'icon' => 'fa-solid fa-envelope',
                    'source' => 'messaging',
                    'source_label' => 'Messagerie',
                    'date' => $msg['created_at'],
                    'date_formatted' => date('d/m/Y', strtotime($msg['created_at'])),
                    'score' => (stripos($msg['subject'], $query) !== false ? 10 : 0) + (stripos($msg['content'], $query) !== false ? 5 : 0)
                ];
            }
        }
        $data['results'] = array_merge($data['results'], $results);
    } catch (\Exception $e) {
        // Ignorer
    }
    return $data;
}

/**
 * Hook: user:profile-stats
 * Envoie les statistiques de messagerie vers le profil utilisateur
 */
function messagingUserProfileStats(array $stats): array
{
    $count = 0;
    $user = \Core\Auth\UsersManager::getCurrentUser();
    if ($user && isset($user->attributes['id'])) {
        try {
            $service = new \Messaging\Services\MessagingService();
            $sentMessages = $service->getSentMessages((int)$user->attributes['id']);
            $count = count($sentMessages);
        } catch (\Exception $e) {
            $count = '-';
        }
    }

    $stats[] = [
        'label' => 'Messages envoyés',
        'value' => $count,
        'color' => 'var(--primary-color)',
        'icon' => 'fa-solid fa-paper-plane'
    ];

    return $stats;
}

/**
 * Hook pour injecter le bouton "Message privé" dans les posts du forum
 * @param array|null $post Données du post (passées depuis le template)
 */
function messagingForumTopicPostActions($post = null): string
{
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('messaging');
    if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
        return '';
    }
    
    // Si $post n'est pas fourni ou n'est pas un tableau, ne rien faire
    if (!is_array($post)) {
        return '';
    }
    
    // Vérifier les conditions pour afficher le bouton
    $user = \Core\Auth\UsersManager::getCurrentUser();
    $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
    $ctx = new \Core\Navigation\ConditionContext($user);
    if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('messaging:access'), $ctx)) {
        return '';
    }
    
    $currentUserId = (int)$user->attributes['id'];
    $postAuthorId = (int)($post['author']['id'] ?? 0);
    $authorAllowsPrivateMessages = (bool)($post['author_allows_private_messages'] ?? false);
    
    // Ne pas afficher si c'est le même utilisateur ou si l'auteur n'autorise pas les messages privés
    if ($currentUserId === $postAuthorId || !$authorAllowsPrivateMessages || $postAuthorId <= 0) {
        return '';
    }
    
    $router = \Core\Router\Router::getInstance();
    try {
        $messagingComposeBaseUrl = $router->generate('messaging-compose');
    } catch (\Exception $e) {
        $messagingComposeBaseUrl = '/messaging/compose';
    }
    
    return '<a href="' . htmlspecialchars($messagingComposeBaseUrl) . '?to=' . $postAuthorId . '" class="button secondary small">' .
           '<i class="fa-solid fa-envelope"></i> Message privé</a>';
}

/**
 * Hook pour injecter la section messagerie dans le profil utilisateur
 */
function messagingUserProfileSections(): string
{
    $plugin = \Core\Plugin\PluginsManager::getInstance()->getPlugin('messaging');
    if (!$plugin || !$plugin->isInstalled() || !$plugin->getConfigVal('activate')) {
        return '';
    }
    
    $router = \Core\Router\Router::getInstance();
    try {
        $messagingLink = $router->generate('messaging-inbox');
    } catch (\Exception $e) {
        $messagingLink = '/messaging/inbox';
    }
    
    return '<div class="messaging-read__card">' .
           '<h3 class="messaging-read__title">' .
           '<i class="fa-solid fa-envelope"></i> Messagerie interne</h3>' .
           '<p class="messaging-read__text">' .
           'Accédez à votre messagerie pour échanger avec les autres membres du site.</p>' .
           '<a href="' . htmlspecialchars($messagingLink) . '" class="button success">' .
           '<i class="fa-solid fa-inbox"></i> Ouvrir ma messagerie</a>' .
           '</div>';
}
/**
 * Hook: user:profile-settings
 */
function messagingUserProfileSettings($user): string
{
    $allowMessaging = $user->attributes['allow_messaging'] ?? true;
    
    return '
    <div class="setting-item">
        <label class="toggle-switch">
            <input type="checkbox" class="toggle-input" data-setting="allow_messaging" ' . ($allowMessaging ? 'checked' : '') . '>
            <span class="toggle-slider"></span>
        </label>
        <div class="setting-info">
            <span class="setting-label">Autoriser les messages privés</span>
            <span class="setting-description">Autorise les autres membres à vous envoyer des messages privés.</span>
        </div>
    </div>';
}

/**
 * Hook: user:profile-edit-settings (placeholder for now/edit view specificities)
 */
function messagingUserProfileEditSettings($user): string
{
    return ''; // On utilise le toggle AJAX sur le profil directement, pas besoin de champ additionnel dans l'edit pour l'instant
}

/**
 * Hook: user:profile-allowed-settings
 */
function messagingUserProfileAllowedSettings(array $allowed): array
{
    $allowed[] = 'allow_messaging';
    $allowed[] = 'notif_messaging';
    return $allowed;
}

/**
 * Hook: user:avatar-menu
 */
function messagingUserAvatarMenu(array $data): array
{
    $items = $data['items'] ?? [];
    $user = \Core\Auth\UsersManager::getCurrentUser();
    $router = \Core\Router\Router::getInstance();

    if ($user && ($user->attributes['allow_messaging'] ?? true)) {
        try {
            $url = $router->generate('messaging-inbox');
        } catch (\Exception $e) {
            $url = '/messaging/inbox';
        }
        
        $items[] = [
            'label' => 'Messagerie',
            'url' => $url,
            'icon' => 'fa-solid fa-envelope',
            'order' => 25
        ];
    }

    $data['items'] = $items;
    return $data;
}

/**
 * Listener: messaging:message-sent
 * Pont entre le domaine (Messagerie) et l'action (Notifications)
 */
function messagingOnMessageSent(array $data): void
{
    $messageId = $data['message_id'] ?? null;
    $fromUserId = $data['from_user_id'] ?? null;
    $toUserId = $data['to_user_id'] ?? null;
    $subject = $data['subject'] ?? 'Sans sujet';

    if (!$messageId || !$toUserId) {
        return;
    }

    $fromUser = \Core\Auth\User::findPK($fromUserId);
    $actorName = $fromUser ? ($fromUser->attributes['username'] ?? 'Utilisateur') : 'Utilisateur';
    
    $router = \Core\Router\Router::getInstance();
    $linkUrl = null;
    try {
        $linkUrl = $router->generate('messaging-read', ['id' => $messageId]);
    } catch (\Exception $e) {
        $linkUrl = '/messaging/read/' . $messageId;
    }

    \Core\Core::getInstance()->callHook('notifications:send', [
        'user_id' => $toUserId,
        'type' => 'messaging.message',
        'source_plugin' => 'messaging',
        'data' => [
            'key' => 'messaging.message',
            'params' => [
                'actor' => $actorName,
                'subject' => $subject
            ],
            'url' => $linkUrl,
            'actor' => [
                'id' => $fromUserId,
                'name' => $actorName,
                'avatar' => $fromUser->attributes['avatar'] ?? null
            ],
            'entity' => [
                'type' => 'message',
                'id' => $messageId
            ]
        ],
        'external_id' => 'messaging:message:' . $messageId
    ]);
}
