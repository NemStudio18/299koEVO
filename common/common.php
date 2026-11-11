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
// Configuration de la durée de vie de la session AVANT session_start()
// La valeur est lue depuis la configuration du CMS (configmanager)
// Par défaut, PHP utilise session.gc_maxlifetime (souvent 1440 secondes = 24 minutes)
// Note: Pour une persistance encore plus longue, utiliser "Se souvenir de moi" (60 jours)

// Charger la configuration pour obtenir la durée de session
// Utiliser ROOT et DS directement car DATA n'est pas encore défini à ce stade
$sessionLifetime = 604800; // Valeur par défaut : 7 jours (604800 secondes)

if (defined('ROOT') && defined('DS')) {
    $configFile = ROOT . 'data' . DS . 'config.json';
    if (file_exists($configFile)) {
        $configData = json_decode(file_get_contents($configFile), true);
        if (is_array($configData) && isset($configData['sessionLifetime']) && is_numeric($configData['sessionLifetime'])) {
            $sessionLifetime = (int)$configData['sessionLifetime'];
            // S'assurer que la valeur est raisonnable (minimum 60 secondes, maximum 30 jours)
            $sessionLifetime = max(60, min(2592000, $sessionLifetime));
        }
    }
}





ini_set('session.gc_maxlifetime', $sessionLifetime);
ini_set('session.cookie_lifetime', $sessionLifetime);

// Détection robuste du HTTPS (gère les proxys comme Cloudflare ou Nginx)
$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
            || ($_SERVER['SERVER_PORT'] == 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

// Force secure false si on est sur localhost ou IP locale pour éviter les blocages de navigateurs
$isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '.base') !== false;
if ($isLocal) {
    $isSecure = false;
}

// Harden Session Cookies
session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path' => '/',
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => 'Lax'
]);

include_once(ROOT . 'common/config.php');

// Définir un chemin local pour les sessions afin d'éviter le garbage collection trop agressif de certains hébergeurs
$sessionPath = DATA . 'cache' . DS . 'sessions';
if (!is_dir($sessionPath)) {
    @mkdir($sessionPath, 0755, true);
}
session_save_path($sessionPath);

// Désactiver les headers anti-cache automatiques de PHP (Expires 1981, Cache-Control: no-store)
// Cela permet au bfcache (Back/Forward Cache) du navigateur de fonctionner sur les pages publiques
session_cache_limiter('');
session_start();
defined('ROOT') or exit('Access denied!');

define('BASE_PATH', rtrim(dirname($_SERVER['SCRIPT_NAME']), DIRECTORY_SEPARATOR));

// Autoloader PSR-4
require_once COMMON . 'Core/Autoloader/HybridAutoloader.php';

$autoloaderCacheFile = DATA . 'cache' . DS . 'autoloader_cache.php';
$debugMode = (file_exists(DATA . 'config.json')) 
    ? (json_decode(file_get_contents(DATA . 'config.json'), true)['debug'] ?? false)
    : false;

$autoloader = new \Core\Autoloader\HybridAutoloader(
    [
        COMMON,                    // Core classes
        PLUGINS                    // Plugin directory
    ],
    $autoloaderCacheFile,      // Cache file
    $debugMode,                // Debug mode
    true                       // Authoritative mode (true = cache activé avec validation automatique)
);

\Core\Security\Csrf::start();

// Inject Security Headers
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; script-src 'self' 'unsafe-inline' https:; style-src 'self' 'unsafe-inline' https:;");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");

$router = \Core\Router\Router::getInstance();
$core = \Core\Core::getInstance();
$core->init();

