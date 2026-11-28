<?php

namespace Core\Workflow;

defined('ROOT') or exit('Access denied!');

use Core\Core;
use Core\Tasks\TaskScheduler;
use Utils\Util;

/**
 * WorkflowManager - Moteur d'automatisation et d'orchestration d'événements sans code.
 * 
 * Permet de définir des règles :
 * Événement (Trigger) -> Conditions (Filtres) -> Actions (Webhooks, Emails, Tâches).
 */
class WorkflowManager
{
    private static ?WorkflowManager $instance = null;
    private static bool $bootstrapped = false;

    private string $workflowsFile;
    private string $historyFile;
    private array $workflows = [];

    private const MAX_HISTORY = 100;

    public function __construct()
    {
        $dir = defined('DATA_CORE') ? DATA_CORE : (defined('ROOT') ? ROOT . 'data/core/' : sys_get_temp_dir() . '/');
        $this->workflowsFile = $dir . 'workflows.json';
        $this->historyFile = $dir . 'workflow_history.json';
        $this->loadWorkflows();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Enregistre les écouteurs de hooks sur le bus d'événements du Core.
     */
    public static function bootstrap(): void
    {
        if (self::$bootstrapped) {
            return;
        }
        self::$bootstrapped = true;

        $manager = self::getInstance();
        $triggers = $manager->getActiveTriggers();

        if (empty($triggers)) {
            return;
        }

        $core = Core::getInstance();
        foreach ($triggers as $triggerName) {
            $core->addHook($triggerName, function ($payload = null) use ($manager, $triggerName) {
                $manager->trigger($triggerName, is_array($payload) ? $payload : ['data' => $payload]);
            });
        }
    }

    /**
     * Retourne le catalogue des déclencheurs standards disponibles.
     */
    public function getAvailableTriggers(): array
    {
        return [
            'blog:post_published' => [
                'label' => 'Blog : Article publié',
                'description' => 'Déclenché dès qu\'un nouvel article est publié ou programmé.',
                'sample' => ['id' => 1, 'title' => 'Mon premier article', 'slug' => 'mon-premier-article', 'category' => 'Actualités', 'author' => 'Admin']
            ],
            'user:registered' => [
                'label' => 'Membres : Inscription d\'un utilisateur',
                'description' => 'Déclenché lorsqu\'un nouvel utilisateur crée un compte.',
                'sample' => ['id' => 2, 'email' => 'visiteur@example.com', 'username' => 'jean_dupont', 'group' => 'Membres']
            ],
            'contact:message_sent' => [
                'label' => 'Contact : Message envoyé',
                'description' => 'Déclenché lorsqu\'un formulaire de contact est soumis avec succès.',
                'sample' => ['name' => 'Alice Martin', 'email' => 'alice@example.com', 'subject' => 'Demande d\'information', 'message' => 'Bonjour...']
            ],
            'ecommerce:order_paid' => [
                'label' => 'Boutique : Commande payée',
                'description' => 'Déclenché quand un paiement est confirmé sur la boutique.',
                'sample' => ['order_id' => 101, 'amount' => 49.90, 'customer_email' => 'client@example.com', 'items_count' => 2]
            ],
            'annonces:created' => [
                'label' => 'Petites Annonces : Nouvelle annonce',
                'description' => 'Déclenché lors de la création d\'une annonce.',
                'sample' => ['title' => 'Vélo de course', 'price' => 120, 'category' => 'Sport', 'city' => 'Paris']
            ],
            'system:cron_hourly' => [
                'label' => 'Planificateur : Toutes les heures',
                'description' => 'Déclenché toutes les heures via le TaskScheduler.',
                'sample' => ['timestamp' => time(), 'hour' => date('H')]
            ],
            'system:cron_daily' => [
                'label' => 'Planificateur : Quotidien (matin)',
                'description' => 'Déclenché une fois par jour à minuit ou au premier hit.',
                'sample' => ['timestamp' => time(), 'date' => date('Y-m-d')]
            ],
        ];
    }

    /**
     * Déclenche l'évaluation des workflows configurés pour un événement.
     */
    public function trigger(string $triggerName, array $payload = []): array
    {
        $executed = [];

        foreach ($this->workflows as $workflow) {
            if (empty($workflow['is_enabled']) || ($workflow['trigger'] ?? '') !== $triggerName) {
                continue;
            }

            // Vérifier les conditions
            if (!$this->evaluateConditions($workflow['conditions'] ?? [], $payload)) {
                continue;
            }

            // Exécution immédiate ou asynchrone
            if (!empty($workflow['is_async'])) {
                $taskId = TaskScheduler::getInstance()->enqueue(
                    'Core\\Workflow\\WorkflowManager::executeAsync',
                    [
                        'workflow_id' => $workflow['id'],
                        'payload' => $payload,
                    ],
                    null,
                    3,
                    5
                );
                $executed[] = [
                    'id' => $workflow['id'],
                    'name' => $workflow['name'],
                    'mode' => 'async',
                    'task_id' => $taskId,
                ];
            } else {
                $result = $this->executeActions($workflow, $payload);
                $executed[] = $result;
            }
        }

        return $executed;
    }

    /**
     * Point d'entrée pour l'exécution asynchrone via TaskScheduler.
     */
    public static function executeAsync(array $data): void
    {
        $workflowId = $data['workflow_id'] ?? '';
        $payload = $data['payload'] ?? [];

        $manager = self::getInstance();
        $workflow = $manager->getWorkflow($workflowId);

        if (!$workflow || empty($workflow['is_enabled'])) {
            return;
        }

        $manager->executeActions($workflow, $payload);
    }

    /**
     * Exécute les actions d'un workflow et enregistre l'historique.
     */
    public function executeActions(array $workflow, array $payload): array
    {
        $startTime = microtime(true);
        $actionResults = [];
        $hasErrors = false;

        $actions = $workflow['actions'] ?? [];
        foreach ($actions as $action) {
            $type = $action['type'] ?? '';
            try {
                switch ($type) {
                    case 'webhook':
                        $res = $this->executeWebhookAction($action, $payload);
                        $actionResults[] = ['type' => 'webhook', 'success' => true, 'response' => $res];
                        break;

                    case 'email':
                        $res = $this->executeEmailAction($action, $payload);
                        $actionResults[] = ['type' => 'email', 'success' => true, 'response' => $res];
                        break;

                    case 'log':
                        $msg = $this->interpolate($action['message'] ?? '', $payload);
                        Core::getInstance()->log($msg);
                        $actionResults[] = ['type' => 'log', 'success' => true];
                        break;

                    default:
                        $actionResults[] = ['type' => $type, 'success' => false, 'error' => "Action inconnue: {$type}"];
                        $hasErrors = true;
                }
            } catch (\Throwable $e) {
                $actionResults[] = ['type' => $type, 'success' => false, 'error' => $e->getMessage()];
                $hasErrors = true;
            }
        }

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        $logEntry = [
            'id' => uniqid('wfl_', true),
            'timestamp' => date('Y-m-d H:i:s'),
            'workflow_id' => $workflow['id'],
            'workflow_name' => $workflow['name'],
            'trigger' => $workflow['trigger'],
            'success' => !$hasErrors,
            'duration_ms' => $durationMs,
            'actions' => $actionResults,
        ];

        $this->recordHistory($logEntry);

        // Mettre à jour les stats du workflow
        $this->incrementWorkflowStats($workflow['id'], !$hasErrors);

        return $logEntry;
    }

    /**
     * Exécute une action de type Webhook (Requête HTTP externe).
     */
    private function executeWebhookAction(array $action, array $payload): array
    {
        $url = trim($this->interpolate($action['url'] ?? '', $payload));
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("URL Webhook invalide : '{$url}'");
        }

        $method = strtoupper($action['method'] ?? 'POST');
        $bodyTemplate = $action['body'] ?? '';

        $body = '';
        if (!empty($bodyTemplate)) {
            $body = $this->interpolate($bodyTemplate, $payload);
        } else {
            $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        $headers = [
            'Content-Type: application/json',
            'User-Agent: 299koEVO-Workflow/1.0',
        ];

        if (!empty($action['headers']) && is_string($action['headers'])) {
            foreach (explode("\n", $action['headers']) as $h) {
                $h = trim($h);
                if (!empty($h)) {
                    $headers[] = $this->interpolate($h, $payload);
                }
            }
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        } elseif ($method === 'PUT' || $method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException("Échec cURL : " . $curlError);
        }

        if ($httpCode >= 400) {
            throw new \RuntimeException("Réponse HTTP d'erreur {$httpCode} : " . substr((string)$response, 0, 200));
        }

        return ['http_code' => $httpCode, 'response' => substr((string)$response, 0, 500)];
    }

    /**
     * Exécute une action d'envoi d'e-mail.
     */
    private function executeEmailAction(array $action, array $payload): array
    {
        $to = trim($this->interpolate($action['to'] ?? '', $payload));
        if ($to === '{ADMIN_EMAIL}' || empty($to)) {
            $to = (string)Core::getInstance()->getConfigVal('email');
        }

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Adresse e-mail destinataire invalide : '{$to}'");
        }

        $subject = $this->interpolate($action['subject'] ?? 'Notification 299ko', $payload);
        $message = $this->interpolate($action['message'] ?? '', $payload);

        $siteName = (string)Core::getInstance()->getConfigVal('siteName');
        $siteEmail = (string)Core::getInstance()->getConfigVal('email');

        $headers = [
            'From: ' . $siteName . ' <' . $siteEmail . '>',
            'Reply-To: ' . $siteEmail,
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'X-Mailer: 299koEVO Workflow Engine',
        ];

        $htmlMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
        $body = "<div style='font-family: sans-serif; font-size: 15px; color: #333; line-height: 1.6;'>"
              . "<h2>Notification Automatisée</h2>"
              . "<p>{$htmlMessage}</p>"
              . "<hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>"
              . "<small style='color: #888;'>Envoyé par le Workflow Engine de {$siteName}</small>"
              . "</div>";

        $sent = @mail($to, $subject, $body, implode("\r\n", $headers));
        if (!$sent && function_exists('error_get_last')) {
            $err = error_get_last();
            throw new \RuntimeException("Erreur envoi mail : " . ($err['message'] ?? 'inconnue'));
        }

        return ['to' => $to, 'subject' => $subject, 'sent' => $sent];
    }

