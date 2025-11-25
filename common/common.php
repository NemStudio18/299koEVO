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
session_start();
defined('ROOT') or exit('Access denied!');

define('BASE_PATH', rtrim(dirname($_SERVER['SCRIPT_NAME']), DIRECTORY_SEPARATOR));

include_once(ROOT . 'common/config.php');

// Autoloader PSR-4
require_once COMMON . 'Core/Autoloader/HybridAutoloader.php';

$autoloaderCacheFile = DATA . 'cache' . DS . 'autoloader_cache.php';
$debugMode = (file_exists(DATA . 'config.json')) 
    ? (json_decode(file_get_contents(DATA . 'config.json'), true)['debug'] ?? false)
    : false;

$autoloader = new \Core\Autoloader\HybridAutoloader(
    COMMON,                    // Base directory
    $autoloaderCacheFile,      // Cache file
    $debugMode,                // Debug mode
    true                       // Authoritative mode (true = cache activé avec validation automatique)
);

\Core\Security\Csrf::start();

$router = \Core\Router\Router::getInstance();
$core = \Core\Core::getInstance();
$core->init();

$pluginsManager = \Core\Plugin\PluginsManager::getInstance();
foreach ($pluginsManager->getPlugins() as $plugin) {
	if ($plugin->getConfigVal('activate')) {
		$plugin->loadLangFile();
		$plugin->loadRoutes();
		if ($plugin->getLibFile() !== false) {
			include_once($plugin->getLibFile());
		}
		foreach ($plugin->getHooks() as $name => $function) {
			$core->addHook($name, $function);
		}
	}
}

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

\Template\Template::addGlobal('COMMON', COMMON);
\Template\Template::addGlobal('DATA', DATA);
\Template\Template::addGlobal('UPLOAD', UPLOAD);
\Template\Template::addGlobal('DATA_PLUGIN', DATA_PLUGIN);
\Template\Template::addGlobal('THEMES', THEMES);
\Template\Template::addGlobal('PLUGINS', PLUGINS);
\Template\Template::addGlobal('THEME_PATH', THEMES . $core->getConfigVal('theme') . '/');
\Template\Template::addGlobal('SITE_URL', $core->getConfigVal('siteUrl'));
\Template\Template::addGlobal('THEME_URL', \Utils\Util::urlBuild(THEMES . $core->getConfigVal('theme')));
\Template\Template::addGlobal('ADMIN_URL', $router->generate('admin'));
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
\Template\Template::addGlobal('_csrfToken', \Core\Security\Csrf::token());

/**
 * Function to display the button to manage files by Ajax
 * Now uses core MediaService instead of plugin
 */
function filemanagerDisplayManagerButton($textareaId = false, $buttonLabel = false):string {
    return \Core\Core::getInstance()->media()->renderManagerButton($textareaId, $buttonLabel);
}
