<?php

defined('ROOT') or exit('Access denied!');

use Core\Core;
use Core\Logger;
use Core\Auth\UsersManager;
use SessionReporter\Services\CollectorService;

/**
 * SessionReporter Plugin
 * Industrial-grade debug collector with Beta-only gating.
 */

function SessionReporterInstall(): void
{
    $dataDir = DATA_PLUGIN . 'SessionReporter' . DS;
    if (!is_dir($dataDir)) {
        @mkdir($dataDir, 0755, true);
    }
}

function SessionReporterBeforeRunPlugin(): void
{
    // Load services and controllers
    foreach (glob(PLUGINS . 'SessionReporter/services/*.php') as $file) {
        require_once $file;
    }
    foreach (glob(PLUGINS . 'SessionReporter/controllers/*.php') as $file) {
        require_once $file;
    }

    // Gating check for active capture
    if (!SessionReporterIsActive()) {
        return;
    }

    // Initialize session id if not present
    if (session_status() === PHP_SESSION_ACTIVE && !isset($_SESSION['debug_session_id'])) {
        $_SESSION['debug_session_id'] = bin2hex(random_bytes(16));
    }

    // Bootstrap early capture
    register_shutdown_function('SessionReporterShutdownHandler');
}

/**
 * Gating logic: Beta users or specific debug cookie.
 */
function SessionReporterIsActive(): bool
{
    // Master Switch
    $core = Core::getInstance();
    if ($core->getConfigVal('session_reporter_disabled')) {
        return false;
    }

    // Beta User check
    $user = UsersManager::getCurrentUser();
    if ($user && ($user->attributes['is_beta'] ?? false)) {
        return true;
    }

    // Debug Cookie (Developer mode - Secure HMAC)
    if (isset($_COOKIE['SR_DEBUG_KEY'])) {
        $encoded = base64_decode($_COOKIE['SR_DEBUG_KEY']);
        $parts = explode('|', $encoded);
        if (count($parts) === 3) {
            [$sessionId, $timestamp, $signature] = $parts;
            $secret = Core::getInstance()->getEnv('APP_SECRET', 'default_secret');
            
            // Check expiration (1 hour)
            if (time() - (int)$timestamp > 3600) return false;

            // Validate signature
            $expected = hash_hmac('sha256', $sessionId . '|' . $timestamp, $secret);
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Handle fatal errors and log them to SQLite via CollectorService.
 */
function SessionReporterShutdownHandler(): void
{
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $collector = CollectorService::getInstance();
        $collector->logEvent('fatal', [
            'message' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line'],
            'type_name' => 'PHP Fatal Error'
        ]);
    }
}

/**
 * Hook: publicContent - Inject Beta Code field in register form.
 */
function SessionReporterPublicContent(string $content): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, '/users/register') !== false) {
        $fieldHtml = '
            <div class="form-group mb-3">
                <label for="beta_code" class="form-label">Code Bêta (Obligatoire)</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-key"></i></span>
                    <input type="text" name="beta_code" id="beta_code" class="form-control" placeholder="Entrez votre code d\'invitation" required>
                </div>
                <div class="form-text">Ce système est actuellement en bêta fermée.</div>
            </div>';
        
        // Inject before the submit button or at the end of the form
        if (strpos($content, '<button type="submit"') !== false) {
            $content = str_replace('<button type="submit"', $fieldHtml . '<button type="submit"', $content);
        } else {
            $content .= $fieldHtml;
        }
    }
    return $content;
}

/**
 * Hook: frontHead - Inject debug.js for Beta users.
 */
function SessionReporterFrontHead(): string
{
    if (!SessionReporterIsActive()) return '';

    $siteUrl = Core::getInstance()->getConfigVal('siteUrl');
    $jsUrl = $siteUrl . '/plugin/SessionReporter/assets/js/debug.js';
    $csrf = \Core\Security\Csrf::token();

    return '
    <script>
        window.SR_CSRF_TOKEN = "' . $csrf . '";
        window.SR_LOG_URL = "' . $siteUrl . '/sessionreporter/log-js-error";
        window.SR_REPORT_URL = "' . $siteUrl . '/sessionreporter/submit-report";
    </script>
    <script src="' . $jsUrl . '?v=' . VERSION . '" defer></script>';
}

/**
 * Hook: footer - Inject "Signaler" button and reporting modal.
 */
