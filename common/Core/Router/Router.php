<?php

namespace Core\Router;

defined('ROOT') OR exit('Access denied!');

// AltoRouter est une classe externe sans namespace
require_once __DIR__ . '/AltoRouter.php';

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
class Router extends \AltoRouter {

    /**
     * 
     * @var Router
     */
    private static $instance;

    protected static $url;

    private function __construct() {
        if (!empty($_SERVER['REQUEST_URL'])) {
            $url = $this->stripFbclid($_SERVER['REQUEST_URL']);
        } elseif (!empty($_SERVER['REQUEST_URI'])) {
            $url = $this->stripFbclid($_SERVER['REQUEST_URI']);
        } else {
            $url = '/';
        }
        $url = str_replace('index.php', '', $url);
        $url = str_replace('//', '/', $url);
        self::$url = $url;
        parent::__construct();
        // Normaliser BASE_PATH : si c'est '.' ou '/' ou vide, utiliser une chaîne vide
        $basePath = str_replace('\\', '/', BASE_PATH);
        $basePath = rtrim($basePath, '/');
        if ($basePath === '.' || $basePath === '') {
            $basePath = '';
        }
        $this->setBasePath($basePath);
        $this->map('GET', '/', 'Core\Controllers\CoreController#renderHome', 'home');
        $this->map('GET', '/index.php[/?]', 'Core\Controllers\CoreController#renderHome');
        $this->map('GET', '/admin[/?]', 'Core\Controllers\CoreController#renderAdminHome', 'admin');
        $this->map('GET', '/admin/dashboard[/?]', 'Core\Controllers\CoreController#renderAdminHome', 'admin-dashboard');
    }
    
    public function getCleanURI() {
        $requestUrl = self::$url;
        if (($pos = strpos($requestUrl, "?")) !== false) {
            $requestUrl = substr($requestUrl, 0, $pos);
        }
        return substr($requestUrl, strlen($this->basePath));
    }

    public function getBasePath() {
        return $this->basePath;
    }

    protected function stripFbclid($url) {
        $patterns = array(
                '/(\?|&)fbclid=[^&]*$/' => '',
                '/\?fbclid=[^&]*&/' => '?',
                '/&fbclid=[^&]*&/' => '&'
        );
        $search = array_keys($patterns);
        $replace = array_values($patterns);
        return preg_replace($search, $replace, $url);
    }

    /**
     * Return Core Instance
     * 
     * @return \self
     */
    public static function getInstance() {
        if (is_null(self::$instance))
            self::$instance = new Router();
        return self::$instance;
    }

    public function generate($routeName, array $params = []):string {
        // Alias pour rétrocompatibilité avec les contrôleurs utilisant admin-home
        if ($routeName === 'admin-home') {
            $routeName = 'admin';
        }
        try {
            return parent::generate($routeName, $params);
        } catch (\Exception $e) {
            return '#';
        }
    }

    public function hasRoute($routeName): bool
    {
        foreach ($this->routes as $route) {
            if ($route[3] === $routeName) {
                return true;
            }
        }
        return false;
    }

    public function match ($requestUrl = null, $requestMethod = null) {
        if ($requestUrl === null) {
            $requestUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
            // Normalize URL: remove multiple slashes and index.php
            $requestUrl = str_replace('index.php', '', $requestUrl);
            $requestUrl = preg_replace('#/+#', '/', $requestUrl);
        }
        if ($requestMethod === null) {
            $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        }
        
        return parent::match($requestUrl, $requestMethod);
    }

    public function getUri():string {
        return self::$url;
    }

    /**
     * Récupère un paramètre depuis la route matchée ou depuis $_GET
     * 
     * @param string $name Nom du paramètre
     * @param mixed $default Valeur par défaut si le paramètre n'existe pas
     * @return mixed
     */
    public function getParam(string $name, $default = null) {
        // D'abord chercher dans les paramètres de route
        $match = $this->match();
        if (is_array($match) && isset($match['params'][$name])) {
            return $match['params'][$name];
        }
        // Ensuite chercher dans $_GET
        if (isset($_GET[$name])) {
            return $_GET[$name];
        }
        return $default;
    }

}