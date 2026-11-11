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
     * @var Logger
     */
    private $logger;


    public float $startTime;

    private int $queryCount = 0;
    ## Constructeur

    public function __construct()
    {
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

    public function init()
    {
        if (!defined('REQUEST_ID')) {
            define('REQUEST_ID', bin2hex(random_bytes(8)));
        }

        $this->config = Util::readJsonFile(DATA . 'config.json', true);
        $this->env = new Env(ROOT . '.env');
        $this->loadEnv();
        $this->createLogger();

        // Sécurité : En-têtes HTTP globaux
        // Protection contre le clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        // Protection contre le sniffing de MIME type
        header('X-Content-Type-Options: nosniff');
        // Protection XSS pour les navigateurs anciens
        header('X-XSS-Protection: 1; mode=block');
        // Politique de referrer
        header('Referrer-Policy: strict-origin-when-cross-origin');
        // Politique de permissions (désactivation fonctionnalités sensibles par défaut)
        // Correction : Autoriser geolocation pour 'self' pour la carte interactive
        header('Permissions-Policy: geolocation=(self), microphone=(), camera=()');

        // Content Security Policy (CSP) Permissive pour commencer
        // Autorise 'self', les scripts inline (nécessaire pour legacy), et les images data/https
        // À durcir progressivement
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tiny.cloud https://cdnjs.cloudflare.com https://unpkg.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com https://unpkg.com https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' data: https: https://fonts.gstatic.com; connect-src 'self' https:; frame-src 'self' https:;");

        // Timezone
        date_default_timezone_set('Europe/Paris');
        
        // Globals pour les templates
        if (class_exists('\Template\Template')) {
            \Template\Template::addGlobal('VERSION', VERSION);
            \Template\Template::addGlobal('ROOT', ROOT);
        }

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
                    $this->pluginToCall = 'dashboard';
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
        
        // MAINTENANCE MODE CHECK
        if ($this->getConfigVal('maintenance_mode')) {
            $user = $this->auth()->getCurrentUser();
            $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
            $isAdmin = $evaluator->isAdmin(new \Core\Navigation\ConditionContext($user));
            
            $uri = Router::getInstance()->getCleanURI();
            
            // Allow login and admin pages
            $allowedRoutes = ['/users/login', '/admin', '/users/auth']; 
            
            $isAllowed = $isAdmin;
            if (!$isAllowed) {
                foreach ($allowedRoutes as $route) {
                     if (strpos($uri, $route) === 0) {
                         $isAllowed = true;
                         break;
                     }
                }
            }
            
            // Allow resources if they pass through router (unlikely for static files but possible)
            if (!$isAllowed && (strpos($uri, '/theme/') === 0 || strpos($uri, '/common/') === 0 || strpos($uri, '/upload/') === 0)) {
                 $isAllowed = true;
            }
            
            if (!$isAllowed) {
                http_response_code(503);
                $maintenanceMessage = $this->getConfigVal('maintenance_message');
                $maintenanceTemplate = $this->getConfigVal('maintenance_template');
                
                $templatePath = '';
                
                // 1. Try selected theme template
                if (!empty($maintenanceTemplate)) {
                     $themeFile = THEMES . $this->getConfigVal('theme') . '/' . $maintenanceTemplate;
                     if (file_exists($themeFile)) {
                         $templatePath = $themeFile;
                     }
                }
                
                // 2. Fallback to common/template/maintenance.tpl
                if (empty($templatePath) && file_exists(COMMON . 'template/maintenance.tpl')) {
                    $templatePath = COMMON . 'template/maintenance.tpl';
                }
                
                if (!empty($templatePath)) {
                    $tpl = new \Template\Template($templatePath);
                    $tpl->set('maintenanceMessage', $maintenanceMessage);
                    // Add global CORE variable
                    \Template\Template::addGlobal('CORE', $this);
                    echo $tpl->output();
                } else {
                    echo "<h1>Maintenance</h1><p>" . ($maintenanceMessage ?: "Site under maintenance.") . "</p>";
                }
                exit;
            }
        }

        // Note: Les styles des formulaires users sont maintenant dans le thème (theme/EVO/css/theme.css)
        $this->pages();
        $this->pages()->registerRoutes(Router::getInstance());
        $this->menus()->registerRoutes(Router::getInstance());
        require_once COMMON . 'Core/Page/hooks.php';
        $this->addHook('endFrontHead', '\Core\Page\pageEndFrontHead');
        $this->addHook('search:get-results', '\Core\Page\pageSearchResultsHook');

        // Hooks d'URL d'authentification gérés par le core (login, register, logout, profil, admin)
        require_once COMMON . 'Core/Auth/hooks.php';
        $this->addHook('user:login-url', '\Core\Auth\authCoreLoginUrl');
        $this->addHook('user:register-url', '\Core\Auth\authCoreRegisterUrl');
        $this->addHook('user:logout-url', '\Core\Auth\authCoreLogoutUrl');
        $this->addHook('user:profile-url', '\Core\Auth\authCoreProfileUrl');
        $this->addHook('admin:url', '\Core\Auth\authCoreAdminUrl');

        // Télémétrie : vérifier la synchronisation avant chaque exécution
        require_once COMMON . 'Core/Telemetry/hooks.php';
        $this->addHook('beforeRunPlugin', '\Core\Telemetry\telemetryCheckSync');
        $this->media();
        $this->media()->registerRoutes(Router::getInstance());
        $this->settings();
        $this->settings()->registerRoutes(Router::getInstance());
        $this->extensions()->registerRoutes(Router::getInstance());
        
        // Exposer l'URI courante nettoyée dans le template globalement
        \Template\Template::addGlobal('CURRENT_URI', Router::getInstance()->getCleanURI());
    }

    /**
     * Return Core Instance
     * 
     * @return \self
     */
    public static function getInstance()
    {
        if (is_null(self::$instance))
            self::$instance = new Core();
        return self::$instance;
    }

    ## Retourne la liste des thèmes

    public function getThemes()
    {
        return $this->themes;
    }

    ## Retourne la configuration complète

    public function getconfig()
    {
        return $this->config;
    }

    ## Retourne une valeur de configuration
    public function getConfigVal($k = null)
    {
        if ($k === null) {
            return false;
        }
        if (isset($this->config[$k])) {
            return $this->config[$k];
        } else {
            return false;
        }
    }

    /**
     * Set up a config val.
     * This setting will not be saved
     * 
     * @param string $key
     * @param string $value
     */
    public function setConfigVal($key, $value)
    {
        $this->config[$key] = $value;
    }

    ## Retourne les infos du thème ciblé

    public function getThemeInfo($k)
    {
        if (isset($this->themes[$this->getConfigVal('theme')]))
            return $this->themes[$this->getConfigVal('theme')][$k];
        else
            return false;
    }

    ## Retourne l'identifiant du plugin solicité

    public function getPluginToCall(): string
    {
        return $this->pluginToCall;
    }

    ## Retourne le tableau de ressources JS de base

    public function getJs()
    {
        return $this->js;
    }

    ## Retourne le tableau de ressources CSS de base

    public function getCss()
    {
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

    public function menus(): \Core\Navigation\MenuService
    {
        return \Core\Navigation\MenuService::getInstance();
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
    public function isCoreModule(string $moduleName): bool
    {
        $coreModules = ['dashboard', 'page', 'menus', 'users', 'configmanager', 'filemanager', 'marketplace', 'pluginsmanager'];
    return in_array(strtolower($moduleName), $coreModules, true);
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

    public function addMeta(string $meta)
    {
        $this->metas[] = $meta;
    }

    public function getMetas()
    {
        return $this->metas;
    }

    /**
     * Set page title tag (for <title>)
     * @param string $title
     */
    public function setTitleTag(string $title): void
    {
        $this->titleTag = $title;
    }

    /**
     * Get page title tag
     * @return string|null
     */
    public function getTitleTag(): ?string
    {
        return $this->titleTag;
    }

    /**
     * Set main title (for <h1>)
     * @param string $title
     */
    public function setMainTitle(string $title): void
    {
        $this->mainTitle = $title;
    }

    /**
     * Get main title
     * @return string|null
     */
    public function getMainTitle(): ?string
    {
        return $this->mainTitle;
    }

    /**
     * Set meta description
     * @param string $description
     */
    public function setMetaDescription(string $description): void
    {
        $this->metaDescription = $description;
    }

    /**
     * Get meta description
     * @return string|null
     */
    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    /**
     * Add item to core navigation (for core modules like page)
     * 
     * @deprecated Utiliser NavigationService::register() à la place
     * Conservé pour compatibilité legacy
     * 
     * @param string $label
     * @param string $target
     * @param string $targetAttribut
     * @param int $id
     * @param int $parent
     * @param string $cssClass
     * @param string|null $icon
     */
    public function addToCoreNavigation(string $label, string $target, string $targetAttribut = '_self', $id = 0, $parent = 0, string $cssClass = '', ?string $icon = null, string $navContext = 'main'): void
    {
        // Pour compatibilité, on garde l'ancien tableau
        // NavigationService::loadFromPage() chargera depuis ce tableau pour éviter les doublons
        $this->coreNavigation[] = [
            'label' => $label,
            'target' => $target,
            'targetAttribut' => $targetAttribut,
            'id' => $id,
            'parent' => $parent,
            'cssClass' => $cssClass,
            'icon' => $icon,
            'navContext' => $navContext
        ];
    }

    /**
     * Get core navigation items
     * 
     * @deprecated Utiliser NavigationService::getTree() à la place
     * Conservé pour compatibilité legacy
     * 
     * @return array
     */
    public function getCoreNavigation(): array
    {
        return $this->coreNavigation;
    }

    ## Détermine si 299ko est installé

    public function isInstalled()
    {
        if (!file_exists(DATA . 'config.json'))
            return false;
        else
            return true;
    }

    ## Génère l'URL du site

    public function makeSiteUrl()
    {
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

    public function addHook($name, $function)
    {
        if (isset($this->hooks[$name])) {
            foreach ($this->hooks[$name] as $existingFunction) {
                if ($existingFunction === $function) {
                    // Hook déjà enregistré, ignorer
                    return;
                }
            }
        }
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
    public function callHook($name, $params = null)
    {
        // Logger pour before404 et endFrontBody
        // DEBUG: Force logging for adminAfterHeader
        if ($name === 'adminAfterHeader') {
            $this->getLogger()->log(\Core\Logger::LEVEL_INFO, "Core::callHook('{$name}') - Called! Hooks registered: " . (isset($this->hooks[$name]) ? count($this->hooks[$name]) : 0));
        }

        if ($name === 'before404' || $name === 'endFrontBody') {
            $logger = $this->getLogger();
            $hookCount = isset($this->hooks[$name]) ? count($this->hooks[$name]) : 0;
            // $logger->log(\Core\Logger::LEVEL_DEBUG, "Core::callHook('{$name}') - Hooks enregistrés: {$hookCount}");
            if ($hookCount > 0 && isset($this->hooks[$name])) {
                foreach ($this->hooks[$name] as $idx => $function) {
                    // $logger->log(\Core\Logger::LEVEL_DEBUG, "Core::callHook('{$name}') - Hook #{$idx}: " . (is_string($function) ? $function : gettype($function)));
                }
            }
        }

        if ($name === 'navigation:build') {
            $logger = $this->getLogger();
            $hookCount = isset($this->hooks[$name]) ? count($this->hooks[$name]) : 0;
            // $logger->debug("Core::callHook('{$name}') - Called with {$hookCount} registered hooks");
        }

        if ($params === null) {
            // Action
            $return = '';
            if (isset($this->hooks[$name])) {
                foreach ($this->hooks[$name] as $idx => $function) {
                    if ($name === 'navigation:build') {
                        $logger = $this->getLogger();
                        // $logger->debug("Core::callHook('{$name}') - Executing hook #{$idx}: " . (is_string($function) ? $function : gettype($function)));
                    }
                    // Handle FQCN function names (with namespace)
                    if (is_string($function) && strpos($function, '\\') !== false && strpos($function, '::') === false) {
                        // Ensure FQCN starts with backslash for global namespace
                        $function = '\\' . ltrim($function, '\\');
                    }
                    // Vérifier si c'est callable (fonction globale ou méthode de classe)
                    // Ne pas utiliser function_exists() car ça ne fonctionne pas pour les méthodes de classe statiques
                    if (!is_callable($function)) {
                        if ($name === 'before404' || $name === 'endFrontBody' || $name === 'adminHead' || $name === 'endFrontHead') {
                            $logger = $this->getLogger();
                            if (is_string($function) && strpos($function, '::') !== false) {
                                list($className, $methodName) = explode('::', $function, 2);
                                $logger->log(\Core\Logger::LEVEL_ERROR, "Core::callHook('{$name}') - Hook #{$idx} méthode non callable: {$function} (classe existe: " . (class_exists($className) ? 'OUI' : 'NON') . ", méthode existe: " . (class_exists($className) && method_exists($className, $methodName) ? 'OUI' : 'NON') . ")");
                            } else {
                                $logger->log(\Core\Logger::LEVEL_ERROR, "Core::callHook('{$name}') - Hook #{$idx} fonction non callable: " . (is_string($function) ? $function : gettype($function)));
                            }
                        }
                        continue;
                    }

                    // La fonction/méthode est callable, on peut l'appeler
                    if (is_callable($function)) {
                        if ($name === 'before404' || $name === 'endFrontBody') {
                            $logger = $this->getLogger();
                            // $logger->log(\Core\Logger::LEVEL_DEBUG, "Core::callHook('{$name}') - Exécution du hook #{$idx}: " . (is_string($function) ? $function : gettype($function)));
                        }
                        $return .= call_user_func($function);
                    } else {
                        if ($name === 'before404' || $name === 'endFrontBody') {
                            $logger = $this->getLogger();
                            $logger->log(\Core\Logger::LEVEL_ERROR, "Core::callHook('{$name}') - Hook #{$idx} non callable: " . (is_string($function) ? $function : gettype($function)) . " - function_exists: " . (is_string($function) && function_exists($function) ? 'OUI' : 'NON'));
                        }
                    }
                }
            }
            return $return;
        }
        // Filter
        $isHtmlHook = (
            str_ends_with($name, ':render') ||
            str_ends_with($name, ':actions') ||
            str_ends_with($name, ':settings') ||
            str_ends_with($name, ':preferences') ||
            str_ends_with($name, '-actions') ||
            str_ends_with($name, '-settings') ||
            str_ends_with($name, '-preferences') ||
            $name === 'searchEVO:poi-actions' ||
            $name === 'header-actions' ||
            $name === 'item-actions' ||
            str_ends_with($name, ':profile-tabs') ||
            str_ends_with($name, ':profile-tabs-content') ||
            $name === 'endMainNavigation' ||
            $name === 'afterHeader' ||
            $name === 'footer' ||
            $name === 'endFrontBody' ||
            $name === 'endFrontHead' ||
            $name === 'frontHead'
        );

        // Exclure spécifiquement les hooks de filtrage de données qui finissent par -settings
        if ($name === 'user:profile-allowed-settings') {
            $isHtmlHook = false;
        }

        if (!isset($this->hooks[$name])) {
            return $isHtmlHook ? '' : $params;
        }

        // Si on a des hooks, on les exécute
        // Pour navigation:build, on veut passer l'arbre à chaque hook sans le modifier
        // On garde une copie de l'arbre original pour les hooks suivants
        $originalParams = $params;

        if ($isHtmlHook) {
                // Concaténer les résultats comme un action hook
                $return = '';
                foreach ($this->hooks[$name] as $function) {
                    // Handle FQCN function names (with namespace)
                    if (is_string($function) && strpos($function, '\\') !== false && strpos($function, '::') === false) {
                        // Ensure FQCN starts with backslash for global namespace
                        $function = '\\' . ltrim($function, '\\');
                    }
                    if (is_callable($function)) {
                        if (is_array($params) && count($params) === 1 && isset($params[0])) {
                            $result = call_user_func($function, $params[0]);
                        } else {
                            $result = call_user_func($function, $params);
                        }
                        if (is_string($result)) {
                            $return .= $result;
                        }
                    }
                }
                return $return;
            }

            // Comportement normal pour les autres filter hooks
            foreach ($this->hooks[$name] as $function) {
                // Handle FQCN function names (with namespace)
                if (is_string($function) && strpos($function, '\\') !== false && strpos($function, '::') === false) {
                    // Ensure FQCN starts with backslash for global namespace
                    $function = '\\' . ltrim($function, '\\');
                }
                if (is_callable($function)) {
                    // Pour navigation:build, on passe toujours l'arbre original à chaque hook
                    // Les hooks de navigation ne retournent rien (void), donc on ne modifie pas $params
                    if ($name === 'navigation:build' && is_array($originalParams) && count($originalParams) === 1) {
                        call_user_func($function, $originalParams[0]);
                    } else if (is_array($params) && count($params) === 1 && isset($params[0]) && $name !== 'user:profile-stats') {
                        $params = call_user_func($function, $params[0]);
                    } else {
                        $params = call_user_func($function, $params);
                    }
                }
        }
        return $params;
    }

    ## Detecte le mode de l'administration

    public function detectAdminMode()
    {
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

    public function detectAjaxRequest()
    {
        $ajaxGet = $_GET['request'] ?? false;
        $ajaxPost = $_POST['request'] ?? false;
        return ($ajaxGet === 'ajax' || $ajaxPost === 'ajax');
    }

    /**
     * Redirect to an other URL and stop current connection
     * 
     * @param string $url
     */
    public function redirect(string $url): void
    {
        // Ne pas utiliser header_remove() car cela supprime les en-têtes Set-Cookie (session)
        header('Location: ' . $url, true, 302);
        die();
    }

    /**
     * Affiche une page d'erreur HTTP unifiée sans jamais exposer le layout admin aux non-admins.
     */
    private function renderErrorPage(
        string $templateName,
        string $httpStatus,
        string $badge,
        string $title,
        string $message,
        string $icon,
        string $tone = 'danger'
    ): void {
        header("HTTP/1.1 {$httpStatus}");
        header("Status: {$httpStatus}");

        $canUseAdminLayout = false;
        if (defined('ADMIN_MODE') && ADMIN_MODE) {
            $currentUser = $this->auth()->getCurrentUser();
            if ($currentUser !== null) {
                $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
                $canUseAdminLayout = $evaluator->isAdmin(new \Core\Navigation\ConditionContext($currentUser));
            }
        }

        $response = $canUseAdminLayout
            ? new \Core\Responses\AdminResponse()
            : new \Core\Responses\PublicResponse();

        $tpl = $response->createCoreTemplate($templateName);
        $tpl->set('errorPartial', COMMON . 'template/error.tpl');
        $tpl->set('errBadge', $badge);
        $tpl->set('errTitle', $title);
        $tpl->set('errMessage', $message);
        $tpl->set('errIcon', $icon);
        $tpl->set('errTone', $tone);
        $tpl->set('homeUrl', rtrim((string)$this->getConfigVal('siteUrl'), '/') . '/');
        $response->addTemplate($tpl);
        echo $response->output();
        die();
    }

    ## Renvoi une page 404

    public function error404()
    {
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = isset($bt[1]) ? " (called from " . ($bt[1]['class'] ?? 'unknown') . "::" . ($bt[1]['function'] ?? 'unknown') . " at " . ($bt[0]['file'] ?? 'unknown') . ":" . ($bt[0]['line'] ?? 'unknown') . ")" : "";
        $this->getLogger()->log(\Core\Logger::LEVEL_DEBUG, "Core::error404() appelé - REQUEST_URI: '{$_SERVER['REQUEST_URI']}' - Match: " . json_encode(\Core\Router\Router::getInstance()->match()) . $caller);
        $hookCount = isset($this->hooks['before404']) ? count($this->hooks['before404']) : 0;
        if ($hookCount > 0) {
            $this->callHook('before404');
        }

        if (!defined('ADMIN_MODE')) {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            define('ADMIN_MODE', (strpos($requestUri, '/admin/') !== false || $requestUri === '/admin'));
        }

        $this->renderErrorPage(
            '404',
            '404 Not Found',
            'ERREUR 404 • PAGE INTROUVABLE',
            'Cette page est introuvable',
            'Le contenu demandé n\'existe pas ou l\'adresse a été modifiée.',
            'fa-solid fa-compass',
            'danger'
        );
    }

    ## Renvoi une page 403 Forbidden

    public function error403()
    {
        $this->renderErrorPage(
            '403',
            '403 Forbidden',
            'ACCÈS REFUSÉ • ERREUR 403',
            'Accès non autorisé',
            'Vous ne disposez pas des droits nécessaires pour consulter cette ressource.',
            'fa-solid fa-lock',
            'warning'
        );
    }

    ## Renvoi une page 500 Internal Server Error

    public function error500()
    {
        $this->renderErrorPage(
            '500',
            '500 Internal Server Error',
            'ERREUR TECHNIQUE • CODE 500',
            'Une erreur technique est survenue',
            'Le service rencontre un incident momentané. Vous allez être redirigé vers l\'accueil.',
            'fa-solid fa-screwdriver-wrench',
            'danger'
        );
    }

    /**
     * Saves a configuration value to the config file.
     *
     * @param string|array $val The configuration value to save. 
     * @param array $append Additional configuration values to append.
     * @return bool True if the save was successful, false otherwise.
     */
    public function saveConfig($val, array $append = []): bool
    {
        $config = Util::readJsonFile(DATA . 'config.json', true);
        $config = array_merge($config, $append);
        foreach ($val as $k => $v) {
            $config[$k] = $v;
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
    public function install()
    {
        $install = true;
        if (file_exists(ROOT . '.htaccess')) {
            @chmod(ROOT . '.htaccess', 0604);
        }
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
    public function getHtaccess()
    {
        if (file_exists(ROOT . '.htaccess')) {
            return @file_get_contents(ROOT . '.htaccess');
        }
        return '';
    }

    /**
     * Update .htaccess file content
     */
    public function saveHtaccess($content)
    {
        $content = str_replace("&amp;", "&", $content);
        @file_put_contents(ROOT . '.htaccess', $content);
    }

    protected function createLogger()
    {
        $this->logger = Logger::getInstance($this->config['debug'] ?? false);
        
        // Propagate debug_session_id to logger if it exists in session
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['debug_session_id'])) {
            $this->logger->setDebugSessionId($_SESSION['debug_session_id']);
        }
    }

    protected function loadEnv()
    {
        if ($this->config === false)
            return;
        if ($this->env->get('siteUrl') !== null) {
            $this->config['siteUrl'] = $this->env->get('siteUrl');
        }
        if ($this->env->get('debug') !== null) {
            $this->config['debug'] = $this->env->get('debug');
        }
    }

    public function getEnv($key, $default = null)
    {
        return $this->env->get($key, $default);
    }

    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Add a log into log file
     * 
     * @param string|array Message
     * @param string Severity
     * Can be 'INFO', 'DEBUG', 'WARNING', 'ERROR'
     */
    public function log($message, $severity = 'INFO')
    {
        $this->logger->log($severity, $message);
    }

    public function executeCallback(Router $router, ?Plugin $runPlugin)
    {
        // $this->logger->log(Logger::LEVEL_DEBUG, "Core::executeCallback - Start. Request URI: " . $_SERVER['REQUEST_URI']);
        $match = $router->match();
        
        // Centralized ADMIN_MODE detection
        if (!defined('ADMIN_MODE')) {
            $isAdmin = false;
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            $basePath = Router::getInstance()->getBasePath();
            $cleanUri = $requestUri;
            if ($basePath && $basePath !== '/' && str_starts_with($requestUri, $basePath)) {
                $cleanUri = substr($requestUri, strlen($basePath));
            }
            $cleanUri = '/' . ltrim($cleanUri, '/');

            if (is_array($match)) {
                $routeName = $match['name'] ?? '';
                $target = $match['target'] ?? '';
                // Check by route name OR target controller namespace/name OR URL prefix
                if (str_starts_with($routeName, 'admin') || 
                    str_contains($target, 'AdminController') || 
                    str_starts_with($cleanUri, '/admin/') || 
                    $cleanUri === '/admin'
                ) {
                    $isAdmin = true;
                }
            } else {
                // For 404s, still detect if we are in admin context via URL
                if (str_starts_with($cleanUri, '/admin/') || $cleanUri === '/admin') {
                    $isAdmin = true;
                }
            }
            define('ADMIN_MODE', $isAdmin);
        }

        $this->logger->log(\Core\Logger::LEVEL_INFO, "Core::executeCallback - URI: " . $_SERVER['REQUEST_URI'] . " - Match: " . ($match ? "{$match['name']} ({$match['target']})" : "No Match") . " - ADMIN_MODE: " . (ADMIN_MODE ? 'YES' : 'NO'));

        if (is_array($match)) {
            // Charger les contrôleurs du plugin si nécessaire
            if ($runPlugin) {
                $runPlugin->loadControllers();
            } else {
                // Si pas de runPlugin mais qu'on appelle un contrôleur de plugin, le charger
                list($controller, $action) = explode('#', $match['target']);
                if (strpos($controller, 'Controller') !== false && !class_exists($controller)) {
                    // Si c'est un contrôleur core, charger le fichier directement
                    // Ajout de Core\Storage\Controllers\ et Core\Controllers\ qui manquaient
                    if (strpos($controller, 'Core\\Auth\\Controllers\\') === 0 || 
                        strpos($controller, 'Core\\Page\\Controllers\\') === 0 || 
                        strpos($controller, 'Core\\Settings\\Controllers\\') === 0 || 
                        strpos($controller, 'Core\\Media\\Controllers\\') === 0 ||
                        strpos($controller, 'Core\\Storage\\Controllers\\') === 0 ||
                        strpos($controller, 'Core\\Extensions\\Controllers\\') === 0 ||
                        strpos($controller, 'Core\\Controllers\\') === 0) {
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

            if (!class_exists($controller)) {
                $this->logger->log(Logger::LEVEL_ERROR, "Core::executeCallback - Classe contrôleur non trouvée: {$controller}");
            }

            if (method_exists($controller, $action)) {
                // allow plugins to disable core cache for specific routes
                $shouldCache = $this->callHook('core:should-cache-route', ['shouldCache' => true, 'match' => $match]);
                $cacheKey = (isset($shouldCache['shouldCache']) && $shouldCache['shouldCache']) ? $this->getCacheKey($match) : false;
                if ($this->getConfigVal('cache_enabled') && $cacheKey) {
                    $cache = new \Core\Storage\Cache();
                    $content = $cache->get($cacheKey);
                    if ($content !== false) {
                        $this->callHook('core:page-hit', ['cached' => true, 'match' => $match]);
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
                // Convertir les paramètres associatifs en paramètres indexés pour call_user_func_array
                // PHP 8.0+ ne supporte pas les paramètres nommés avec call_user_func_array
                $params = is_array($match['params']) ? array_values($match['params']) : [];
                $response = call_user_func_array([$obj, $action], $params);
                $output = ob_get_clean();

                // Si die() a été appelé, la sortie a déjà été envoyée
                if (!empty($output)) {
                    echo $output;
                    ob_end_flush();
                    $this->logGenerationTime();
                    die();
                }

                // Sinon, traiter la réponse normalement
                // DEBUG: Log type of response to debug HTMX 404
                // $this->logger->log(Logger::LEVEL_DEBUG, "Core::executeCallback - Action: {$action}, Response type: " . (is_object($response) ? get_class($response) : gettype($response)));

                if ($response instanceof \Core\Responses\Response) {
                    // Ne pas appeler output() ici car processResponseContent() l'appelle déjà
                    $optimizedContent = $this->processResponseContent($response);

                    // Pour les ApiResponse, ne pas ajouter de logs après le JSON
                    if ($response instanceof \Core\Responses\ApiResponse) {
                        echo $optimizedContent;
                        die();
                    }

                    $this->callHook('core:page-hit', ['cached' => false, 'match' => $match]);
                    if ($this->getConfigVal('cache_enabled') && $cacheKey) {
                        $this->setResponseCache($optimizedContent, $cacheKey);
                    }
                    echo $optimizedContent;
                    ob_end_flush();
                    $this->logGenerationTime();
                    die();
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


    protected function processResponseContent(Response $response): string
    {
        $content = $response->output();
        // Ne pas minifier les réponses API (JSON)
        if ($response instanceof \Core\Responses\ApiResponse) {
            return $content;
        }
        $minifyer = new \Content\Minifyer();
        $content = $minifyer->minify($content);
        return $content;
    }

    protected function setResponseCache(string $content, string $cacheKey = ''): void
    {
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

    protected function getCacheKey(array $match)
    {
        // Only process in public mode
        if (defined('ADMIN_MODE') && ADMIN_MODE) {
            return false;
        }

        // Check if cache is enabled
        if (!$this->getConfigVal('cache_enabled')) {
            return false;
        }

        // Ne JAMAIS cacher ni servir de cache si l'utilisateur est connecté !
        // Les utilisateurs connectés ont des éléments personnalisés (avatar, notifications, mur social, etc.)
        if (defined('IS_LOGGED') && IS_LOGGED) {
            return false;
        }

        // Ne JAMAIS cacher les pages d'authentification, de profils ou de formulaires avec CSRF (login, register, contact...)
        $reqUri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#^/(users/|admin|contact|claim|auth/|search/suggest|api/)#i', $reqUri)) {
            return false;
        }
        $routeName = $match['name'] ?? '';
        if (in_array($routeName, ['login', 'login-send', 'register', 'register-send', 'lost-password', 'lost-password-send', 'reset-password', 'contact', 'claim'])) {
            return false;
        }

        // Ne JAMAIS cacher ni servir de cache si un message flash (erreur, succès, etc.) est en attente
        if (\Utils\Show::hasMsg()) {
            return false;
        }

        $cacheKey = $match['target'] . serialize($match['params']);

        // CRITIQUE : Inclure tous les paramètres de requête GET (recherche, filtres, catégories, pagination...)
        // Sans cela, toutes les recherches et filtres partagent la même clé de cache !
        if (!empty($_GET)) {
            $queryParams = $_GET;
            ksort($queryParams);
            $cacheKey .= '_get_' . http_build_query($queryParams);
        }

        // Inclure la géolocalisation cookie UNIQUEMENT si la route active dépend du tri / filtrage par distance
        // (ex: Recherche, Cartographie, POI à proximité). Pour les pages statiques (home, contact, blog, mentions...),
        // cela évite de fragmenter inutilement le cache en variantes étanches.
        if (!empty($_COOKIE['user_geoloc'])) {
            $target = $match['target'] ?? '';
            $routeName = $match['name'] ?? '';
            $reqUri = $_SERVER['REQUEST_URI'] ?? '';
            $isGeolocRoute = (
                stripos($target, 'Search') !== false ||
                stripos($routeName, 'search') !== false ||
                stripos($reqUri, '/recherche') !== false ||
                stripos($reqUri, '/carte') !== false ||
                !empty($_GET['around_me']) ||
                !empty($_GET['user_lat'])
            );
            if ($isGeolocRoute) {
                $cacheKey .= '_geoloc_' . $_COOKIE['user_geoloc'];
            }
        }

        // Jeton d'invalidation globale de contenu (MAJ BDD, crawler, imports, etc.)
        $contentVersionFile = (defined('CACHE') ? CACHE : (DATA . 'cache' . DIRECTORY_SEPARATOR)) . 'content_version.txt';
        $contentVersion = file_exists($contentVersionFile) ? filemtime($contentVersionFile) : 0;
        $cacheKey .= '_cv_' . $contentVersion;

        // Invalidation automatique si le design ou les templates ont été modifiés
        $theme = $this->getConfigVal('theme');
        if ($theme && defined('THEMES')) {
            $themeDir = THEMES . $theme . DIRECTORY_SEPARATOR;
            $themeVersion = 0;
            $watchFiles = [
                'styles.css', 'styles.min.css', 'layout.tpl', 'landing.tpl', 'landing.php',
                'header.php', 'functions.php', 'header.webp', 'logo.webp', 'logo.png'
            ];
            foreach ($watchFiles as $wf) {
                if (file_exists($themeDir . $wf)) {
                    $themeVersion = max($themeVersion, filemtime($themeDir . $wf));
                }
            }
            // Surveiller récursivement le sous-dossier template/ du thème si présent
            $tplSubDir = $themeDir . 'template' . DIRECTORY_SEPARATOR;
            if (is_dir($tplSubDir)) {
                $subFiles = glob($tplSubDir . '*.tpl');
                if (is_array($subFiles)) {
                    foreach ($subFiles as $sf) {
                        $themeVersion = max($themeVersion, filemtime($sf));
                    }
                }
            }
            $cacheKey .= '_thm_' . $theme . '_v' . $themeVersion;
        }

        // Surveiller les fichiers de données dynamiques (agenda events.json)
        $agendaEventsFile = DATA . 'plugin' . DIRECTORY_SEPARATOR . 'agenda' . DIRECTORY_SEPARATOR . 'events.json';
        if (file_exists($agendaEventsFile)) {
            $cacheKey .= '_evts_' . filemtime($agendaEventsFile);
        }

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
    protected function generateCacheTags(): array
    {
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
    public function invalidateCacheByTag(string $tag): void
    {
        $cache = new \Core\Storage\Cache();
        $cache->deleteByTag($tag);
    }

    /**
     * Actualise le jeton de version de contenu pour invalider instantanément le cache HTML
     */
    public static function bumpContentVersion(): void
    {
        $cacheDir = defined('CACHE') ? CACHE : (DATA . 'cache' . DIRECTORY_SEPARATOR);
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }
        @touch($cacheDir . 'content_version.txt');
    }

    /**
     * Invalidate all cache
     * 
     * @return void
     */
    public function invalidateAllCache(): void
    {
        self::bumpContentVersion();
        $cacheManager = new \Core\Storage\CacheManager();
        $cacheManager->clearCache();
    }

    public function addQueryCounter()
    {
        $this->queryCount++;
    }

    public function logGenerationTime()
    {
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
function logg($message, $severity = 'INFO')
{
    \Core\Core::getInstance()->log($message, $severity);
}

/**
 * Debug display as var_dump with <pre> tag
 * @param mixed $message Message or var to display
 * @return void
 */
function debug($message): void
{
    echo '<pre>';
    var_dump($message);
    echo '</pre>';
}
