<?php

namespace SearchEngine\Services;

use Core\Core;
use Core\Logger;

defined('ROOT') or exit('Access denied!');

/**
 * Utilitaire de logging pour SearchEVO
 * Gère le basculement entre le logger du CMS et error_log
 */
class SearchLogger
{
    /**
     * @param string $message
     * @param string $level Use Core\Logger::LEVEL_* constants
     */
    public static function log(string $message, string $level = Logger::LEVEL_DEBUG): void
    {
        $fullMessage = "SearchEVO: " . $message;

        if (class_exists('\Core\Core')) {
            try {
                $core = Core::getInstance();
                if ($core) {
                    $logger = $core->getLogger();
                    if ($logger) {
                        $logger->log($level, $fullMessage);
                        return;
                    }
                }
            } catch (\Throwable $e) {
                // Ignore errors during logging
            }
        }

        // Fallback or CLI mode
        error_log("[" . $level . "] " . $fullMessage);
    }

    public static function debug(string $message): void
    {
        if (class_exists('\Core\Core')) {
            $core = Core::getInstance();
            if ($core && !$core->getConfigVal('debug')) {
                return;
            }
        }
        self::log($message, Logger::LEVEL_DEBUG);
    }

    public static function info(string $message): void
    {
        self::log($message, Logger::LEVEL_INFO);
    }

    public static function warning(string $message): void
    {
        self::log($message, Logger::LEVEL_WARNING);
    }

    public static function error(string $message): void
    {
        self::log($message, Logger::LEVEL_ERROR);
    }
}
