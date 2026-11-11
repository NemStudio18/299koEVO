<?php
defined('ROOT') OR exit('No direct script access allowed');

/**
 * Génère une paire de clés VAPID pour Web Push
 * Utilise OpenSSL si disponible, sinon retourne false
 * @return array|false ['publicKey' => string, 'privateKey' => string] ou false
 */
/**function pwaGenerateVapidKeys() {
    if (!function_exists('openssl_pkey_new')) {
        \Core\Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_ERROR, "PWA: openssl_pkey_new function not available");
        return false;
    }

    // Vérifier que la courbe prime256v1 est disponible
    $availableCurves = openssl_get_curve_names();
    if (!in_array('prime256v1', $availableCurves)) {
        \Core\Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_ERROR, "PWA: prime256v1 curve not available. Available curves: " . implode(', ', $availableCurves));
        return false;
    }

    // Sur Windows, OpenSSL peut nécessiter un fichier de configuration
    // On peut créer une configuration minimale en mémoire ou désactiver la vérification
    $config = [
        'digest_alg' => 'sha256',
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_EC,
        'curve_name' => 'prime256v1',
        'config' => '' // Configuration vide pour éviter les erreurs de fichier manquant
    ];

    // Sur Windows, essayer de désactiver la vérification du fichier de configuration
    // en définissant OPENSSL_CONF à une valeur vide ou inexistante
    $oldOpenSslConf = getenv('OPENSSL_CONF');
    if (PHP_OS_FAMILY === 'Windows') {
        // Créer un fichier de configuration temporaire minimal
        $tempConfigFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'openssl_pwa_' . uniqid() . '.cnf';
        $configContent = "[req]\n";
        $configContent .= "distinguished_name = req_distinguished_name\n";
        $configContent .= "[req_distinguished_name]\n";
        file_put_contents($tempConfigFile, $configContent);
        $config['config'] = $tempConfigFile;
        putenv('OPENSSL_CONF=' . $tempConfigFile);
    }

    // Supprimer les erreurs OpenSSL précédentes
    while (openssl_error_string() !== false) {}

    $key = @openssl_pkey_new($config);
    if (!$key) {
        $error = openssl_error_string();
        \Core\Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_ERROR, "PWA: openssl_pkey_new failed: " . ($error ?: 'Unknown error'));
        // Restaurer OPENSSL_CONF
        if (PHP_OS_FAMILY === 'Windows' && $oldOpenSslConf !== false) {
            putenv('OPENSSL_CONF=' . $oldOpenSslConf);
        } elseif (PHP_OS_FAMILY === 'Windows') {
            putenv('OPENSSL_CONF');
        }
        if (isset($tempConfigFile) && file_exists($tempConfigFile)) {
            @unlink($tempConfigFile);
        }
        return false;
    }

    $details = @openssl_pkey_get_details($key);
    if (!$details) {
        $error = openssl_error_string();
        \Core\Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_ERROR, "PWA: openssl_pkey_get_details failed: " . ($error ?: 'Unknown error'));
        // Restaurer OPENSSL_CONF
        if (PHP_OS_FAMILY === 'Windows' && $oldOpenSslConf !== false) {
            putenv('OPENSSL_CONF=' . $oldOpenSslConf);
        } elseif (PHP_OS_FAMILY === 'Windows') {
            putenv('OPENSSL_CONF');
        }
        if (isset($tempConfigFile) && file_exists($tempConfigFile)) {
            @unlink($tempConfigFile);
        }
        return false;
    }

    if (!isset($details['ec'])) {
        \Core\Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_ERROR, "PWA: Key details do not contain 'ec' field. Available fields: " . implode(', ', array_keys($details)));
        // Restaurer OPENSSL_CONF
        if (PHP_OS_FAMILY === 'Windows' && $oldOpenSslConf !== false) {
            putenv('OPENSSL_CONF=' . $oldOpenSslConf);
        } elseif (PHP_OS_FAMILY === 'Windows') {
            putenv('OPENSSL_CONF');
        }
        if (isset($tempConfigFile) && file_exists($tempConfigFile)) {
            @unlink($tempConfigFile);
        }
        return false;
    }

    $ec = $details['ec'];

    // Vérifier que les champs nécessaires existent
    if (!isset($ec['x']) || !isset($ec['y']) || !isset($ec['d'])) {
        \Core\Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_ERROR, "PWA: EC key missing required fields. Available: " . implode(', ', array_keys($ec)));
        // Restaurer OPENSSL_CONF
        if (PHP_OS_FAMILY === 'Windows' && $oldOpenSslConf !== false) {
            putenv('OPENSSL_CONF=' . $oldOpenSslConf);
        } elseif (PHP_OS_FAMILY === 'Windows') {
            putenv('OPENSSL_CONF');
        }
        if (isset($tempConfigFile) && file_exists($tempConfigFile)) {
            @unlink($tempConfigFile);
        }
        return false;
    }

    // Convertir en format base64url pour VAPID
    $publicKeyRaw = "\x04" . $ec['x'] . $ec['y']; // Format uncompressed point
    $privateKeyRaw = $ec['d'];
    $publicKey = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($publicKeyRaw));
    $privateKey = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($privateKeyRaw));

    // Restaurer OPENSSL_CONF
    if (PHP_OS_FAMILY === 'Windows' && $oldOpenSslConf !== false) {
        putenv('OPENSSL_CONF=' . $oldOpenSslConf);
    } elseif (PHP_OS_FAMILY === 'Windows') {
        putenv('OPENSSL_CONF');
    }
    if (isset($tempConfigFile) && file_exists($tempConfigFile)) {
        @unlink($tempConfigFile);
    }

    \Core\Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_INFO, "PWA: VAPID keys generated successfully. Public key length: " . strlen($publicKey) . ", Private key length: " . strlen($privateKey));

    return [
        'publicKey' => $publicKey,
        'privateKey' => $privateKey
    ];
}
*/

