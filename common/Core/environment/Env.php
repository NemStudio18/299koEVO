<?php

namespace Core\Environment;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 *
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

/**
 * The Env class provides methods to manage environment variables.
 */

class Env
{
    private string $path;
    private array $data = [];

    /**
     * Creates a new Env instance.
     *
     * If the given $path points to a file, its contents are read and
     * parsed as environment variables. Each line should be in the format
     * `key=value`. Lines that are empty or start with `#` are ignored.
     *
     * The parsed values are stored in the `$data` property.
     *
     * @param string $path The path to the file to parse.
     */
    public function __construct(string $path) {
        $this->path = $path;
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#') {
                    continue;
                }
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    $this->data[$key] = $this->parseValue($value);
                }
            }
        }
    }

    /**
     * Parse a string value into a boolean, null, or string.
     *
     * Returns `true` if the string is "true", `false` if it is "false",
     * `null` if it is "null", or an empty string if it is "empty".
     *
     * If the string is enclosed in quotes, removes them.
     *
     * Otherwise, returns the original string.
     *
     * @param string $value The value to parse.
     * @return bool|null|string The parsed value.
     */
    private function parseValue(string $value) {
        $lower = strtolower($value);
        if ($lower === 'true') {
            return true;
        } elseif ($lower === 'false') {
            return false;
        } elseif ($lower === 'null') {
            return null;
        } elseif ($lower === 'empty') {
            return '';
        }

        // Remove quotes if present
        if (
            (substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")
        ) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    /**
     * Formats a value into a string suitable for writing to an environment
     * file.
     *
     * Booleans are written as "true" or "false", null is written as "null",
     * and empty strings are written as "empty". Otherwise, strings are
     * written as-is unless they contain spaces, #, or quotes, in which case
     * they are quoted and any inner quotes are escaped.
     *
     * @param mixed $value The value to format.
     * @return string The formatted value.
     */
    private function formatValue($value): string {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_null($value)) {
            return 'null';
        }
        if ($value === '') {
            return 'empty';
        }
        if (preg_match('/\s/', $value) || strpbrk($value, '#"\'') !== false) {
            // Quote value if it contains spaces, #, or quotes
            return '"' . addslashes($value) . '"';
        }
        return (string) $value;
    }

    /**
     * Returns the value of a variable from the environment file.
     *
     * @param string $key The name of the variable to retrieve.
     * @param mixed $default The value to return if the variable is not set.
     * @return mixed The value of the variable if set, or the default value.
     */
    public function get(string $key, $default = null) {
        return $this->data[$key] ?? $default;
    }
}
