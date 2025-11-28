<?php

namespace Core;

use Core\Auth\AuthService;
use Core\Page\Page;
use Core\Media\MediaService;
use Core\Settings\SettingsService;
use Core\Extensions\ExtensionsService;
use Core\Environment\Env;
use Core\Logger;
use Utils\Util;
use Core\Router\Router;
use Core\Lang;
use Core\Responses\PublicResponse;
use Core\Responses\Response;
use Core\Storage\Cache;
use Core\Storage\CacheManager;
use Content\Minifyer;
use Core\Plugin\Plugin;
use Core\Auth\UsersManager;

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
defined('ROOT') or exit('Access denied!');

class Core
{

    private static $instance = null;
    private $config;
    private $hooks;
    private $themes;
    private $pluginToCall;
    private $js;
    private $css;
    private $locale;
    private ?AuthService $authService = null;
    private ?Page $pageService = null;
    private ?MediaService $mediaService = null;
    private ?SettingsService $settingsService = null;
    private ?ExtensionsService $extensionsService = null;

    private ?Env $env;

    /**
     * Metas are used by plugins to display metas property or other in <head> HTML
     */
    private $metas = [];

    /**
     * Centralized page metadata (for core modules and plugins)
     */
    private ?string $titleTag = null;
    private ?string $mainTitle = null;
    private ?string $metaDescription = null;

    /**
     * Centralized navigation for core modules
     */
    private array $coreNavigation = [];

    /**
     * Logger instance
     * @var \Logger
     */
    private $logger;


    private float $startTime;

    private int $queryCount = 0;
    ## Constructeur

    public function __construct() {
        if (!is_dir(DATA)) {
            @mkdir(DATA);
        }
        if (!is_dir(DATA_CORE)) {
            @mkdir(DATA_CORE);
        }
        if (!is_dir(DATA_CORE_AUTH)) {
            @mkdir(DATA_CORE_AUTH, 0755, true);
        }
        if (!is_dir(DATA_CORE_PAGE)) {
            @mkdir(DATA_CORE_PAGE, 0755, true);
        }
        if (!is_dir(DATA_CORE_MEDIA)) {
            @mkdir(DATA_CORE_MEDIA, 0755, true);
        }
        if (!is_dir(DATA_CORE_SETTINGS)) {
            @mkdir(DATA_CORE_SETTINGS, 0755, true);
        }
        if (!is_dir(DATA_CORE_EXTENSIONS)) {
            @mkdir(DATA_CORE_EXTENSIONS, 0755, true);
        }
        if (!is_dir(UPLOAD . 'files')) {
            @mkdir(UPLOAD . 'files', 0755, true);
        }
    }

