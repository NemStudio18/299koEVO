<?php

namespace Core\Controllers;

use Core\Core;
use Core\Router\Router;
use Core\Plugin\PluginsManager;
use Core\Http\Request;
use Core\Logger;
use Core\Security\Csrf;
use Utils\Show;

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

    protected bool $enforceCsrf = true;

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
        if ($this->enforceCsrf) {
            $this->validateCsrfToken();
        }
    }

    protected function validateCsrfToken(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method !== 'POST') {
            return;
        }

        $token = $_POST['_csrf'] ?? null;
        if ($token === null && isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        if ($token === null && isset($this->jsonData['_csrf'])) {
            $token = $this->jsonData['_csrf'];
        }

        if ($token === null || $token === '') {
            return;
        }

        if (Csrf::validate($token, false)) {
            return;
        }

        $this->logger->warning('CSRF token mismatch on ' . ($_SERVER['REQUEST_URI'] ?? ''));

        if ($this->request->isAjax() || stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
            header('Content-Type: application/json', true, 400);
            echo json_encode(['success' => 0, 'error' => 'Invalid CSRF token']);
            die();
        }

        Show::msg('Une erreur de sécurité est survenue, veuillez réessayer.', 'error');
        $redirect = $_SERVER['HTTP_REFERER'] ?? $this->router->generate('home');
        $this->core->redirect($redirect);
    }
}