    /**
     * Évalue un ensemble de conditions logiques face au payload.
     */
    public function evaluateConditions(array $conditions, array $payload): bool
    {
        if (empty($conditions)) {
            return true; // Pas de conditions = toujours exécuter
        }

        foreach ($conditions as $cond) {
            $field = $cond['field'] ?? '';
            $operator = $cond['operator'] ?? 'equals';
            $expected = $cond['value'] ?? '';

            $actual = $this->getNestedValue($payload, $field);

            if (!$this->checkCondition($actual, $operator, $expected)) {
                return false; // Règle AND stricte
            }
        }

        return true;
    }

    /**
     * Vérifie une condition individuelle.
     */
    private function checkCondition($actual, string $operator, $expected): bool
    {
        $actualStr = is_scalar($actual) ? (string)$actual : json_encode($actual);
        $expectedStr = (string)$expected;

        switch ($operator) {
            case 'equals':
                return strcasecmp($actualStr, $expectedStr) === 0;

            case 'not_equals':
                return strcasecmp($actualStr, $expectedStr) !== 0;

            case 'contains':
                return stripos($actualStr, $expectedStr) !== false;

            case 'not_contains':
                return stripos($actualStr, $expectedStr) === false;

            case 'greater_than':
                return is_numeric($actual) && is_numeric($expected) && ((float)$actual > (float)$expected);

            case 'less_than':
                return is_numeric($actual) && is_numeric($expected) && ((float)$actual < (float)$expected);

            case 'is_empty':
                return empty($actual);

            case 'is_not_empty':
                return !empty($actual);

            default:
                return true;
        }
    }