    public function init() {
        $this->config = Util::readJsonFile(DATA . 'config.json', true);
        $this->env = new Env(ROOT . '.env');
        $this->loadEnv();
        $this->createLogger();

        // Timezone
        date_default_timezone_set(date_default_timezone_get());
        // Réglage de l'error reporting suivant le paramètre debug
        if ($this->config && $this->config['debug']) {
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
            $this->startTime = microtime(true);
        } else
            error_reporting(E_ERROR | E_PARSE);
        // Liste des thèmes
        $temp = Util::scanDir(THEMES);
        foreach ($temp['dir'] as $k => $v) {
            $this->themes[$v] = Util::readJsonFile(THEMES . $v . '/infos.json', true);
        }
        // On détermine le plugin que l'on doit executer suivant le mode (public ou admin)

        $parts = explode('/', trim(Router::getInstance()->getCleanURI(), '/'));
        if ($parts[0] === 'index.php') {
            array_shift($parts);
        }
        if (!isset($parts[0])) {
            $parts[0] = '';
        }
        if ($parts[0] === '') {
            $defaultPlugin = $this->getConfigVal('defaultPlugin');
            // Si defaultPlugin est null, false ou vide, utiliser 'page' comme module core par défaut
            if ($defaultPlugin === null || $defaultPlugin === false || $defaultPlugin === '') {
                $this->pluginToCall = 'page';
            } else {
                $this->pluginToCall = $defaultPlugin;
            }
            define('ISHOMEPAGE', true);
        } else {
            if ($parts[0] === 'admin') {
                if (isset($parts[1]) && $parts[1] !== '') {
                    $this->pluginToCall = $parts[1];
                    define('ISHOMEPAGE', false);
                } else {
                    $defaultAdminPlugin = $this->getConfigVal('defaultAdminPlugin');
                    // Si defaultAdminPlugin est null, false ou vide, utiliser 'configmanager' comme module admin par défaut
                    if ($defaultAdminPlugin === null || $defaultAdminPlugin === false || $defaultAdminPlugin === '') {
                        $this->pluginToCall = 'configmanager';
                    } else {
                        $this->pluginToCall = $defaultAdminPlugin;
                    }
                    define('ISHOMEPAGE', true);
                }
            } else {
                $this->pluginToCall = $parts[0];
                define('ISHOMEPAGE', false);
            }
        }

        $this->locale = $this->determineLocale();
        Lang::setLocale($this->locale);
        Lang::loadLanguageFile(COMMON . 'langs/');
        Lang::loadLanguageFile(COMMON . 'langs/pluginsmanager/');
        Lang::loadLanguageFile(COMMON . 'langs/marketplace/');
        Lang::loadLanguageFile(COMMON . 'langs/configmanager/');
        Lang::loadLanguageFile(COMMON . 'langs/filemanager/');
        Lang::loadLanguageFile(COMMON . 'langs/page/');
        Lang::loadLanguageFile(COMMON . 'langs/users/');
        $this->css[] = FONTICON;
        $this->css[] = FANCYCSS;
        $this->js[] = FANCYJS;
        $this->auth();
        $this->auth()->registerRoutes(Router::getInstance());
        $this->pages();
        $this->pages()->registerRoutes(Router::getInstance());
        require_once COMMON . 'Core/Page/hooks.php';
        $this->addHook('endFrontHead', '\Core\Page\pageEndFrontHead');
        \Core\Page\Page::addToNavigation();
        $this->media();
        $this->media()->registerRoutes(Router::getInstance());
        $this->settings();
        $this->settings()->registerRoutes(Router::getInstance());
        $this->extensions()->registerRoutes(Router::getInstance());
    }

    /**
     * Return Core Instance
     * 
     * @return \self
     */
    public static function getInstance() {
        if (is_null(self::$instance))
            self::$instance = new Core();
        return self::$instance;
    }

    ## Retourne la liste des thèmes

    public function getThemes() {
        return $this->themes;
    }

    ## Retourne la configuration complète

    public function getconfig() {
        return $this->config;
    }

    ## Retourne une valeur de configuration

    public function getConfigVal($k) {
        if (isset($this->config[$k]))
            return $this->config[$k];
        else
            return false;
    }

    /**
     * Set up a config val.
     * This setting will not be saved
     * 
     * @param string $key
     * @param string $value
     */
    public function setConfigVal($key, $value) {
        $this->config[$key] = $value;
    }

    ## Retourne les infos du thème ciblé

    public function getThemeInfo($k) {
        if (isset($this->themes[$this->getConfigVal('theme')]))
            return $this->themes[$this->getConfigVal('theme')][$k];
        else
            return false;
    }

    ## Retourne l'identifiant du plugin solicité

    public function getPluginToCall(): string {
        return $this->pluginToCall;
    }

    ## Retourne le tableau de ressources JS de base

    public function getJs() {
        return $this->js;
    }

    ## Retourne le tableau de ressources CSS de base

    public function getCss() {
        return $this->css;
    }

    public function auth(): AuthService
    {
        if ($this->authService === null) {
            $this->authService = new AuthService();
        }

        return $this->authService;
    }

    public function pages(): Page
    {
        if ($this->pageService === null) {
            $this->pageService = new Page();
        }

        return $this->pageService;
    }

    public function media(): MediaService
    {
        if ($this->mediaService === null) {
            $this->mediaService = new MediaService();
        }

        return $this->mediaService;
    }

    public function settings(): SettingsService
    {
        if ($this->settingsService === null) {
            $this->settingsService = new SettingsService();
        }

        return $this->settingsService;
    }

    public function extensions(): ExtensionsService
    {
        if ($this->extensionsService === null) {
            $this->extensionsService = new ExtensionsService();
        }

        return $this->extensionsService;
    }

    /**
     * Check if a module name is a core module (page, users, etc.)
     * @param string $moduleName
     * @return bool
     */
    public function isCoreModule(string $moduleName): bool {
        $coreModules = ['page', 'users', 'configmanager', 'filemanager', 'marketplace', 'pluginsmanager'];
        return in_array($moduleName, $coreModules, true);
    }