function pwaGenerateVapidKeys()
{
    $logger = \Core\Core::getInstance()->getLogger();
    if (!extension_loaded('openssl')) {
        $logger->log(\Core\Logger::LEVEL_ERROR, "PWA: OpenSSL manquant");
        return false;
    }

    try {
        $key = @openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1'
        ]);

        if (!$key)
            return false;

        $details = @openssl_pkey_get_details($key);
        if (!$details || !isset($details['ec']))
            return false;

        $ec = $details['ec'];
        if (!isset($ec['x']) || !isset($ec['y']) || !isset($ec['d']))
            return false;

        $publicKey = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode("\x04" . $ec['x'] . $ec['y']));
        $privateKey = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($ec['d']));

        return ['publicKey' => $publicKey, 'privateKey' => $privateKey];
    } catch (\Exception $e) {
        return false;
    }
}



/**
 * Récupère le chemin du fichier des clés VAPID
 * @return string
 */
function pwaGetVapidKeysFile()
{
    return DATA_PLUGIN . 'pwa/vapid_keys.json';
}

/**
 * Lit les clés VAPID depuis le fichier séparé
 * @return array|false ['publicKey' => string, 'privateKey' => string] ou false
 */
function pwaGetVapidKeys()
{
    $file = pwaGetVapidKeysFile();
    if (!file_exists($file)) {
        return false;
    }
    $data = @json_decode(file_get_contents($file), true);
    if (!is_array($data) || empty($data['publicKey']) || empty($data['privateKey'])) {
        return false;
    }
    return $data;
}

/**
 * Sauvegarde les clés VAPID dans un fichier séparé
 * @param string $publicKey
 * @param string $privateKey
 * @return bool
 */