function SessionReporterFooter(): string
{
    if (!SessionReporterIsActive()) return '';

    // Only for Beta users
    $user = UsersManager::getCurrentUser();
    if (!$user || !($user->attributes['is_beta'] ?? false)) {
        if (!isset($_COOKIE['SR_DEBUG_KEY'])) return '';
    }

    return '
    <!-- Reporting FAB -->
    <div id="sr-fab" style="position:fixed; bottom:20px; right:20px; z-index:9999;">
        <button class="btn btn-danger rounded-circle shadow-lg p-3" onclick="openReporter()" title="Signaler un bug">
            <i class="fa-solid fa-bug fa-xl"></i>
        </button>
    </div>

    <!-- Reporting Modal -->
    <div id="sr-modal" class="d-none" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10000; overflow-y:auto;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fa-solid fa-bug-slash me-2"></i> Signaler un problème</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="closeReporter()"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="sr-form">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Type de problème</label>
                            <select name="type" class="form-select" required>
                                <option value="visual">Problème visuel</option>
                                <option value="functional">Dysfonctionnement (lien cassé, erreur)</option>
                                <option value="performance">Lenteur excessive</option>
                                <option value="suggestion">Suggestion / Amélioration</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="comment" class="form-control" rows="3" placeholder="Que s\'est-il passé ?" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Étapes pour reproduire (Optionnel)</label>
                            <textarea name="steps" class="form-control" rows="2" placeholder="1. J\'ai cliqué sur... 2. Ensuite..."></textarea>
                        </div>
                        <div id="sr-status" class="alert d-none"></div>
                        <button type="submit" class="btn btn-danger w-100 py-2 fw-bold">
                            <i class="fa-solid fa-paper-plane me-1"></i> Envoyer le rapport
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        function openReporter() { document.getElementById("sr-modal").classList.remove("d-none"); }
        function closeReporter() { document.getElementById("sr-modal").classList.add("d-none"); }
    </script>';
}

/**
 * Hook: users:register-validation - Validate Beta Code.
 */
function SessionReporterRegisterValidation(): void
{
    $code = $_POST['beta_code'] ?? '';
    if (empty($code)) {
        \Utils\Show::msg("Un code bêta est requis pour s'inscrire.", 'error');
        Core::getInstance()->redirect(\Core\Router\Router::getInstance()->generate('register'));
    }

    // Validate in SQLite
    $dbPath = DATA . 'SessionReporter' . DS . 'debug.sqlite';
    if (!file_exists($dbPath)) {
        \Utils\Show::msg("Système d'inscription bêta indisponible.", 'error');
        Core::getInstance()->redirect(\Core\Router\Router::getInstance()->generate('home'));
    }

    $db = new \PDO("sqlite:" . $dbPath);
    $stmt = $db->prepare("SELECT * FROM beta_codes WHERE code = :code AND used_at IS NULL AND (expires_at > CURRENT_TIMESTAMP OR expires_at IS NULL)");
    $stmt->execute([':code' => $code]);
    $validCode = $stmt->fetch();

    if (!$validCode) {
        \Utils\Show::msg("Code bêta invalide, déjà utilisé ou expiré.", 'error');
        Core::getInstance()->redirect(\Core\Router\Router::getInstance()->generate('register'));
    }
}

/**
 * Hook: users:after-registration-success - Flag user and consume code.
 */
function SessionReporterOnRegistrationSuccess(array $params): void
{
    $user = $params['user'] ?? null;
    $code = $_POST['beta_code'] ?? '';
    
    if ($user && !empty($code)) {
        // Flag user as Beta
        $user->attributes['is_beta'] = true;
        $user->save();

        // Consume code in SQLite
        $dbPath = DATA . 'SessionReporter' . DS . 'debug.sqlite';
        $db = new \PDO("sqlite:" . $dbPath);
        $stmt = $db->prepare("UPDATE beta_codes SET used_by_user_id = :uid, used_at = CURRENT_TIMESTAMP WHERE code = :code");
        $stmt->execute([
            ':uid' => $user->getId(),
            ':code' => $code
        ]);
    }
}

/**
 * Hook: afterResponse - Capture performance markers.
 */
function SessionReporterAfterResponse(): void
{
    if (!SessionReporterIsActive()) return;

    // Use property_exists to avoid lint errors if private/missing, though we fixed it to public
    $startTime = \Core\Core::getInstance()->startTime ?? null;
    if ($startTime === null) return;

    $collector = CollectorService::getInstance();
    $executionTime = microtime(true) - $startTime;
    
    if ($executionTime > 2.5) {
        $collector->logEvent('slow_request', [
            'uri' => $_SERVER['REQUEST_URI'],
            'execution_time' => $executionTime,
            'memory_peak' => memory_get_peak_usage(true)
        ]);
    }
}

/**
 * Hook: notFound - Capture 404 occurrences.
 */
function SessionReporterNotFound(): void
{
    if (!SessionReporterIsActive()) return;

    $collector = CollectorService::getInstance();
    $collector->logEvent('404', [
        'uri' => $_SERVER['REQUEST_URI'],
        'referrer' => $_SERVER['HTTP_REFERER'] ?? 'none'
    ]);
}

/**
 * Hook: accessDenied - Capture 403 occurrences.
 */
function SessionReporterAccessDenied(): void
{
    if (!SessionReporterIsActive()) return;

    $collector = CollectorService::getInstance();
    $collector->logEvent('access_denied', [
        'uri' => $_SERVER['REQUEST_URI'],
        'user_id' => \Core\Auth\UsersManager::getCurrentUser() ? \Core\Auth\UsersManager::getCurrentUser()->getId() : 'guest'
    ]);
}