    /**
     * Remplace les balises de type {{field.subfield}} par les données du payload.
     */
    public function interpolate(string $template, array $payload): string
    {
        $core = Core::getInstance();
        $globals = [
            'site.name' => (string)$core->getConfigVal('siteName'),
            'site.url' => rtrim((string)$core->getConfigVal('siteUrl'), '/'),
            'date' => date('Y-m-d'),
            'time' => date('H:i:s'),
            'datetime' => date('Y-m-d H:i:s'),
        ];

        return preg_replace_callback('/\{\{([a-zA-Z0-9_\-\.]+)\}\}/', function ($matches) use ($payload, $globals) {
            $key = $matches[1];
            if (isset($globals[$key])) {
                return $globals[$key];
            }
            $val = $this->getNestedValue($payload, $key);
            if ($val !== null) {
                return is_scalar($val) ? (string)$val : json_encode($val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            return $matches[0];
        }, $template);
    }

    /**
     * Extrait une valeur imbriquée avec la notation par point (ex: user.email).
     */
    private function getNestedValue(array $data, string $key)
    {
        if (array_key_exists($key, $data)) {
            return $data[$key];
        }

        $parts = explode('.', $key);
        $current = $data;

        foreach ($parts as $part) {
            if (is_array($current) && array_key_exists($part, $current)) {
                $current = $current[$part];
            } else {
                return null;
            }
        }

        return $current;
    }

    /**
     * Charge la liste des workflows configurés.
     */
    private function loadWorkflows(): void
    {
        if (file_exists($this->workflowsFile)) {
            $data = Util::readJsonFile($this->workflowsFile, true);
            if (is_array($data)) {
                $this->workflows = $data;
                return;
            }
        }
        $this->workflows = [];
    }

    /**
     * Enregistre l'ensemble des workflows sur le disque.
     */
    public function saveWorkflows(array $workflows): bool
    {
        $this->workflows = $workflows;
        return (bool)Util::writeJsonFile($this->workflowsFile, $workflows);
    }

    public function getWorkflows(): array
    {
        return $this->workflows;
    }

    public function getWorkflow(string $id): ?array
    {
        foreach ($this->workflows as $wf) {
            if (($wf['id'] ?? '') === $id) {
                return $wf;
            }
        }
        return null;
    }

    public function saveWorkflow(array $workflowData): bool
    {
        if (empty($workflowData['id'])) {
            $workflowData['id'] = uniqid('wf_', true);
            $workflowData['created_at'] = date('Y-m-d H:i:s');
            $workflowData['runs_count'] = 0;
            $workflowData['success_count'] = 0;
            $workflowData['failure_count'] = 0;
            $workflowData['last_run'] = null;
        }

        $workflowData['updated_at'] = date('Y-m-d H:i:s');

        $found = false;
        foreach ($this->workflows as $idx => $existing) {
            if ($existing['id'] === $workflowData['id']) {
                $this->workflows[$idx] = array_merge($existing, $workflowData);
                $found = true;
                break;
            }
        }

        if (!$found) {
            $this->workflows[] = $workflowData;
        }

        return $this->saveWorkflows($this->workflows);
    }

    public function deleteWorkflow(string $id): bool
    {
        $initialCount = count($this->workflows);
        $this->workflows = array_values(array_filter($this->workflows, fn($wf) => ($wf['id'] ?? '') !== $id));
        if (count($this->workflows) < $initialCount) {
            return $this->saveWorkflows($this->workflows);
        }
        return false;
    }

    public function toggleWorkflow(string $id): bool
    {
        foreach ($this->workflows as &$wf) {
            if (($wf['id'] ?? '') === $id) {
                $wf['is_enabled'] = empty($wf['is_enabled']) ? 1 : 0;
                return $this->saveWorkflows($this->workflows);
            }
        }
        return false;
    }

    private function incrementWorkflowStats(string $id, bool $success): void
    {
        foreach ($this->workflows as &$wf) {
            if (($wf['id'] ?? '') === $id) {
                $wf['runs_count'] = ($wf['runs_count'] ?? 0) + 1;
                if ($success) {
                    $wf['success_count'] = ($wf['success_count'] ?? 0) + 1;
                } else {
                    $wf['failure_count'] = ($wf['failure_count'] ?? 0) + 1;
                }
                $wf['last_run'] = date('Y-m-d H:i:s');
                break;
            }
        }
        $this->saveWorkflows($this->workflows);
    }

    public function getActiveTriggers(): array
    {
        $triggers = [];
        foreach ($this->workflows as $wf) {
            if (!empty($wf['is_enabled']) && !empty($wf['trigger'])) {
                $triggers[] = $wf['trigger'];
            }
        }
        return array_unique($triggers);
    }

    /**
     * Enregistre l'exécution dans le journal d'historique.
     */
    private function recordHistory(array $entry): void
    {
        $history = $this->getHistory();
        array_unshift($history, $entry);
        if (count($history) > self::MAX_HISTORY) {
            $history = array_slice($history, 0, self::MAX_HISTORY);
        }
        @file_put_contents($this->historyFile, json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    public function getHistory(): array
    {
        if (file_exists($this->historyFile)) {
            $decoded = json_decode((string)@file_get_contents($this->historyFile), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    public function clearHistory(): bool
    {
        if (file_exists($this->historyFile)) {
            @unlink($this->historyFile);
        }
        return true;
    }
}

