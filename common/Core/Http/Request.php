<?php

namespace Core\Http;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 *
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

/**
 * The Request class provides methods to access and manipulate HTTP request data.
 */
class Request
{
    
    /**
     * Gets a GET parameter by name.
     *
     * @param string $key     The parameter name.
     * @param mixed  $default The default value if the parameter does not exist.
     *
     * @return mixed The value of the requested GET parameter or the default value.
     */
    public function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Retrieves a POST parameter by name.
     *
     * @param string $key     The parameter name.
     * @param mixed  $default The default value if the parameter does not exist.
     *
     * @return mixed The value of the requested POST parameter or the default value.
     */
    public function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Retrieves a file from the $_FILES array by name.
     *
     * @param string $key The name of the file.
     *
     * @return array|null The file data or null if the file does not exist.
     */
    public function file($key) {
        return $_FILES[$key] ?? null;
    }

    /**
     * Checks if the current request is an AJAX request.
     *
     * @return bool True if the request is an AJAX request, false otherwise.
     */
    public function isAjax(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Retrieves the HTTP request method used for the current request.
     *
     * @return string The HTTP request method (e.g., 'GET', 'POST').
     */
    public function getMethod(): string {
        return $_SERVER['REQUEST_METHOD'];
    }
}