    protected function determineLocale(): string
    {
        $locale = $this->getConfigVal('siteLang');
        if ($locale === false) {
            if (isset($_GET['lang'])) {
                $locale = $_GET['lang'];
            } else {
                $navLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
                if (file_exists(COMMON . 'langs/' . $navLang . '.ini')) {
                    $locale = $navLang;
                } else {
                    $locale = 'fr';
                }
            }
        }

        return (string) $locale;
    }

    public function addMeta(string $meta) {
        $this->metas[] = $meta;
    }

    public function getMetas() {
        return $this->metas;
    }

    /**
     * Set page title tag (for <title>)
     * @param string $title
     */
    public function setTitleTag(string $title): void {
        $this->titleTag = $title;
    }

    /**
     * Get page title tag
     * @return string|null
     */
    public function getTitleTag(): ?string {
        return $this->titleTag;
    }

    /**
     * Set main title (for <h1>)
     * @param string $title
     */
    public function setMainTitle(string $title): void {
        $this->mainTitle = $title;
    }

    /**
     * Get main title
     * @return string|null
     */
    public function getMainTitle(): ?string {
        return $this->mainTitle;
    }

    /**
     * Set meta description
     * @param string $description
     */
    public function setMetaDescription(string $description): void {
        $this->metaDescription = $description;
    }

    /**
     * Get meta description
     * @return string|null
     */
    public function getMetaDescription(): ?string {
        return $this->metaDescription;
    }

    /**
     * Add item to core navigation (for core modules like page)
     * @param string $label
     * @param string $target
     * @param string $targetAttribut
     * @param int $id
     * @param int $parent
     * @param string $cssClass
     */
    public function addToCoreNavigation(string $label, string $target, string $targetAttribut = '_self', int $id = 0, int $parent = 0, string $cssClass = ''): void {
        $this->coreNavigation[] = [
            'label' => $label,
            'target' => $target,
            'targetAttribut' => $targetAttribut,
            'id' => $id,
            'parent' => $parent,
            'cssClass' => $cssClass
        ];
    }

    /**
     * Get core navigation items
     * @return array
     */
    public function getCoreNavigation(): array {
        return $this->coreNavigation;
    }

    ## Détermine si 299ko est installé

    public function isInstalled() {
        if (!file_exists(DATA . 'config.json'))
            return false;
        else
            return true;
    }

    ## Génère l'URL du site

    public function makeSiteUrl() {
        $siteUrl = str_replace(array('install.php', '/admin', '/index.php'), array('', '', ''), $_SERVER['SCRIPT_NAME']);
        $isSecure = false;
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
            $isSecure = true;
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on')
            $isSecure = true;
        $REQUEST_PROTOCOL = $isSecure ? 'https' : 'http';
        $siteUrl = $REQUEST_PROTOCOL . '://' . $_SERVER['HTTP_HOST'] . $siteUrl;
        $pos = mb_strlen($siteUrl) - 1;
        if ($siteUrl[$pos] == '/')
            $siteUrl = substr($siteUrl, 0, -1);
        return $siteUrl;
    }

    ## Alimente le tableau des hooks

    public function addHook($name, $function) {
        $this->hooks[$name][] = $function;
    }

    /**
     * Permet d'appeler un hook
     * Si un paramètre est fourni, celui-ci sera passé de fonction en fonction Hook de filtre).
     * Sinon, la valeur de retour sera concaténé à chaque fonction (Hook d'action).
     * 
     * @param   string  Nom du hook
     * @param   mixed   Paramètres
     * @return  mixed
     */
    public function callHook($name, $params = null) {
        if ($params === null) {
            // Action
            $return = '';
            if (isset($this->hooks[$name])) {
                foreach ($this->hooks[$name] as $function) {
                    // Handle FQCN function names (with namespace)
                    if (is_string($function) && strpos($function, '\\') !== false && strpos($function, '::') === false) {
                        // Ensure FQCN starts with backslash for global namespace
                        $function = '\\' . ltrim($function, '\\');
                    }
                    if (is_callable($function)) {
                        $return .= call_user_func($function);
                    }
                }
            }
            return $return;
        }
        // Filter
        if (isset($this->hooks[$name])) {
            foreach ($this->hooks[$name] as $function) {
                // Handle FQCN function names (with namespace)
                if (is_string($function) && strpos($function, '\\') !== false && strpos($function, '::') === false) {
                    // Ensure FQCN starts with backslash for global namespace
                    $function = '\\' . ltrim($function, '\\');
                }
                if (is_callable($function)) {
                    $params = call_user_func($function, $params);
                }
            }
        }
        return $params;
    }

