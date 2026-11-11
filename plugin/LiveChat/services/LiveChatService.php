<?php

namespace LiveChat\Services;

defined('ROOT') or exit('Access denied!');

class LiveChatService
{
    private \PDO $pdo;
    private array $config;

    public function __construct()
    {
        $this->pdo = \Core\Database::getInstance()->getPdo();
        $this->loadConfig();
    }

    private function loadConfig(): void
    {
        $this->config = [
            'discord_webhook' => '',
            'admin_name' => 'Équipe Vivre-Bourges',
            'welcome_message' => 'Bonjour ! Une question sur votre établissement ou sur le site ? Discutons en direct.',
            'auto_notify_discord' => true
        ];
        
        $configFile = DATA_PLUGIN . 'LiveChat/config.json';
        if (file_exists($configFile)) {
            $parsed = json_decode(file_get_contents($configFile), true);
            if (is_array($parsed)) {
                $this->config = array_merge($this->config, $parsed);
            }
        }
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Récupère ou initialise une conversation à partir du token de session du visiteur
     */
    public function getOrCreateConversation(string $sessionToken, ?string $name = null, ?string $contact = null): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM vb_livechat_conversations WHERE session_token = ? LIMIT 1');
        $stmt->execute([$sessionToken]);
        $conv = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($conv) {
            // Mettre à jour nom/contact si fournis
            $updates = [];
            $params = [];
            if (!empty($name) && $conv['visitor_name'] !== $name) {
                $updates[] = 'visitor_name = ?';
                $params[] = $name;
                $conv['visitor_name'] = $name;
            }
            if (!empty($contact) && $conv['visitor_contact'] !== $contact) {
                $updates[] = 'visitor_contact = ?';
                $params[] = $contact;
                $conv['visitor_contact'] = $contact;
            }
            if (!empty($updates)) {
                $params[] = $conv['id'];
                $this->pdo->prepare('UPDATE vb_livechat_conversations SET ' . implode(', ', $updates) . ' WHERE id = ?')->execute($params);
            }
            return $conv;
        }

        $visitorName = !empty($name) ? $name : 'Visiteur #' . substr(md5($sessionToken), 0, 4);
        $insert = $this->pdo->prepare('
            INSERT INTO vb_livechat_conversations (session_token, visitor_name, visitor_contact, status, created_at, updated_at)
            VALUES (?, ?, ?, \'open\', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ');
        $insert->execute([$sessionToken, $visitorName, $contact]);
        $id = (int)$this->pdo->lastInsertId();

        // Insérer le message de bienvenue automatique de l'admin
        $welcomeMsg = $this->config['welcome_message'] ?? 'Bonjour ! Comment pouvons-nous vous aider ?';
        $this->addMessage($id, 'admin', $welcomeMsg, $this->config['admin_name']);

        return $this->getConversationById($id);
    }

    public function getConversationById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM vb_livechat_conversations WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public function getConversationByToken(string $sessionToken): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM vb_livechat_conversations WHERE session_token = ? LIMIT 1');
        $stmt->execute([$sessionToken]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Enregistre un message dans une conversation
     */
    public function addMessage(int $conversationId, string $sender, string $message, ?string $senderName = null): array
    {
        $message = trim($message);
        if ($message === '') {
            throw new \InvalidArgumentException('Message vide.');
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO vb_livechat_messages (conversation_id, sender, sender_name, message, created_at, is_read)
            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, 0)
        ');
        $stmt->execute([$conversationId, $sender, $senderName, $message]);
        $msgId = (int)$this->pdo->lastInsertId();

        // Mettre à jour la conversation
        $unreadField = ($sender === 'visitor') ? 'unread_admin = unread_admin + 1' : 'unread_visitor = unread_visitor + 1';
        $updateConv = $this->pdo->prepare("
            UPDATE vb_livechat_conversations 
            SET last_message = ?, updated_at = CURRENT_TIMESTAMP, status = 'open', {$unreadField}
            WHERE id = ?
        ");
        $updateConv->execute([mb_substr($message, 0, 150), $conversationId]);

        // Si le visiteur écrit, notifier Discord
        if ($sender === 'visitor') {
            $conv = $this->getConversationById($conversationId);
            $visitorName = $conv['visitor_name'] ?? 'Visiteur';
            $contact = $conv['visitor_contact'] ?? '';
            $this->sendDiscordNotification($visitorName, $contact, $message, $conversationId);
        }

        return [
            'id' => $msgId,
            'conversation_id' => $conversationId,
            'sender' => $sender,
            'sender_name' => $senderName,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s'),
            'is_read' => 0
        ];
    }

    /**
     * Récupère les messages d'une conversation (avec option after_id pour polling fluide)
     */
    public function getMessages(int $conversationId, int $afterId = 0): array
    {
        if ($afterId > 0) {
            $stmt = $this->pdo->prepare('
                SELECT * FROM vb_livechat_messages 
                WHERE conversation_id = ? AND id > ? 
                ORDER BY id ASC
            ');
            $stmt->execute([$conversationId, $afterId]);
        } else {
            $stmt = $this->pdo->prepare('
                SELECT * FROM vb_livechat_messages 
                WHERE conversation_id = ? 
                ORDER BY id ASC
            ');
            $stmt->execute([$conversationId]);
        }
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Marque les messages comme lus
     */
    public function markAsRead(int $conversationId, string $readerRole): void
    {
        if ($readerRole === 'admin') {
            $this->pdo->prepare('UPDATE vb_livechat_messages SET is_read = 1 WHERE conversation_id = ? AND sender = "visitor"')->execute([$conversationId]);
            $this->pdo->prepare('UPDATE vb_livechat_conversations SET unread_admin = 0 WHERE id = ?')->execute([$conversationId]);
        } else {
            $this->pdo->prepare('UPDATE vb_livechat_messages SET is_read = 1 WHERE conversation_id = ? AND sender = "admin"')->execute([$conversationId]);
            $this->pdo->prepare('UPDATE vb_livechat_conversations SET unread_visitor = 0 WHERE id = ?')->execute([$conversationId]);
        }
    }

    /**
     * Construit la clause WHERE et les paramètres associés pour filtrer les conversations.
     *
     * @return array{0: string, 1: array}
     */
    private function buildConversationFilter(string $status = 'all', string $search = ''): array
    {
        $conditions = [];
        $params = [];

        if ($status === 'open') {
            $conditions[] = "c.status = 'open'";
        } elseif ($status === 'closed') {
            $conditions[] = "c.status = 'closed'";
        } elseif ($status === 'unread') {
            $conditions[] = 'c.unread_admin > 0';
        }

        $search = trim($search);
        if ($search !== '') {
            $like = '%' . mb_strtolower($search) . '%';
            $conditions[] = "(LOWER(COALESCE(c.visitor_name, '')) LIKE ? OR LOWER(COALESCE(c.visitor_contact, '')) LIKE ? OR LOWER(COALESCE(c.last_message, '')) LIKE ?)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

        return [$where, $params];
    }

    /**
     * Liste les conversations pour l'administration avec filtrage, recherche et pagination.
     */
    public function getAllConversations(int $limit = 50, string $status = 'all', string $search = '', int $offset = 0): array
    {
        $limit = max(1, min(1000, $limit));
        $offset = max(0, $offset);
        [$where, $params] = $this->buildConversationFilter($status, $search);

        $stmt = $this->pdo->prepare("
            SELECT c.*, 
                   (SELECT COUNT(*) FROM vb_livechat_messages m WHERE m.conversation_id = c.id) as total_messages
            FROM vb_livechat_conversations c
            {$where}
            ORDER BY c.unread_admin DESC, c.updated_at DESC, c.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Compte le nombre de conversations correspondant au filtre.
     */
    public function countConversations(string $status = 'all', string $search = ''): int
    {
        [$where, $params] = $this->buildConversationFilter($status, $search);
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM vb_livechat_conversations c {$where}");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Retourne les compteurs globaux de conversations par statut.
     */
    public function getConversationStats(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                COUNT(*) AS all_count,
                COALESCE(SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END), 0) AS open_count,
                COALESCE(SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END), 0) AS closed_count,
                COALESCE(SUM(CASE WHEN unread_admin > 0 THEN 1 ELSE 0 END), 0) AS unread_count
            FROM vb_livechat_conversations
        ");
        $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];

        return [
            'all' => (int)($row['all_count'] ?? 0),
            'open' => (int)($row['open_count'] ?? 0),
            'closed' => (int)($row['closed_count'] ?? 0),
            'unread' => (int)($row['unread_count'] ?? 0),
        ];
    }

    /**
     * Ferme ou rouvre une conversation
     */
    public function toggleStatus(int $conversationId): bool
    {
        $conv = $this->getConversationById($conversationId);
        if (!$conv) return false;

        $newStatus = ($conv['status'] === 'open') ? 'closed' : 'open';
        $stmt = $this->pdo->prepare('UPDATE vb_livechat_conversations SET status = ? WHERE id = ?');
        return $stmt->execute([$newStatus, $conversationId]);
    }

    /**
     * Envoie une notification instantanée vers Discord
     */
    public function sendDiscordNotification(string $visitorName, string $contact, string $message, int $conversationId): bool
    {
        $webhookUrl = trim($this->config['discord_webhook'] ?? '');
        if (empty($webhookUrl)) {
            // Vérifier aussi dans config_pro_chat.json si présent
            $proConfig = ROOT . 'data/config_pro_chat.json';
            if (file_exists($proConfig)) {
                $p = json_decode(file_get_contents($proConfig), true);
                $webhookUrl = trim($p['discord_webhook'] ?? '');
            }
        }

        if (empty($webhookUrl) || !filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
            return false;
        }

        $replyUrl = "https://vivre-bourges.fr/admin/livechat?conv={$conversationId}";

        $embed = [
            'title' => '💬 Nouveau message LiveChat reçu !',
            'color' => 15368460, // Orange foncé #ea580c
            'description' => "Un internaute discute en direct sur **Vivre-Bourges.fr** :",
            'fields' => [
                [
                    'name' => '👤 Interlocuteur',
                    'value' => substr($visitorName, 0, 200),
                    'inline' => true,
                ],
                [
                    'name' => '📞 Contact renseigné',
                    'value' => !empty($contact) ? substr($contact, 0, 200) : '_Non renseigné_',
                    'inline' => true,
                ],
                [
                    'name' => '✉️ Message',
                    'value' => substr($message, 0, 1000),
                    'inline' => false,
                ],
                [
                    'name' => '⚡ Répondre en direct',
                    'value' => "[**👉 Cliquer ici pour répondre depuis votre smartphone**]({$replyUrl})",
                    'inline' => false,
                ]
            ],
            'footer' => [
                'text' => 'Vivre-Bourges.fr LiveChat direct',
            ],
            'timestamp' => date('c'),
        ];

        $payload = json_encode([
            'username' => 'LiveChat Vivre-Bourges',
            'avatar_url' => 'https://vivre-bourges.fr/favicon.ico',
            'embeds' => [$embed],
        ]);

        $ch = curl_init($webhookUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return ($httpCode >= 200 && $httpCode < 300);
    }
}