function pwaSaveVapidKeys($publicKey, $privateKey)
{
    $dir = DATA_PLUGIN . 'pwa/';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $file = pwaGetVapidKeysFile();
    $data = [
        'publicKey' => $publicKey,
        'privateKey' => $privateKey,
        'createdAt' => date('c')
    ];
    return @file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

function pwaInstall()
{
    $dir = DATA_PLUGIN . 'pwa/';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (!file_exists($dir . 'subscriptions.json')) {
        @file_put_contents($dir . 'subscriptions.json', json_encode([]));
    }

    // Migrer les clés VAPID depuis la config du plugin vers le fichier séparé (si elles existent)
    $pluginsManager = \Core\Plugin\PluginsManager::getInstance();
    $plugin = $pluginsManager->getPlugin('pwa');
    if ($plugin) {
        $oldPublicKey = $plugin->getConfigVal('publicKey');
        $oldPrivateKey = $plugin->getConfigVal('privateKey');
        if (!empty($oldPublicKey) && !empty($oldPrivateKey)) {
            // Migrer vers le nouveau fichier
            if (pwaSaveVapidKeys($oldPublicKey, $oldPrivateKey)) {
                // Supprimer les clés de la config du plugin
                $plugin->setConfigVal('publicKey', '');
                $plugin->setConfigVal('privateKey', '');
                $pluginsManager->savePluginConfig($plugin);
                $logger = \Core\Core::getInstance()->getLogger();
                $logger->log(\Core\Logger::LEVEL_INFO, "PWA: VAPID keys migrated from plugin config to separate file");
            }
        }
    }

    // Ne pas générer les clés automatiquement - l'utilisateur doit le faire via l'interface admin
    $logger = \Core\Core::getInstance()->getLogger();
    $logger->log(\Core\Logger::LEVEL_INFO, "PWA: Plugin installed. VAPID keys can be generated from the admin interface.");
}


function pwaBeforeRunPlugin()
{
    if (class_exists('\Template\Template')) {
        \Template\Template::addGlobal('pwaEnabled', true);
    }
}

## Hooks

function pwaEndFrontHead($param = ''): string
{
    $pluginsManager = \Core\Plugin\PluginsManager::getInstance();
    if (!$pluginsManager->isActivePlugin('pwa')) {
        return (string)$param;
    }

    $router = \Core\Router\Router::getInstance();
    $core = \Core\Core::getInstance();

    $output = (string)$param;
    // Lien vers le manifest via la route PHP pour éviter les erreurs 403 sur les fichiers statiques
    $output .= '<link rel="manifest" href="' . $router->generate('pwa-manifest') . '">' . "\n";

    // Meta tags pour PWA
    $themeColor = $core->getConfigVal('themeColor') ?: '#000000';
    $output .= '<meta name="theme-color" content="' . htmlspecialchars($themeColor) . '">' . "\n";
    $output .= '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
    $output .= '<meta name="mobile-web-app-capable" content="yes">' . "\n";
    $output .= '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n";

    // CSS PWA pour le public
    $pwaCssUrl = \Utils\Util::urlBuild(PLUGINS . 'pwa/assets/css/pwa.css');
    $output .= '<link rel="stylesheet" href="' . htmlspecialchars($pwaCssUrl) . '">' . "\n";

    return $output;
}

function pwaEndAdminHead($param = ''): string
{
    $pluginsManager = \Core\Plugin\PluginsManager::getInstance();
    if (!$pluginsManager->isActivePlugin('pwa')) {
        return (string)$param;
    }

    $output = (string)$param;
    // Script PWA pour l'admin aussi (pour tester les notifications)
    $pwaJsUrl = \Utils\Util::urlBuild(PLUGINS . 'pwa/pwa.js');
    $pwaCssUrl = \Utils\Util::urlBuild(PLUGINS . 'pwa/assets/css/pwa.css');
    $output .= '<script src="' . htmlspecialchars($pwaJsUrl) . '"></script>' . "\n";
    $output .= '<link rel="stylesheet" href="' . htmlspecialchars($pwaCssUrl) . '">' . "\n";
    return $output;
}

function pwaEndFrontBody()
{
    $pluginsManager = \Core\Plugin\PluginsManager::getInstance();
    if (!$pluginsManager->isActivePlugin('pwa')) {
        return;
    }

    // S'assurer que les traductions du plugin sont chargées côté public
    \Core\Lang::loadLanguageFile(PLUGINS . 'pwa' . DS . 'langs' . DS);
    $installLabel = \Core\Lang::get('pwa.install-app');
    if ($installLabel === 'pwa.install-app') {
        $installLabel = 'Installer l\'application';
    }

    // Afficher le bouton d'installation PWA (similaire à newsletter)
    $templateFile = PLUGINS . 'pwa/template/install-button.tpl';
    if (!file_exists($templateFile)) {
        $logger = \Core\Core::getInstance()->getLogger();
        $logger->log(\Core\Logger::LEVEL_ERROR, "PWA: Template file not found: $templateFile");
        return;
    }

    try {
        $tpl = new \Template\Template($templateFile);
        $tpl->set('installLabel', $installLabel);
        echo $tpl->output();
    } catch (\Exception $e) {
        \Core\Core::getInstance()->getLogger()->log(\Core\Logger::LEVEL_ERROR, "PWA: Error rendering template: " . $e->getMessage());
    }

    // Charger le script PWA
    $pwaJsUrl = \Utils\Util::urlBuild(PLUGINS . 'pwa/pwa.js');
    echo '<script src="' . htmlspecialchars($pwaJsUrl) . '"></script>';
}