    ## Detecte le mode de l'administration

    public function detectAdminMode() {
        $mode = '';
        if (isset($_GET['action']) && $_GET['action'] == 'login')
            return 'login';
        elseif (isset($_GET['action']) && $_GET['action'] == 'logout')
            return 'logout';
        elseif (isset($_GET['action']) && $_GET['action'] == 'lostpwd')
            return 'lostpwd';
        elseif (!isset($_GET['p']))
            return 'plugin';
        elseif (isset($_GET['p']))
            return 'plugin';
    }

    public function detectAjaxRequest() {
        $ajaxGet = $_GET['request'] ?? false;
        $ajaxPost = $_POST['request'] ?? false;
        return ($ajaxGet === 'ajax' || $ajaxPost === 'ajax');
    }

    /**
     * Redirect to an other URL and stop current connection
     * 
     * @param string $url
     */
    public function redirect(string $url): void {
        header_remove();
        header('location:' . $url);
        die();
    }

    ## Renvoi une page 404

    public function error404() {
        if (!defined('ADMIN_MODE')) {
            define('ADMIN_MODE', false);
        }
        header("HTTP/1.1 404 Not Found");
        header("Status: 404 Not Found");
        $response = new \Core\Responses\PublicResponse();
        $tpl = $response->createCoreTemplate('404');
        $response->addTemplate($tpl);
        echo $response->output();
        die();
    }

    /**
     * Saves a configuration value to the config file.
     *
     * @param string|array $val The configuration value to save. 
     * @param array $append Additional configuration values to append.
     * @return bool True if the save was successful, false otherwise.
     */
    public function saveConfig($val, array $append = []): bool {
        $config = Util::readJsonFile(DATA . 'config.json', true);
        $config = array_merge($config, $append);
        foreach ($config as $k => $v)
            if (isset($val[$k])) {
                $config[$k] = $val[$k];
            }
        if (Util::writeJsonFile(DATA . 'config.json', $config)) {
            $this->config = Util::readJsonFile(DATA . 'config.json', true);
            return true;
        } else
            return false;
    }

    /**
     * 299ko installation
     */
    public function install() {
        $install = true;
        @chmod(ROOT . '.htaccess', 0604);
        if (!is_dir(DATA) && (!@mkdir(DATA) || !@chmod(DATA, 0755)))
            $install = false;
        if ($install) {
            if (!file_exists(DATA . '.htaccess')) {
                if (
                    !@file_put_contents(
                        DATA . '.htaccess',
                        "<IfModule mod_authz_core.c>
                        # Apache 2.4
                        Require all denied
                    </IfModule>
                    <IfModule !mod_authz_core.c>
                        # Apache 2.2
                        Order deny,allow
                        Deny from all
                    </IfModule>",
                        0604
                    )
                )
                    $install = false;
            }
            if (!is_dir(DATA_PLUGIN) && (!@mkdir(DATA_PLUGIN) || !@chmod(DATA_PLUGIN, 0755)))
                $install = false;
            if (!is_dir(UPLOAD) && (!@mkdir(UPLOAD) || !@chmod(UPLOAD, 0755)))
                $install = false;
            if (!file_exists(UPLOAD . '.htaccess')) {
                if (
                    !@file_put_contents(
                        UPLOAD . '.htaccess',
                        "<IfModule mod_authz_core.c>
                        # Apache 2.4
                        Require all granted
                    </IfModule>
                    <IfModule !mod_authz_core.c>
                        # Apache 2.2
                        Order allow,deny
                        Allow from all
                    </IfModule>",
                        0604
                    )
                )
                    $install = false;
            }
            if (!file_exists(__FILE__) || !@chmod(__FILE__, 0644))
                $install = false;
            $key = uniqid(true);
            if (
                !file_exists(DATA . 'key.php') && !@file_put_contents(DATA
                    . 'key.php', "<?php\ndefined('ROOT') OR exit"
                    . "('Access denied!');"
                    . "\ndefine('KEY', '$key'); ?>", 0604)
            )
                $install = false;
        }
        return $install;
    }

