<?php

namespace LightStats\Controllers;

use Core\Controllers\Controller;
use LightStats\Lib\LightStatsAnalyticsService;

defined('ROOT') or exit('Access denied!');

class LightStatsApiController extends Controller
{
    /**
     * Reçoit et enregistre les micro-événements (clics, scans, impressions, etc.)
     */
    public function logEvent()
    {
        header('Content-Type: application/json');
        
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: $_POST;

        $sessionId = trim($data['session_id'] ?? '');
        $visitorId = !empty($data['visitor_id']) ? trim($data['visitor_id']) : null;
        $eventId = !empty($data['event_id']) ? trim($data['event_id']) : null;
        $rawReqId = !empty($data['request_id']) && is_string($data['request_id']) ? trim($data['request_id']) : '';
        $requestId = preg_match('/^req_[a-f0-9]{32}$/', $rawReqId) ? $rawReqId : null;
        $module = trim($data['module'] ?? 'other');
        $eventType = trim($data['event_type'] ?? 'unknown');
        $page = trim($data['page'] ?? ($_SERVER['HTTP_REFERER'] ?? '/'));
        $eventData = is_array($data['event_data'] ?? null) ? $data['event_data'] : [];

        // Fallback serveur : si le session_id est manquant ou non conforme, utiliser le cookie HttpOnly vb_sid
        if (empty($sessionId) || !preg_match('/^[a-f0-9]{32}$/', $sessionId)) {
            $cookieSid = $_COOKIE['vb_sid'] ?? null;
            if (is_string($cookieSid) && preg_match('/^[a-f0-9]{32}$/', $cookieSid)) {
                $sessionId = $cookieSid;
            } else {
                $sessionId = bin2hex(random_bytes(16));
                if (!headers_sent()) {
                    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
                        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
                    setcookie('vb_sid', $sessionId, [
                        'expires'  => time() + 1800,
                        'path'     => '/',
                        'secure'   => $isHttps,
                        'httponly' => true,
                        'samesite' => 'Lax',
                    ]);
                }
            }
        }

        // 2. Consentement et visiteur : le cookie vb_vid est un signal de consentement présent et conforme (format UUID v4)
        // Ce cookie n'est pas une preuve cryptographique infalsifiable, mais un signal d'accord stocké côté client.
        // Tout visitor_id transmis arbitrairement dans le corps JSON sans cookie vb_vid conforme est strictement ignoré.
        $cookieVid = $_COOKIE['vb_vid'] ?? null;
        $isValidCookieVid = is_string($cookieVid) && preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i', $cookieVid);

        if ($isValidCookieVid) {
            // Cookie de consentement présent et conforme : on retient ce visitor_id
            $visitorId = $cookieVid;
        } else {
            // Aucun cookie de consentement valide : tout identifiant fourni arbitrairement dans le JSON est rejeté (anonymat strict)
            $visitorId = null;
        }

        $responsePayload = ['status' => 'ok'];

        if (!empty($eventType)) {
            $service = LightStatsAnalyticsService::getInstance();
            $res = $service->recordEvent($sessionId, $visitorId, $module, $eventType, $page, $eventData, $eventId, $requestId);
            if (!empty($res['code']) && $res['code'] !== 200) {
                http_response_code($res['code']);
            }
            $responsePayload = $res;
        }

        echo json_encode($responsePayload);
        exit;
    }
}
