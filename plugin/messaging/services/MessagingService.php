<?php

namespace Messaging\Services;

use Core\Core;
use Core\Auth\User;

defined('ROOT') or exit('No direct script access allowed');

class MessagingService
{
    private string $messagesFile;
    private Core $core;
    
    public function __construct()
    {
        $this->core = Core::getInstance();
        $this->messagesFile = DATA_PLUGIN . 'messaging' . DS . 'messages.json';
        $this->ensureMessagesFile();
    }
    
    private function ensureMessagesFile(): void
    {
        $dir = dirname($this->messagesFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (!file_exists($this->messagesFile)) {
            file_put_contents($this->messagesFile, json_encode([], JSON_PRETTY_PRINT));
        }
    }
    
    private function loadMessages(): array
    {
        $testMode = \Core\Auth\AdminTestMode::getInstance();
        if ($testMode->isActive()) {
            return $testMode->getVolatileRows('messaging_messages');
        }

        if (!file_exists($this->messagesFile)) {
            return [];
        }
        $content = file_get_contents($this->messagesFile);
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }
    
    private function saveMessages(array $messages): bool
    {
        $testMode = \Core\Auth\AdminTestMode::getInstance();
        if ($testMode->isActive()) {
            // Dans le stockage volatil, on remplace tout par le nouveau tableau
            $_SESSION['volatile_sql_tables']['messaging_messages'] = $messages;
            return true;
        }

        $json = json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($this->messagesFile, $json, LOCK_EX) !== false;
    }
    
    public function sendMessage(int $fromUserId, int $toUserId, string $subject, string $content): ?int
    {
        $messages = $this->loadMessages();
        
        $messageId = 1;
        if (!empty($messages)) {
            $maxId = max(array_column($messages, 'id'));
            $messageId = $maxId + 1;
        }
        
        $message = [
            'id' => $messageId,
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'subject' => $subject,
            'content' => $content,
            'created_at' => time(),
            'is_read' => false,
            'is_deleted_by_sender' => false,
            'is_deleted_by_recipient' => false
        ];
        
        $messages[] = $message;
        
        if ($this->saveMessages($messages)) {
            // Trigger domain hook for decoupling
            Core::getInstance()->callHook('messaging:message-sent', [
                'message_id' => $messageId,
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'subject' => $subject,
                'content' => $content
            ]);

            return $messageId;
        }
        
        return null;
    }
    
    public function getInboxMessages(int $userId): array
    {
        $messages = $this->loadMessages();
        $inbox = [];
        
        foreach ($messages as $message) {
            if ($message['to_user_id'] == $userId && !($message['is_deleted_by_recipient'] ?? false)) {
                $inbox[] = $message;
            }
        }
        
        // Trier par date décroissante
        usort($inbox, function($a, $b) {
            return ($b['created_at'] ?? 0) - ($a['created_at'] ?? 0);
        });
        
        return $inbox;
    }
    
    public function getSentMessages(int $userId): array
    {
        $messages = $this->loadMessages();
        $sent = [];
        
        foreach ($messages as $message) {
            if ($message['from_user_id'] == $userId && !($message['is_deleted_by_sender'] ?? false)) {
                $sent[] = $message;
            }
        }
        
        // Trier par date décroissante
        usort($sent, function($a, $b) {
            return ($b['created_at'] ?? 0) - ($a['created_at'] ?? 0);
        });
        
        return $sent;
    }
    
    public function getMessage(int $messageId, int $userId): ?array
    {
        $messages = $this->loadMessages();
        
        foreach ($messages as $message) {
            if ($message['id'] == $messageId) {
                // Vérifier que l'utilisateur est soit l'expéditeur soit le destinataire
                if ($message['from_user_id'] == $userId || $message['to_user_id'] == $userId) {
                    return $message;
                }
            }
        }
        
        return null;
    }
    
    public function markAsRead(int $messageId, int $userId): bool
    {
        $messages = $this->loadMessages();
        
        $testMode = \Core\Auth\AdminTestMode::getInstance();
        foreach ($messages as &$message) {
            if ($message['id'] == $messageId && $message['to_user_id'] == $userId) {
                $message['is_read'] = true;
                
                // Sync with notifications
                Core::getInstance()->callHook('notifications:mark-as-read-external', [
                    'user_id' => $userId,
                    'external_id' => 'msg_' . $messageId
                ]);

                if ($testMode->isActive()) {
                    $_SESSION['volatile_sql_tables']['messaging_messages'] = $messages;
                    return true;
                }
                return $this->saveMessages($messages);
            }
        }
        
        return false;
    }
    
    public function deleteMessage(int $messageId, int $userId): bool
    {
        $messages = $this->loadMessages();
        
        $testMode = \Core\Auth\AdminTestMode::getInstance();
        foreach ($messages as &$message) {
            if ($message['id'] == $messageId) {
                if ($message['from_user_id'] == $userId) {
                    $message['is_deleted_by_sender'] = true;
                }
                if ($message['to_user_id'] == $userId) {
                    $message['is_deleted_by_recipient'] = true;
                }
                if ($testMode->isActive()) {
                    $_SESSION['volatile_sql_tables']['messaging_messages'] = $messages;
                    return true;
                }
                return $this->saveMessages($messages);
            }
        }
        
        return false;
    }
    
    public function getUnreadCount(int $userId): int
    {
        $messages = $this->getInboxMessages($userId);
        $count = 0;
        
        foreach ($messages as $message) {
            if (!($message['is_read'] ?? false)) {
                $count++;
            }
        }
        
        return $count;
    }
    
    public function getUserById(int $userId): ?User
    {
        return User::findPK($userId);
    }
}