$pluginsManager = \Core\Plugin\PluginsManager::getInstance();
foreach ($pluginsManager->getPlugins() as $plugin) {
	if ($plugin->getConfigVal('activate')) {
		$plugin->loadLangFile();
		$plugin->loadRoutes();
		// Charger le fichier PHP du plugin AVANT d'enregistrer les hooks pour que les fonctions soient définies
		if ($plugin->getLibFile() !== false) {
			$libFile = $plugin->getLibFile();
			$core->getLogger()->log(\Core\Logger::LEVEL_DEBUG, "Chargement fichier plugin '{$plugin->getName()}': {$libFile} - existe: " . (file_exists($libFile) ? 'OUI' : 'NON') . ", ROOT défini: " . (defined('ROOT') ? 'OUI' : 'NON'));
			if (file_exists($libFile)) {
				// S'assurer que ROOT est défini avant de charger le fichier (car search.php vérifie ROOT)
				if (!defined('ROOT')) {
					define('ROOT', '.' . DIRECTORY_SEPARATOR);
				}
				$result = @include_once($libFile);
				if ($result === false) {
					$core->getLogger()->log(\Core\Logger::LEVEL_ERROR, "Erreur lors du chargement du fichier plugin '{$plugin->getName()}': {$libFile}");
				} else {
					// Vérifier que les fonctions/classes sont bien définies après le chargement
					if ($plugin->getName() === 'searchEVO') {
						$core->getLogger()->log(\Core\Logger::LEVEL_DEBUG, "searchEVO: Fichier chargé - searchBefore404 existe: " . (function_exists('searchBefore404') ? 'OUI' : 'NON') . ", searchEndFrontBody existe: " . (function_exists('searchEndFrontBody') ? 'OUI' : 'NON'));
					}
					// Pour lightstats, vérifier que la classe Hooks est chargée
					if ($plugin->getName() === 'lightstats') {
						$core->getLogger()->log(\Core\Logger::LEVEL_DEBUG, "lightstats: Fichier chargé - LightStats\\Hooks existe: " . (class_exists('\\LightStats\\Hooks') ? 'OUI' : 'NON') . ", addScript existe: " . (class_exists('\\LightStats\\Hooks') && method_exists('\\LightStats\\Hooks', 'addScript') ? 'OUI' : 'NON'));
					}
				}
			} else {
				$core->getLogger()->log(\Core\Logger::LEVEL_ERROR, "Fichier plugin '{$plugin->getName()}' introuvable: {$libFile}");
			}
		} else {
			if ($plugin->getName() === 'searchEVO') {
				$core->getLogger()->log(\Core\Logger::LEVEL_WARNING, "searchEVO: getLibFile() retourne false");
			}
		}
		// Enregistrer les hooks après avoir chargé le fichier PHP
		foreach ($plugin->getHooks() as $name => $function) {
			// Vérifier si c'est une fonction globale ou une méthode de classe statique
			$isCallable = false;
			if (is_string($function)) {
				// Si c'est une méthode de classe (contient ::)
				if (strpos($function, '::') !== false) {
					list($className, $methodName) = explode('::', $function, 2);
					// Normaliser le nom de classe (ajouter \ au début si nécessaire pour le namespace global)
					$normalizedClassName = $className;
					if (strpos($className, '\\') !== 0) {
						$normalizedClassName = '\\' . $className;
					}
					// Vérifier si la classe existe et si la méthode est callable
					$isCallable = (class_exists($className) || class_exists($normalizedClassName)) && method_exists($className, $methodName);
					if (!$isCallable) {
						$classExists = class_exists($className) || class_exists($normalizedClassName);
						$methodExists = $classExists && method_exists($className, $methodName);
						$core->getLogger()->log(\Core\Logger::LEVEL_WARNING, "Hook '{$name}' pour plugin '{$plugin->getName()}': méthode de classe '{$function}' non disponible (classe existe: " . ($classExists ? 'OUI' : 'NON') . ", méthode existe: " . ($methodExists ? 'OUI' : 'NON') . ")");
					}
				} else {
					// Fonction globale
					$isCallable = function_exists($function);
					if (!$isCallable) {
						$core->getLogger()->log(\Core\Logger::LEVEL_WARNING, "Hook '{$name}' pour plugin '{$plugin->getName()}': fonction '{$function}' non définie (sera peut-être définie plus tard)");
					}
				}
			} else {
				$isCallable = is_callable($function);
			}
			
			// Enregistrer quand même, la fonction/méthode sera peut-être définie plus tard (dans beforeRunPlugin)
			$core->addHook($name, $function);
		}
	}
}

