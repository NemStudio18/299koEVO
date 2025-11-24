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
        } else {
            $url = $this->stripFbclid($_SERVER['REQUEST_URI']);
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
        $this->map('GET', '/admin/', 'Core\Controllers\CoreController#renderAdminHome', 'admin');
    }
    
    public function getCleanURI() {
        $requestUrl = self::$url;
        return substr($requestUrl, strlen($this->basePath));
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
        return parent::generate($routeName, $params);
    }

    public function match ($requestUrl = null, $requestMethod = null) {
        if ($requestUrl === null) {
            // AltoRouter retire le basePath lui-même, donc on passe l'URL complète
            $requestUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
            // Normaliser l'URL : retirer les slashes multiples et index.php
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

}