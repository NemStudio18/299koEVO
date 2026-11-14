<?php

namespace Messaging\Controllers;

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Core\Security\Csrf;
use Utils\Show;
use Core\Lang;
use Messaging\Services\MessagingService;
use Core\Auth\User;

defined('ROOT') or exit('No direct script access allowed');

class MessagingPublicController extends PublicController
{
    private MessagingService $messagingService;
    
    public function __construct()
    {
        parent::__construct();
        require_once PLUGINS . 'messaging/services/MessagingService.php';
        $this->messagingService = new MessagingService();
    }
    
    public function inbox()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('messaging:access'), $ctx)) {
            Show::msg('Vous devez être connecté pour accéder à la messagerie.', 'error');
            $this->core->redirect($this->router->generate('login'));
        }
        
        $userId = (int)$this->user->attributes['id'];
        $inboxMessages = $this->messagingService->getInboxMessages($userId);
        $sentMessages = $this->messagingService->getSentMessages($userId);
        $unreadCount = $this->messagingService->getUnreadCount($userId);
        
        // Enrichir les messages avec les informations des utilisateurs et pré-générer les URLs
        $inboxEnriched = [];
        foreach ($inboxMessages as $message) {
            $fromUser = $this->messagingService->getUserById($message['from_user_id']);
            try {
                $readUrl = $this->router->generate('messaging-read', ['id' => $message['id']]);
            } catch (\Exception $e) {
                $readUrl = '/messaging/read/' . $message['id'];
            }
            $inboxEnriched[] = [
                'id' => $message['id'],
                'from_user' => $fromUser ? [
                    'id' => $fromUser->attributes['id'],
                    'username' => $fromUser->username(),
                    'email' => $fromUser->email ?? ''
                ] : null,
                'subject' => $message['subject'],
                'content' => $message['content'],
                'created_at' => $message['created_at'],
                'created_at_formatted' => date('d/m/Y H:i', $message['created_at']),
                'is_read' => $message['is_read'] ?? false,
                'read_url' => $readUrl
            ];
        }
        
        $sentEnriched = [];
        foreach ($sentMessages as $message) {
            $toUser = $this->messagingService->getUserById($message['to_user_id']);
            try {
                $readUrl = $this->router->generate('messaging-read', ['id' => $message['id']]);
            } catch (\Exception $e) {
                $readUrl = '/messaging/read/' . $message['id'];
            }
            $sentEnriched[] = [
                'id' => $message['id'],
                'to_user' => $toUser ? [
                    'id' => $toUser->attributes['id'],
                    'username' => $toUser->username(),
                    'email' => $toUser->email ?? ''
                ] : null,
                'subject' => $message['subject'],
                'content' => $message['content'],
                'created_at' => $message['created_at'],
                'created_at_formatted' => date('d/m/Y H:i', $message['created_at']),
                'is_read' => $message['is_read'] ?? false,
                'read_url' => $readUrl
            ];
        }
        
        $response = new PublicResponse();
        $response->setTitle('Messagerie');
        $tpl = $response->createPluginTemplate('messaging', 'inbox');
        $tpl->set('inboxMessages', $inboxEnriched);
        $tpl->set('sentMessages', $sentEnriched);
        $tpl->set('unreadCount', $unreadCount);
        $tpl->set('composeLink', $this->router->generate('messaging-compose'));
        $tpl->set('ROUTER', $this->router);
        $tpl->set('_csrfToken', Csrf::token());
        
        $response->addTemplate($tpl);
        return $response;
    }
    
    public function compose()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('messaging:access'), $ctx)) {
            Show::msg('Vous devez être connecté pour envoyer un message.', 'error');
            $this->core->redirect($this->router->generate('login'));
        }
        
        $toUserId = isset($_GET['to']) ? (int)$_GET['to'] : 0;
        $toUser = null;
        if ($toUserId > 0) {
            $toUser = $this->messagingService->getUserById($toUserId);
        }
        
        // Récupérer tous les utilisateurs pour la liste déroulante
        $allUsers = User::all();
        $usersList = [];
        $currentUserId = (int)$this->user->attributes['id'];
        foreach ($allUsers as $user) {
            if ((int)$user->attributes['id'] !== $currentUserId) {
                $usersList[] = [
                    'id' => $user->attributes['id'],
                    'username' => $user->username(),
                    'email' => $user->email ?? ''
                ];
            }
        }
        
        $response = new PublicResponse();
        $response->setTitle('Nouveau message');
        $tpl = $response->createPluginTemplate('messaging', 'compose');
        $tpl->set('toUser', $toUser ? [
            'id' => $toUser->attributes['id'],
            'username' => $toUser->username(),
            'email' => $toUser->email ?? ''
        ] : null);
        $tpl->set('usersList', $usersList);
        $tpl->set('sendLink', $this->router->generate('messaging-send'));
        $tpl->set('inboxLink', $this->router->generate('messaging-inbox'));
        $tpl->set('_csrfToken', Csrf::token());
        
        $response->addTemplate($tpl);
        return $response;
    }
    
    public function send()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('messaging:access'), $ctx)) {
            Show::msg('Vous devez être connecté pour envoyer un message.', 'error');
            $this->core->redirect($this->router->generate('login'));
        }
        
        if (!Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg('Erreur de sécurité.', 'error');
            $this->core->redirect($this->router->generate('messaging-compose'));
        }
        
        $toUserId = (int)($_POST['to_user_id'] ?? 0);
        $subject = trim($_POST['subject'] ?? '');
        $content = trim($_POST['content'] ?? '');
        
        if ($toUserId <= 0) {
            Show::msg('Destinataire invalide.', 'error');
            $this->core->redirect($this->router->generate('messaging-compose'));
        }
        
        if (empty($subject)) {
            Show::msg('Le sujet est obligatoire.', 'error');
            $this->core->redirect($this->router->generate('messaging-compose'));
        }
        
        if (empty($content)) {
            Show::msg('Le contenu est obligatoire.', 'error');
            $this->core->redirect($this->router->generate('messaging-compose'));
        }
        
        $config = json_decode(file_get_contents(PLUGINS . 'messaging/param/config.json'), true);
        $maxLength = $config['maxMessageLength'] ?? 5000;
        
        if (strlen($content) > $maxLength) {
            Show::msg('Le message est trop long. Longueur maximale : ' . $maxLength . ' caractères.', 'error');
            $this->core->redirect($this->router->generate('messaging-compose'));
        }
        
        $fromUserId = (int)$this->user->attributes['id'];
        
        if ($fromUserId === $toUserId) {
            Show::msg('Vous ne pouvez pas vous envoyer un message à vous-même.', 'error');
            $this->core->redirect($this->router->generate('messaging-compose'));
        }
        
        $messageId = $this->messagingService->sendMessage($fromUserId, $toUserId, $subject, $content);
        
        if ($messageId !== null) {
            Show::msg('Message envoyé avec succès.', 'success');
            $this->core->redirect($this->router->generate('messaging-inbox'));
        } else {
            Show::msg('Erreur lors de l\'envoi du message.', 'error');
            $this->core->redirect($this->router->generate('messaging-compose'));
        }
    }
    
    public function read(int $id)
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('messaging:access'), $ctx)) {
            Show::msg('Vous devez être connecté pour lire un message.', 'error');
            $this->core->redirect($this->router->generate('login'));
        }
        
        $userId = (int)$this->user->attributes['id'];
        $message = $this->messagingService->getMessage($id, $userId);
        
        if ($message === null) {
            Show::msg('Message introuvable.', 'error');
            $this->core->redirect($this->router->generate('messaging-inbox'));
        }
        
        // Marquer comme lu si c'est un message reçu
        if ($message['to_user_id'] == $userId && !($message['is_read'] ?? false)) {
            $this->messagingService->markAsRead($id, $userId);
        }
        
        // Enrichir avec les informations des utilisateurs
        $fromUser = $this->messagingService->getUserById($message['from_user_id']);
        $toUser = $this->messagingService->getUserById($message['to_user_id']);
        
        $messageEnriched = [
            'id' => $message['id'],
            'from_user' => $fromUser ? [
                'id' => $fromUser->attributes['id'],
                'username' => $fromUser->username(),
                'email' => $fromUser->email ?? ''
            ] : null,
            'to_user' => $toUser ? [
                'id' => $toUser->attributes['id'],
                'username' => $toUser->username(),
                'email' => $toUser->email ?? ''
            ] : null,
            'subject' => $message['subject'],
            'content' => $message['content'],
            'created_at' => $message['created_at'],
            'created_at_formatted' => date('d/m/Y à H:i', $message['created_at']),
            'is_read' => $message['is_read'] ?? false,
            'is_sent' => ($message['from_user_id'] == $userId)
        ];
        
        $response = new PublicResponse();
        $response->setTitle($message['subject']);
        $tpl = $response->createPluginTemplate('messaging', 'read');
        $tpl->set('message', $messageEnriched);
        $tpl->set('inboxLink', $this->router->generate('messaging-inbox'));
        $tpl->set('composeLink', $this->router->generate('messaging-compose'));
        $tpl->set('deleteLink', $this->router->generate('messaging-delete', ['id' => $id]));
        $tpl->set('_csrfToken', Csrf::token());
        
        $response->addTemplate($tpl);
        return $response;
    }
    
    public function delete(int $id)
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('messaging:access'), $ctx)) {
            Show::msg('Vous devez être connecté.', 'error');
            $this->core->redirect($this->router->generate('login'));
            return;
        }
        
        if (!Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg('Erreur de sécurité.', 'error');
            $this->core->redirect($this->router->generate('messaging-inbox'));
        }
        
        $userId = (int)$this->user->attributes['id'];
        
        if ($this->messagingService->deleteMessage($id, $userId)) {
            Show::msg('Message supprimé avec succès.', 'success');
        } else {
            Show::msg('Erreur lors de la suppression du message.', 'error');
        }
        
        $this->core->redirect($this->router->generate('messaging-inbox'));
    }
    
    public function markRead(int $id)
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('messaging:access'), $ctx)) {
            $this->core->redirect($this->router->generate('login'));
        }
        
        if (!Csrf::validate($_POST['_csrf'] ?? null, false)) {
            $this->core->redirect($this->router->generate('messaging-inbox'));
        }
        
        $userId = (int)$this->user->attributes['id'];
        $this->messagingService->markAsRead($id, $userId);
        
        $this->core->redirect($this->router->generate('messaging-inbox'));
    }
}

