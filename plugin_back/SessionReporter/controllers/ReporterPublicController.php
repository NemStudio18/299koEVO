<?php

namespace SessionReporter\Controllers;

use Core\Controllers\PublicController;
use Core\Core;
use Core\Logger;
use Core\Security\Csrf;
use SessionReporter\Services\CollectorService;

/**
 * Endpoint for AJAX debug collection (JS errors, heartbeats, manual reports).
 */
class ReporterPublicController extends PublicController
{
    /**
     * POST /SessionReporter/log-js-error
     */
    public function logJsError()
    {
        $this->validateAjaxSecurity();

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Invalid data'], 400);
        }

        // Rate limiting (simple session-based counter)
        $this->checkRateLimit('js_errors', 10, 60);

        $collector = CollectorService::getInstance();
        $collector->logEvent('js_error', [
            'message' => $data['message'] ?? 'Unknown JS Error',
            'file' => $data['url'] ?? 'unknown',
            'line' => $data['line'] ?? 0,
            'column' => $data['column'] ?? 0,
            'stack' => $data['stack'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);

        $this->jsonResponse(['status' => 'success']);
    }

    /**
     * POST /SessionReporter/submit-report
     */
    public function submitReport()
    {
        $this->validateAjaxSecurity();

        $data = $_POST['report'] ?? null;
        if (!$data || !is_array($data)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Missing report data'], 400);
        }

        $this->checkRateLimit('bug_reports', 3, 3600); // Max 3 reports per hour

        $collector = CollectorService::getInstance();
        
        // Final enrichment before gelling
        $reportData = [
            'type' => $data['type'] ?? 'general',
            'user_comment' => $data['comment'] ?? '',
            'reproduce_steps' => $data['steps'] ?? '',
            'snapshots' => [
                'plugins' => $collector->getPluginAudit(),
                'server' => $this->getServerSnapshot(),
                'performance' => [
                    'peak_memory' => memory_get_peak_usage(true),
                    'execution_time' => microtime(true) - Core::getInstance()->startTime
                ]
            ]
        ];

        $collector->logEvent('manual_report', $reportData);

        // TODO: Gelling (Export to JSON file)
        $this->jsonResponse(['status' => 'success']);
    }

    private function validateAjaxSecurity(): void
    {
        if (!Csrf::validate($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '', false)) {
            $this->jsonResponse(['status' => 'error', 'message' => 'CSRF Token Invalid'], 403);
        }

        // Must be Beta user
        $user = \Core\Auth\UsersManager::getCurrentUser();
        if (!$user || !($user->attributes['is_beta'] ?? false)) {
            // Check if debug cookie is present (dev mode)
            if (!isset($_COOKIE['SR_DEBUG_KEY'])) {
                $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
            }
        }
    }

    private function checkRateLimit(string $key, int $limit, int $period): void
    {
        $sessionKey = "sr_rate_limit_$key";
        $now = time();
        
        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = ['count' => 0, 'start' => $now];
        }

        $rl = &$_SESSION[$sessionKey];
        if ($now - $rl['start'] > $period) {
            $rl = ['count' => 1, 'start' => $now];
        } else {
            $rl['count']++;
        }

        if ($rl['count'] > $limit) {
            $this->jsonResponse(['status' => 'error', 'message' => 'Rate limit exceeded'], 429);
        }
    }

    private function getServerSnapshot(): array
    {
        $whitelist = ['REQUEST_URI', 'REQUEST_METHOD', 'HTTP_USER_AGENT', 'HTTP_REFERER', 'SERVER_PROTOCOL'];
        $snapshot = [];
        foreach ($whitelist as $key) {
            if (isset($_SERVER[$key])) {
                $snapshot[$key] = $_SERVER[$key];
            }
        }
        
        // Anonymize IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $snapshot['ip_hash'] = hash('sha256', $ip . Core::getInstance()->getEnv('APP_SECRET', 'salt'));
        
        return $snapshot;
    }

    private function jsonResponse(array $data, int $code = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode($data);
        exit;
    }
}