    /**
     * Get .htaccess file content
     */
    public function getHtaccess() {
        return @file_get_contents(ROOT . '.htaccess');
    }

    /**
     * Update .htaccess file content
     */
    public function saveHtaccess($content) {
        $content = str_replace("&amp;", "&", $content);
        @file_put_contents(ROOT . '.htaccess', $content);
    }

    protected function createLogger() {
        $this->logger = Logger::getInstance($this->config['debug'] ?? false);
    }

    protected function loadEnv() {
        if ($this->config === false)
            return;
        if ($this->env->get('siteUrl') !== null) {
            $this->config['siteUrl'] = $this->env->get('siteUrl');
        }
        if ($this->env->get('debug') !== null) {
            $this->config['debug'] = $this->env->get('debug');
        }
    }

    public function getEnv($key, $default = null) {
        return $this->env->get($key, $default);
    }

    public function getLogger() {
        return $this->logger;
    }

    /**
     * Add a log into log file
     * 
     * @param string|array Message
     * @param string Severity
     * Can be 'INFO', 'DEBUG', 'WARNING', 'ERROR'
     */
    public function log($message, $severity = 'INFO') {
        $this->logger->log($severity, $message);
    }

    public function executeCallback(Router $router, ?Plugin $runPlugin) {
        $match = $router->match();
        if (is_array($match)) {
            // Charger les contrôleurs du plugin si nécessaire
            if ($runPlugin) {
                $runPlugin->loadControllers();
            } else {
                // Si pas de runPlugin mais qu'on appelle un contrôleur de plugin, le charger
                list($controller, $action) = explode('#', $match['target']);
                if (strpos($controller, 'Controller') !== false && !class_exists($controller)) {
                    // Si c'est un contrôleur core Auth, charger le fichier directement
                    if (strpos($controller, 'Core\\Auth\\Controllers\\') === 0 || strpos($controller, 'Core\\Page\\Controllers\\') === 0 || strpos($controller, 'Core\\Settings\\Controllers\\') === 0 || strpos($controller, 'Core\\Media\\Controllers\\') === 0) {
                        $controllerFile = str_replace('\\', DIRECTORY_SEPARATOR, $controller);
                        $controllerFile = COMMON . $controllerFile . '.php';
                        if (file_exists($controllerFile)) {
                            require_once $controllerFile;
                        }
                    } else {
                        // Essayer de trouver le plugin correspondant
                        $pluginsManager = \Core\Plugin\PluginsManager::getInstance();
                        foreach ($pluginsManager->getPlugins() as $plugin) {
                            if ($plugin->getConfigVal('activate')) {
                                $plugin->loadControllers();
                                if (class_exists($controller)) {
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            list($controller, $action) = explode('#', $match['target']);
            // Si le contrôleur n'a pas de namespace, il est dans l'espace global
            if (strpos($controller, '\\') === false) {
                $controller = '\\' . $controller;
            }
            if (method_exists($controller, $action)) {
                $cacheKey = $this->getCacheKey($match);
                if ($this->getConfigVal('cache_enabled') && $cacheKey) {
                    $cache = new \Core\Storage\Cache();
                    $content = $cache->get($cacheKey);
                    if ($content !== false) {
                        echo $content;
                        ob_end_flush();
                        $this->logGenerationTime();
                        die();
                    }
                }
                // No cache
                // Si le contrôleur n'a pas de namespace, il est dans l'espace global
                if (strpos($controller, '\\') === false) {
                    $controller = '\\' . $controller;
                }
                $obj = new $controller();
                $this->callHook('beforeRunPlugin');
                
                // Capturer la sortie pour détecter si die() a été appelé
                ob_start();
                $response = call_user_func_array([$obj, $action], $match['params']);
                $output = ob_get_clean();
                
                // Si die() a été appelé, la sortie a déjà été envoyée
                if (!empty($output)) {
                    echo $output;
                    ob_end_flush();
                    $this->logGenerationTime();
                    die();
                }
                
                // Sinon, traiter la réponse normalement
                if ($response instanceof \Core\Responses\Response) {
                    $content = $response->output();
                    $optimizedContent = $this->processResponseContent($response);
                    if ($this->getConfigVal('cache_enabled') && $cacheKey) {
                        
                        $this->setResponseCache($optimizedContent, $cacheKey);
                        echo $optimizedContent;
                        ob_end_flush();
                        $this->logGenerationTime();
                        die();
                    } else {
                        echo $optimizedContent;
                        ob_end_flush();
                        $this->logGenerationTime();
                        die();
                    }
                } else {
                    // Si la méthode ne retourne pas une Response, c'est une erreur
                    $this->error404();
                }
            } else {
                // unreachable target
                $this->error404();
            }
        }

        $this->error404();
    }


    protected function processResponseContent(Response $response): string {
        $content = $response->output();
        $minifyer = new \Content\Minifyer();
        $content = $minifyer->minify($content);
        return $content;
    }

    protected function setResponseCache(string $content, string $cacheKey = ''): void {
        if (defined('ADMIN_MODE') && ADMIN_MODE) {
            return;
        }

        // Check if cache is enabled
        if (!$this->getConfigVal('cache_enabled')) {
            return;
        }
        $cache = new \Core\Storage\Cache();
        $cache->set($cacheKey, $content, $this->getConfigVal('cache_duration') ?: 3600, $this->generateCacheTags());
    }

    protected function getCacheKey(array $match) {
        // Only process in public mode
        if (defined('ADMIN_MODE') && ADMIN_MODE) {
            return false;
        }

        // Check if cache is enabled
        if (!$this->getConfigVal('cache_enabled')) {
            return false;
        }

        $cacheKey = $match['target'] . serialize($match['params']);

        // Ajout sécurité : inclure l'état de protection dans la clé
        // 1. Protection par mot de passe de page
        if (isset($_SESSION['pagePassword'])) {
            $cacheKey .= '_pw_' . sha1($_SESSION['pagePassword']);
        }
        // 2. Utilisateur connecté
        if (class_exists('Core\Auth\UsersManager') && method_exists('Core\Auth\UsersManager', 'getCurrentUser')) {
            $user = \Core\Auth\UsersManager::getCurrentUser();
            if ($user && isset($user->id)) {
                $cacheKey .= '_user_' . $user->id;
            }
        }

        return $cacheKey;
    }

    /**
     * Generate cache tags based on current context
     * 
     * @return array
     */
    protected function generateCacheTags(): array {
        $tags = ['page'];

        // Tag pour le thème actuel
        $currentTheme = $this->getConfigVal('theme');
        if ($currentTheme) {
            $tags[] = 'theme_' . $currentTheme;
        }

        // Tag pour le plugin actuel
        $currentPlugin = $this->getPluginToCall();
        if ($currentPlugin) {
            $tags[] = 'plugin_' . $currentPlugin;
        }

        // Tag pour la langue
        $currentLang = $this->getConfigVal('siteLang');
        if ($currentLang) {
            $tags[] = 'lang_' . $currentLang;
        }

        // Tag pour les paramètres de cache
        if ($this->getConfigVal('cache_minify')) {
            $tags[] = 'minify_enabled';
        }
        if ($this->getConfigVal('cache_lazy_loading')) {
            $tags[] = 'lazy_enabled';
        }

        return $tags;
    }

    /**
     * Invalidate cache by tag
     * 
     * @param string $tag
     * @return void
     */
    public function invalidateCacheByTag(string $tag): void {
        $cache = new \Core\Storage\Cache();
        $cache->deleteByTag($tag);
    }

    /**
     * Invalidate all cache
     * 
     * @return void
     */
    public function invalidateAllCache(): void {
        $cacheManager = new \Core\Storage\CacheManager();
        $cacheManager->clearCache();
    }

    public function addQueryCounter() {
        $this->queryCount++;
    }

    public function logGenerationTime() {
        if ($this->config && !$this->config['debug']) {
            return;
        }
        $endTime = microtime(true);
        echo '<script>console.log("Generation time: ' . round($endTime - $this->startTime, 3) . 's");';
        echo 'console.log("Queries: ' . $this->queryCount . '");</script>';
    }
}

/**
 * Add a log into log file
 * @see \core->log()
 * 
 * @param string|array Message
 * @param string Severity
 * Can be 'INFO', 'DEBUG', 'WARNING', 'ERROR'
 */
function logg($message, $severity = 'INFO') {
    \Core\Core::getInstance()->log($message, $severity);
}

/**
 * Debug display as var_dump with <pre> tag
 * @param mixed $message Message or var to display
 * @return void
 */
function debug($message): void {
    echo '<pre>';
    var_dump($message);
    echo '</pre>';
}
