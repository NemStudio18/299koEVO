<?php

namespace SessionReporter\Controllers;

use Core\Controllers\AdminController;
use Core\Core;
use Utils\Util;
use SessionReporter\Services\CollectorService;

/**
 * Admin Dashboard for SessionReporter.
 * Displays grouped incidents, heatmaps, and beta codes.
 */
class ReporterAdminController extends AdminController
{
    private string $dbPath;

    public function __construct()
    {
        parent::__construct();
        $this->dbPath = DATA . 'SessionReporter' . DS . 'debug.sqlite';
    }

    public function index()
    {
        $db = $this->getDb();
        $collector = CollectorService::getInstance();
        
        // 1. Incidents
        $groups = [];
        if ($db) {
            $query = "SELECT fingerprint, type, 
                             MIN(content) as sample_content, 
                             COUNT(*) as occurrences,
                             MAX(created_at) as last_seen
                      FROM events
                      GROUP BY fingerprint
                      ORDER BY last_seen DESC";
            $groups = $db->query($query)->fetchAll();
            foreach ($groups as &$g) {
                $content = json_decode($g['sample_content'], true);
                $g['file'] = $content['file'] ?? 'unknown';
                $g['line'] = $content['line'] ?? 0;
                $g['message'] = $content['message'] ?? 'No message';
                $g['severity_score'] = $this->calculateSeverity($g['type'], $g['occurrences'], $content, $g['last_seen']);
            }
            usort($groups, fn($a, $b) => $b['severity_score'] <=> $a['severity_score']);
        }

        // 2. Heatmap
        $heatmap = [];
        if ($db) {
            $events = $db->query("SELECT content FROM events WHERE type != 'heartbeat'")->fetchAll();
            foreach ($events as $e) {
                $content = json_decode($e['content'], true);
                $uri = $content['uri'] ?? ($content['url'] ?? 'unknown');
                $normUri = $collector->normalizeUri($uri);
                if (!isset($heatmap[$normUri])) {
                    $heatmap[$normUri] = ['uri' => $normUri, 'count' => 0];
                }
                $heatmap[$normUri]['count']++;
            }
            usort($heatmap, fn($a, $b) => $b['count'] <=> $a['count']);
        }

        // 3. Beta Codes
        $codes = $db ? $db->query("SELECT * FROM beta_codes ORDER BY created_at DESC")->fetchAll() : [];

        $tpl = $this->createTemplate('dashboard');
        $tpl->set('groups', $groups);
        $tpl->set('heatmap', $heatmap);
        $tpl->set('codes', $codes);
        $tpl->set('generateUrl', \Core\Router\Router::getInstance()->generate('admin-sessionreporter-generate-code'));
        
        return $this->render($tpl);
    }

    /**
     * POST /admin/SessionReporter/generate-code
     */
    public function generateCode()
    {
        $db = $this->getDb();
        $code = strtoupper(bin2hex(random_bytes(4)));
        $adminId = \Core\Auth\UsersManager::getCurrentUser()->getId();
        
        $stmt = $db->prepare("INSERT INTO beta_codes (code, generated_by_admin_id, expires_at) 
                             VALUES (:code, :admin, :expires)");
        $stmt->execute([
            ':code' => $code,
            ':admin' => $adminId,
            ':expires' => date('Y-m-d H:i:s', strtotime('+30 days'))
        ]);
        
        \Utils\Show::msg("Code bêta généré avec succès.", 'success');
        header('Location: ' . \Core\Router\Router::getInstance()->generate('admin-sessionreporter'));
        exit;
    }

    /**
     * Set a secure HMAC-signed debug cookie for the developer.
     */
    public function setDebugToken()
    {
        $sessionId = session_id() ?: bin2hex(random_bytes(16));
        $timestamp = time();
        $secret = Core::getInstance()->getEnv('APP_SECRET', 'default_secret');
        
        $signature = hash_hmac('sha256', $sessionId . '|' . $timestamp, $secret);
        $token = base64_encode($sessionId . '|' . $timestamp . '|' . $signature);
        
        setcookie('SR_DEBUG_KEY', $token, [
            'expires' => time() + 3600,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        \Utils\Show::msg("Debug Token (1h) set successfully.", 'success');
        header('Location: ' . \Core\Router\Router::getInstance()->generate('admin-sessionreporter'));
        exit;
    }

    private function calculateSeverity(string $type, int $occurrences, array $content, string $lastSeen): float
    {
        $baseWeights = [
            'fatal' => 50,
            '500' => 40,
            'js_error' => 20,
            'slow_request' => 10,
            '404' => 5,
            'access_denied' => 15
        ];

        $weight = $baseWeights[$type] ?? 10;
        
        // 1. Logarithmic frequency factor
        $frequencyFactor = log($occurrences + 1, 2);

        // 2. Recency boost (Incidents within the last 24h get a bonus)
        $lastSeenTs = strtotime($lastSeen);
        $hoursOld = (time() - $lastSeenTs) / 3600;
        $recencyBonus = max(0, 50 - $hoursOld); // Decreases over 50 hours

        return ($weight * $frequencyFactor) + $recencyBonus;
    }

    private function getDb(): ?\PDO
    {
        return CollectorService::getInstance()->getDb();
    }

    private function createTemplate(string $name)
    {
        // CMS specific template creation
        $response = new \Core\Responses\AdminResponse();
        return $response->createPluginTemplate('SessionReporter', $name);
    }

    private function render($tpl)
    {
        $response = new \Core\Responses\AdminResponse();
        $response->addTemplate($tpl);
        return $response;
    }
}
