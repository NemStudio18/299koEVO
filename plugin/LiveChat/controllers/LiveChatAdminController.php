<?php

namespace LiveChat\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use LiveChat\Services\LiveChatService;

defined('ROOT') or exit('Access denied!');

class LiveChatAdminController extends AdminController
{
    private LiveChatService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new LiveChatService();
    }

    private function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo json_encode($data);
        exit;
    }

    /**
     * Normalise les paramètres de filtre de conversations.
     */
    private function normalizeStatusFilter(string $status): string
    {
        return in_array($status, ['all', 'open', 'closed', 'unread'], true) ? $status : 'all';
    }

    /**
     * Tableau de bord d'administration du LiveChat (responsive desktop & smartphone)
     */
    public function index(): AdminResponse
    {
        $response = new AdminResponse();
        $response->setTitle('<i class="fa-solid fa-comments"></i> LiveChat Direct');

        $activeConvId = (int)($_GET['conv'] ?? 0);
        $mobileList = !empty($_GET['list']);
        $status = $this->normalizeStatusFilter((string)($_GET['status'] ?? 'all'));
        $search = trim((string)($_GET['q'] ?? ''));
        $limit = 50;

        $conversations = $this->service->getAllConversations($limit, $status, $search, 0);
        $totalMatching = $this->service->countConversations($status, $search);
        $stats = $this->service->getConversationStats();

        $activeConversation = null;
        $activeMessages = [];

        if ($activeConvId > 0) {
            $activeConversation = $this->service->getConversationById($activeConvId);
            if ($activeConversation) {
                $activeMessages = $this->service->getMessages($activeConvId, 0);
                $this->service->markAsRead($activeConvId, 'admin');
            }
        } elseif (!$mobileList && !empty($conversations)) {
            $activeConversation = $conversations[0];
            $activeConvId = (int)$activeConversation['id'];
            $activeMessages = $this->service->getMessages($activeConvId, 0);
            $this->service->markAsRead($activeConvId, 'admin');
        }

        $tpl = $response->createPluginTemplate('LiveChat', 'admin');
        $tpl->set('conversations', $conversations);
        $tpl->set('activeConversation', $activeConversation);
        $tpl->set('activeMessages', $activeMessages);
        $tpl->set('activeConvId', $activeConvId);
        $tpl->set('filterStatus', $status);
        $tpl->set('searchQuery', $search);
        $tpl->set('searchQueryEncoded', urlencode($search));
        $tpl->set('stats', $stats);
        $tpl->set('totalMatching', $totalMatching);
        $tpl->set('loadedCount', count($conversations));
        $tpl->set('hasMore', count($conversations) < $totalMatching);
        $tpl->set('pageLimit', $limit);
        $tpl->set('mobileList', $mobileList);
        $tpl->set('config', $this->service->getConfig());
        $response->addTemplate($tpl);

        return $response;
    }

    /**
     * Liste paginée / filtrée des conversations en JSON (recherche, filtres, Charger plus)
     */
    public function listConversations(): void
    {
        $status = $this->normalizeStatusFilter((string)($_GET['status'] ?? 'all'));
        $search = trim((string)($_GET['q'] ?? ''));
        $limit = max(1, min(1000, (int)($_GET['limit'] ?? 50)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $conversations = $this->service->getAllConversations($limit, $status, $search, $offset);
        $totalMatching = $this->service->countConversations($status, $search);
        $stats = $this->service->getConversationStats();

        $this->jsonResponse([
            'success' => true,
            'conversations' => $conversations,
            'total' => $totalMatching,
            'offset' => $offset,
            'has_more' => ($offset + count($conversations)) < $totalMatching,
            'stats' => $stats,
        ]);
    }

    /**
     * Répondre à une conversation depuis l'espace admin
     */
    public function adminReply(): void
    {
        $convId = (int)($_POST['conversation_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $config = $this->service->getConfig();
        $adminName = !empty($_POST['admin_name']) ? trim($_POST['admin_name']) : ($config['admin_name'] ?? 'Équipe Vivre-Bourges');

        if ($convId <= 0 || empty($message)) {
            $this->jsonResponse(['success' => false, 'error' => 'Paramètres invalides'], 400);
        }

        try {
            $msg = $this->service->addMessage($convId, 'admin', $message, $adminName);
            $this->jsonResponse(['success' => true, 'message' => $msg]);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    /**
     * Polling admin pour recevoir les nouveaux messages du visiteur en direct
     */
    public function adminPoll(): void
    {
        $convId = (int)($_GET['conversation_id'] ?? 0);
        $afterId = max(0, (int)($_GET['after_id'] ?? 0));
        $status = $this->normalizeStatusFilter((string)($_GET['status'] ?? 'all'));
        $search = trim((string)($_GET['q'] ?? ''));
        $limit = max(1, min(1000, (int)($_GET['limit'] ?? 50)));

        $messages = [];
        if ($convId > 0) {
            $messages = $this->service->getMessages($convId, $afterId);
            if (!empty($messages)) {
                $this->service->markAsRead($convId, 'admin');
            }
        }

        $conversations = $this->service->getAllConversations($limit, $status, $search, 0);
        $totalMatching = $this->service->countConversations($status, $search);
        $stats = $this->service->getConversationStats();

        $this->jsonResponse([
            'success' => true,
            'messages' => $messages,
            'conversations' => $conversations,
            'total' => $totalMatching,
            'has_more' => count($conversations) < $totalMatching,
            'stats' => $stats,
        ]);
    }

    /**
     * Basculer l'état ouvert/fermé d'une conversation
     */
    public function toggleStatus(): void
    {
        $convId = (int)($_POST['conversation_id'] ?? 0);
        if ($convId <= 0) {
            $this->jsonResponse(['success' => false, 'error' => 'ID invalide'], 400);
        }

        $res = $this->service->toggleStatus($convId);
        $conv = $this->service->getConversationById($convId);

        $this->jsonResponse([
            'success' => $res,
            'new_status' => $conv['status'] ?? 'open'
        ]);
    }

    /**
     * Sauvegarde de la configuration LiveChat
     */
    public function saveConfig(): void
    {
        $webhook = trim($_POST['discord_webhook'] ?? '');
        $adminName = trim($_POST['admin_name'] ?? 'Équipe Vivre-Bourges');
        $welcome = trim($_POST['welcome_message'] ?? '');

        $configFile = DATA_PLUGIN . 'LiveChat/config.json';
        $current = $this->service->getConfig();
        $current['discord_webhook'] = $webhook;
        $current['admin_name'] = $adminName ?: 'Équipe Vivre-Bourges';
        if (!empty($welcome)) {
            $current['welcome_message'] = $welcome;
        }

        file_put_contents($configFile, json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        // Mettre à jour aussi dans config_pro_chat.json si utile
        $proConfigFile = ROOT . 'data/config_pro_chat.json';
        if (file_exists($proConfigFile)) {
            $p = json_decode(file_get_contents($proConfigFile), true) ?: [];
            $p['discord_webhook'] = $webhook;
            file_put_contents($proConfigFile, json_encode($p, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }

        $this->jsonResponse(['success' => true, 'message' => 'Configuration enregistrée']);
    }
}
