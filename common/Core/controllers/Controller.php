<?php

namespace Core\Controllers;

use Core\Core;
use Core\Router\Router;
use Core\Plugin\PluginsManager;
use Core\Http\Request;
use Core\Logger;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('Access denied!');

abstract class Controller {
    
    /**
     * Core instance
     * @var Core
     */
    protected Core $core;
    
    /**
     * Router instance
     * @var Router
     */
    protected Router $router;

    /**
     * pluginsManager instance
     * @var PluginsManager
     */
    protected PluginsManager $pluginsManager;

    /**
     * Request instance
     * @var Request
     */
    protected Request $request;

    /**
     * SLogger instance
     * @var Logger
     */
    protected Logger $logger;

    /**
     * JSON data sent by fetch, used for API
     * @var array
     */
    protected array $jsonData = [];
    
    public function __construct() {
        $this->core = Core::getInstance();
        $this->router = Router::getInstance();
        $this->pluginsManager = PluginsManager::getInstance();
        $this->request = new Request();
        $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
        if ($contentType === "application/json") {
            $content = trim(file_get_contents("php://input"));
            $this->jsonData = json_decode($content, true);
        }
        $this->logger = $this->core->getLogger();
    }
}