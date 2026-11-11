<?php

namespace Notifications\Controllers;

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Core\Auth\UsersManager;
use Utils\Show;
use Core\Security\Csrf;

defined('ROOT') or exit('Access denied!');

class NotificationsPublicController extends PublicController
{
    /**
     * Liste les notifications de l'utilisateur
     */
    public function list()
    {
        // TODO: Vérifier que l'utilisateur est connecté
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('notifications:access'), $ctx)) {
            Show::msg('Vous devez être connecté.', 'error');
            return $this->core->redirect('/');
        }

        $user = UsersManager::getCurrentUser();
        $userId = $user->attributes['id'] ?? 0;

        // TODO: Charger NotificationManager
        require_once PLUGINS . 'notifications/entities/NotificationManager.php';
        $manager = new \Notifications\Entities\NotificationManager();

        $notifications = $manager->getUserNotifications($userId);
        $unreadCount = $manager->getUnreadCount($userId);

        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('notifications', 'list');

        $tpl->set('notifications', $notifications);
        $tpl->set('unreadCount', $unreadCount);
        $tpl->set('csrfToken', Csrf::token());

        $response->setTitle('Mes Notifications');
        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Marque une notification comme lue
     */
    /**
     * Marque une notification comme lue
     */
    public function markRead($id = null)
    {
        $user = UsersManager::getCurrentUser();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('notifications:access'), $ctx)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Not authorized']);
            exit;
        }

        $notificationId = trim((string) ($id ?? $_POST['id'] ?? ''));
        $token = $_POST['token'] ?? $_POST['_csrf'] ?? null;

        if (!Csrf::validate($token, false)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            exit;
        }

        require_once PLUGINS . 'notifications/entities/NotificationManager.php';
        $manager = new \Notifications\Entities\NotificationManager();

        $success = $manager->markAsRead($notificationId, $user->attributes['id']);

        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
        exit;
    }

    /**
     * Marque toutes les notifications comme lues
     */
    public function markAllAsRead()
    {
        $user = UsersManager::getCurrentUser();
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('notifications:access'), $ctx)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Not authorized']);
            exit;
        }

        $token = $_POST['token'] ?? $_POST['_csrf'] ?? null;

        // Si appelée via formulaire POST standard (pas AJAX, redirection)
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        if (!Csrf::validate($token, false)) {
            if ($isAjax) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
                exit;
            } else {
                Show::msg('Token invalide.', 'error');
                $this->core->redirect('/notifications');
                return;
            }
        }

        require_once PLUGINS . 'notifications/entities/NotificationManager.php';
        $manager = new \Notifications\Entities\NotificationManager();

        $manager->markAllAsRead($user->attributes['id']);

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        } else {
            Show::msg('Toutes les notifications ont été marquées comme lues.', 'success');
            $this->core->redirect('/notifications');
        }
    }
}