// Initialiser la navigation maintenant que tous les plugins (et leurs hooks) sont chargés
\Core\Page\Page::addToNavigation();

// Initialiser le contexte utilisateur global (Phase 2 de l'initialisation du singleton)
// Cela permet de collecter les données métiers (claims, abonnements) via hooks
\Core\Auth\CurrentUser::get()->initialize();


\Core\Lang::loadLanguageFile(THEMES . $core->getConfigVal('theme') . '/langs/');
$themeFunctionsFile = THEMES . $core->getConfigVal('theme') . '/functions.php';
if (file_exists($themeFunctionsFile)) {
	include_once($themeFunctionsFile);
}

## $runPLugin représente le plugin en cours d'execution et s'utilise avec la classe plugin & pluginsManager
## Pour les modules core (page, users, etc.), $runPlugin est null car ils ne sont plus des plugins
$pluginToCall = $core->getPluginToCall();
if ($core->isCoreModule($pluginToCall)) {
    $runPlugin = null;
} else {
    $currentPlugin = $pluginsManager->getPlugin($pluginToCall);
    $runPlugin = ($currentPlugin !== false) ? $currentPlugin : null;
}

// Inclure le functions.php du thème si il existe
$themeFunctions = THEMES . $core->getConfigVal('theme') . '/functions.php';
if (file_exists($themeFunctions)) {
    require_once $themeFunctions;
}

\Template\Template::addGlobal('COMMON', COMMON);
\Template\Template::addGlobal('DATA', DATA);
\Template\Template::addGlobal('UPLOAD', UPLOAD);
\Template\Template::addGlobal('DATA_PLUGIN', DATA_PLUGIN);
\Template\Template::addGlobal('THEMES', THEMES);
\Template\Template::addGlobal('PLUGINS', PLUGINS);
\Template\Template::addGlobal('THEME_PATH', THEMES . $core->getConfigVal('theme') . '/');
\Template\Template::addGlobal('SITE_URL', rtrim($core->getConfigVal('siteUrl'), '/'));
\Template\Template::addGlobal('THEME_URL', \Utils\Util::urlBuild(THEMES . $core->getConfigVal('theme')));
\Template\Template::addGlobal('ADMIN_URL', rtrim($router->generate('admin'), '/') . '/');
\Template\Template::addGlobal('VERSION', VERSION);
\Template\Template::addGlobal('runPlugin', $runPlugin);
\Template\Template::addGlobal('ROUTER', $router);
\Template\Template::addGlobal('pluginsManager', $pluginsManager);
\Template\Template::addGlobal('CORE', $core);
\Template\Template::addGlobal('ADMIN_PATH', ADMIN_PATH);
\Template\Template::addGlobal('SHOW', \Utils\Show::class);
\Template\Template::addGlobal('show', \Utils\Show::class);
\Template\Template::addGlobal('Lang', \Core\Lang::class);
\Template\Template::addGlobal('lang', \Core\Lang::class);
\Template\Template::addGlobal('Util', \Utils\Util::class);
\Template\Template::addGlobal('util', \Utils\Util::class);
\Template\Template::addGlobal('USER', \Core\Auth\CurrentUser::get());
\Template\Template::addGlobal('user', \Core\Auth\CurrentUser::get());
\Template\Template::addGlobal('_csrfToken', \Core\Security\Csrf::token());
\Template\Template::addGlobal('allowRegistrations', (bool)$core->getConfigVal('allowRegistrations'));

/**
 * Function to display the button to manage files by Ajax
 * Now uses core MediaService instead of plugin
 */
function filemanagerDisplayManagerButton($textareaId = false, $buttonLabel = false):string {
    return \Core\Core::getInstance()->media()->renderManagerButton($textareaId, $buttonLabel);
}
