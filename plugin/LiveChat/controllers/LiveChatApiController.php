<?php

namespace LiveChat\Controllers;

use LiveChat\Services\LiveChatService;

defined('ROOT') or exit('Access denied!');

class LiveChatApiController
{
    private LiveChatService $service;

    public function __construct()
    {
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
     * Initialise ou récupère la conversation active d'un visiteur
     */
    public function init(): void
    {
        $token = trim($_POST['session_token'] ?? '');
        if (empty($token) || strlen($token) < 16) {
            $token = bin2hex(random_bytes(16));
        }

        $name = trim($_POST['visitor_name'] ?? '');
        $contact = trim($_POST['visitor_contact'] ?? '');

        try {
            $conv = $this->service->getOrCreateConversation($token, $name ?: null, $contact ?: null);
            $messages = $this->service->getMessages((int)$conv['id'], 0);
            $this->service->markAsRead((int)$conv['id'], 'visitor');

            $this->jsonResponse([
                'success' => true,
                'session_token' => $token,
                'conversation' => $conv,
                'messages' => $messages,
                'admin_name' => $this->service->getConfig()['admin_name'] ?? 'Équipe Support'
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    /**
     * Envoie un message depuis le visiteur
     */
    public function send(): void
    {
        // Honeypot anti-spam
        if (!empty($_POST['_gotcha'])) {
            $this->jsonResponse(['success' => true]);
        }

        $token = trim($_POST['session_token'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $name = trim($_POST['visitor_name'] ?? '');
        $contact = trim($_POST['visitor_contact'] ?? '');

        if (empty($token)) {
            $this->jsonResponse(['success' => false, 'error' => 'Session invalide'], 400);
        }
        if (empty($message)) {
            $this->jsonResponse(['success' => false, 'error' => 'Veuillez saisir un message'], 400);
        }

        try {
            $conv = $this->service->getOrCreateConversation($token, $name ?: null, $contact ?: null);
            $msg = $this->service->addMessage((int)$conv['id'], 'visitor', $message, $name ?: $conv['visitor_name']);

            $this->jsonResponse([
                'success' => true,
                'message' => $msg,
                'conversation_id' => $conv['id']
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    /**
     * Polling des nouveaux messages reçus (côté visiteur)
     */
    public function poll(): void
    {
        $token = trim($_GET['session_token'] ?? '');
        $afterId = max(0, (int)($_GET['after_id'] ?? 0));

        if (empty($token)) {
            $this->jsonResponse(['success' => false, 'error' => 'Token manquant'], 400);
        }

        $conv = $this->service->getConversationByToken($token);
        if (!$conv) {
            $this->jsonResponse(['success' => true, 'messages' => []]);
        }

        $messages = $this->service->getMessages((int)$conv['id'], $afterId);
        if (!empty($messages)) {
            $this->service->markAsRead((int)$conv['id'], 'visitor');
        }

        $this->jsonResponse([
            'success' => true,
            'messages' => $messages,
            'status' => $conv['status']
        ]);
    }
}
