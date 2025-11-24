<?php

namespace Core;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 *
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

/**
 * Class Logger
 */

class Logger
{
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_INFO = 'INFO';
    const LEVEL_DEBUG = 'DEBUG';
    const LEVEL_WARNING = 'WARNING';

    protected static ?Logger $instance = null;

    protected bool $debugMode;
    protected string $logDir;
    protected int $maxFileSize;

    protected function __construct(bool $debugMode, string $logDir, int $maxFileSize)
    {
        $this->debugMode = $debugMode;
        $this->logDir = rtrim($logDir, '/');
        $this->maxFileSize = $maxFileSize;

        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    /**
     * Returns the singleton instance of the Logger class.
     *
     * If the instance does not exist, it is created with the specified
     * debug mode, log directory, and maximum file size parameters.
     *
     * @param bool $debugMode Whether to enable debug mode.
     * @param string $logDir The directory where log files are stored.
     * @param int $maxFileSize The maximum file size for log files in bytes.
     * @return self The singleton instance of the Logger.
     */
    public static function getInstance(bool $debugMode = false, string $logDir = DATA . 'logs', int $maxFileSize = 1_000_000): self
    {
        if (self::$instance === null) {
            self::$instance = new self($debugMode, $logDir, $maxFileSize);
        }
        return self::$instance;
    }

    /**
     * Logs a message with a specific level.
     *
     * @param string $level The log level. Can be one of the following:
     *                      - Logger::LEVEL_ERROR
     *                      - Logger::LEVEL_INFO
     *                      - Logger::LEVEL_DEBUG
     *                      - Logger::LEVEL_WARNING
     * @param mixed $message The log message. Can be a string or an array.
     *                       If an array is provided, it will be converted to a string using print_r.
     *
     * @return void
     */
    public function log(string $level, $message): void
    {
        if (is_array($message)) {
            $message = print_r($message, true);
        }

        if (!$this->debugMode && $level === self::LEVEL_DEBUG) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');

        if ($this->debugMode) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            if (isset($trace[1])) {
                $caller = $trace[1];
                $file = $caller['file'] ?? 'unknown file';
                $line = $caller['line'] ?? 'unknown line';
                $message .= " (at $file:$line)";
            }
        }

        $formattedMessage = "[$timestamp] [$level] $message" . PHP_EOL;

        $logFile = $this->getLogFilePath();
        $this->rotateLogFileIfNeeded($logFile);
        file_put_contents($logFile, $formattedMessage, FILE_APPEND);
    }

    protected function getLogFilePath(): string
    {
        $date = date('Y-m-d');
        return "{$this->logDir}/$date.log";
    }

    /**
     * Rotates the log file at the given path if it has grown bigger than the maximum allowed size.
     *
     * The original file is renamed to a backup file with a timestamp, and a new empty file is created
     * in its place.
     *
     * @param string $filePath The path of the log file to rotate.
     */
    protected function rotateLogFileIfNeeded(string $filePath): void
    {
        if (file_exists($filePath) && filesize($filePath) >= $this->maxFileSize) {
            $timestamp = date('Ymd_His');
            $backupFile = $filePath . '.' . $timestamp . '.bak';
            rename($filePath, $backupFile);
        }
    }

    /**
     * Outputs the given log message to the console if the debug mode is enabled.
     *
     * The message is output using the console.log() method if the level is not recognized.
     * If the level is recognized, the respective console method is used (console.error() for
     * LEVEL_ERROR, console.info() for LEVEL_INFO, console.warn() for LEVEL_WARNING).
     *
     * The message is JSON encoded before being output to ensure it is valid JavaScript.
     *
     * @param string $level The level of the log message.
     * @param string $message The log message to output.
     */
    protected function outputToConsole(string $level, string $message): void
    {
        $src = '<script>';
        if ($level === self::LEVEL_ERROR) {
            $src .= 'console.error(';
        } elseif ($level === self::LEVEL_INFO) {
            $src .= 'console.info(';
        } elseif ($level === self::LEVEL_WARNING) {
            $src .= 'console.warn(';
        } else {
            $src .= 'console.log(';
        }

        $src .= json_encode($message, JSON_HEX_TAG + JSON_PRETTY_PRINT) . ');</script>';
        echo $src;
    }

    /**
     * Logs an error message.
     *
     * This method is a shortcut for logging messages with the error level.
     * The message can be a string or an array, which will be converted to a string.
     *
     * @param mixed $message The error message to log.
     *                       If an array is provided, it will be converted to a string using print_r.
     *
     * @return void
     */

    public function error($message): void
    {
        $this->log(self::LEVEL_ERROR, $message);
    }

    /**
     * Logs an informational message.
     *
     * This method is a shortcut for logging messages with the info level.
     * The message can be a string or an array, which will be converted to a string.
     *
     * @param mixed $message The informational message to log.
     *                       If an array is provided, it will be converted to a string using print_r.
     *
     * @return void
     */
    public function info($message): void
    {
        $this->log(self::LEVEL_INFO, $message);
    }

    /**
     * Logs a warning message.
     *
     * This method is a shortcut for logging messages with the warning level.
     * The message can be a string or an array, which will be converted to a string.
     *
     * @param mixed $message The warning message to log.
     *                       If an array is provided, it will be converted to a string using print_r.
     *
     * @return void
     */
    public function warning($message): void
    {
        $this->log(self::LEVEL_WARNING, $message);
    }

    /**
     * Logs a debug message.
     *
     * This method is a shortcut for logging messages with the debug level.
     * The message can be a string or an array, which will be converted to a string.
     *
     * @param mixed $message The debug message to log.
     *                       If an array is provided, it will be converted to a string using print_r.
     *
     * @return void
     */
    public function debug($message): void
    {
        $this->log(self::LEVEL_DEBUG, $message);
    }
}
