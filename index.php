<?php

/**
 * @copyright (C) 2024, 299Ko, based on code (2010-2021) 99ko https://github.com/99kocms/
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Jonathan Coulet <j.coulet@gmail.com>
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * @author Frédéric Kaplon <frederic.kaplon@me.com>
 * @author Florent Fortat <florent.fortat@maxgun.fr>
 *
 * @package 299Ko https://github.com/299Ko/299ko
 */
ob_start();
const ROOT = __DIR__ . DIRECTORY_SEPARATOR;
const DS = DIRECTORY_SEPARATOR;

// Safeguard: Early exit for missing assets to avoid full CMS boot on 404s
// Each missing asset (CSS, JS, Image) would otherwise trigger ~20+ SQL queries
if (isset($_SERVER['REQUEST_URI']) 
    && preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|otf|map|webmanifest)$/i', $_SERVER['REQUEST_URI'])
    && strpos($_SERVER['REQUEST_URI'], '/pwa/') === false
    && strpos($_SERVER['REQUEST_URI'], '/plugin/') === false
    && strpos($_SERVER['REQUEST_URI'], '/theme/') === false
    && strpos($_SERVER['REQUEST_URI'], '/admin') === false
    && strpos($_SERVER['REQUEST_URI'], '/common') === false
) {
    header("HTTP/1.0 404 Not Found");
    header("Content-Type: text/plain");
    echo "Asset not found (Safeguard)";
    exit;
}

// Safeguard 1: Early exit for vulnerability probes & sensitive scanners
// Cuts off scrapers/bots targeting .env, .git, phpinfo, etc. in 0.1ms without touching MySQL/CMS
$reqUri = strtolower($_SERVER['REQUEST_URI'] ?? '');
if (preg_match('/\.(env|git|aws|sql|bak|backup|old|save|swp|temp|tmp|yml|yaml|ini|conf|config|cfg|log|tar|tar\.gz|tgz|zip|rar|7z|sh|bash|key|pem|crt|cer|pfx|p12|id_rsa)(\?|$)/i', $reqUri)
    || preg_match('/(wp-config|phpinfo|_phpinfo|pi\.php|info\.php|test\.php|web\.xml|settings\.py|\.aws\/|bedrock|phpmyadmin|adminer|eval-stdin|invokefunction|database\.sql|\.git\/|\.pypirc)/i', $reqUri)) {
    header("HTTP/1.1 404 Not Found");
    header("Content-Type: text/plain; charset=utf-8");
    echo "Not Found";
    exit;
}

// Safeguard 2: Anti-Burst / Aggressive Scraper Shield
// Throttles automated bots sending abnormal bursts (>12 req in 3s or >45 req/min without valid session)
$clientIp = $_SERVER['HTTP_CF_CONNECTING_IP'] 
    ?? $_SERVER['HTTP_X_FORWARDED_FOR'] 
    ?? $_SERVER['REMOTE_ADDR'] 
    ?? '';
if (strpos($clientIp, ',') !== false) {
    $clientIp = trim(explode(',', $clientIp)[0]);
}

if ($clientIp !== '' && !in_array($clientIp, ['127.0.0.1', '::1', '192.168.1.178'], true)) {
    // Immediate block for confirmed malicious / datacenter scraper ranges identified in security analysis
    if (strpos($clientIp, '213.209.159.') === 0 || strpos($clientIp, '2001:41d0:303:') === 0) {
        header("HTTP/1.1 404 Not Found");
        header("Content-Type: text/plain; charset=utf-8");
        echo "Not Found";
        exit;
    }

    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $isCrawler = (bool)preg_match('/(googlebot|bingbot|google-inspectiontool|applebot|yandexbot)/i', $ua);
    $hasSession = isset($_COOKIE['vb_sid']) || isset($_COOKIE['PHPSESSID']);
    
    if (!$isCrawler && !$hasSession) {
        $rateDir = is_dir('/dev/shm') && is_writable('/dev/shm') ? '/dev/shm/vb_ratelimit' : sys_get_temp_dir() . '/vb_ratelimit';
        if (!is_dir($rateDir)) {
            @mkdir($rateDir, 0777, true);
        }
        $ipHash = md5($clientIp);
        $rateFile = $rateDir . '/' . $ipHash . '.json';
        $now = microtime(true);
        $data = null;
        if (file_exists($rateFile)) {
            $data = json_decode(@file_get_contents($rateFile), true);
        }
        if (!$data || !is_array($data) || ($now - ($data['t'] ?? 0) > 60)) {
            $data = ['t' => $now, 'c' => 1, 'burst' => 1, 'bt' => $now];
        } else {
            $data['c']++;
            if ($now - ($data['bt'] ?? 0) <= 3) {
                $data['burst']++;
            } else {
                $data['burst'] = 1;
                $data['bt'] = $now;
            }
        }
        @file_put_contents($rateFile, json_encode($data), LOCK_EX);

        if (($data['burst'] ?? 0) > 12 || ($data['c'] ?? 0) > 45) {
            header("HTTP/1.1 404 Not Found");
            header("Content-Type: text/plain; charset=utf-8");
            echo "Not Found";
            exit;
        }
    }
}

include_once(ROOT . 'common' . DS . 'common.php');

if (!$core->isInstalled()) {
    header('location: install.php');
    die();
}

// Vérifier si les données sont en legacy (pas de versionCMS dans config.json)
$configFile = DATA . 'config.json';
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
    if (!isset($config['versionCMS'])) {
        // Les données sont en legacy, lancer la migration
        $migrationScript = ROOT . 'migrate_legacy_data.php';
        if (file_exists($migrationScript)) {
            include_once($migrationScript);
        }
        // Après la migration (ou si le script n'existe pas), vérifier et ajouter versionCMS si nécessaire
        $config = json_decode(file_get_contents($configFile), true);
        if (!isset($config['versionCMS'])) {
            $config['versionCMS'] = VERSION;
            file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }
}

$__currentUser = \Core\Auth\UsersManager::getCurrentUser();
define('IS_LOGGED', $__currentUser !== null);
// Check if user has admin permission via declarative policy engine
$isAdmin = false;
$isPro = false;
if (IS_LOGGED) {
    $__evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
    $__ctx = new \Core\Navigation\ConditionContext($__currentUser);
    $isAdmin = $__evaluator->isAdmin($__ctx);
    
    $authCurrentUser = \Core\Auth\CurrentUser::get();
    $isPro = ($authCurrentUser && $authCurrentUser->hasClaims()) || $isAdmin;
}
define('IS_ADMIN', $isAdmin);
define('IS_PRO', $isPro);

\Template\Template::addGlobal('IS_LOGGED', IS_LOGGED);
\Template\Template::addGlobal('IS_ADMIN', IS_ADMIN);
\Template\Template::addGlobal('IS_PRO', IS_PRO);
\Template\Template::addGlobal('REGISTRATION_ENABLED', $core->getConfigVal('allowRegistrations'));
\Template\Template::addGlobal('REGISTRATION_DEFAULT_GROUP', $core->getConfigVal('registrationDefaultGroup'));
\Template\Template::addGlobal('IS_HOMEPAGE', false);

$core->executeCallback($router, $runPlugin);