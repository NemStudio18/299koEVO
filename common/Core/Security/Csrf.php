<?php

namespace Core\Security;

/**
 * Minimal, framework-agnostic CSRF manager.
 *
 * Usage:
 * Csrf::start();
 * $token = Csrf::token();
 * Csrf::validate($providedToken) // boolean
 */
class Csrf
{
    private const SESSION_KEY = '_csrf_token';
    private const SESSION_TIME = '_csrf_time';

    /**
     * Ensure session started, without coupling to any framework.
     */
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        if (!isset($_SESSION[self::SESSION_KEY])) {
            self::regenerate();
        }
    }

    /**
     * Get current token, generate one if missing.
     */
    public static function token(): string
    {
        self::start();
        return (string)($_SESSION[self::SESSION_KEY] ?? '');
    }

    /**
     * Regenerate token (rotate).
     */
    public static function regenerate(): void
    {
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        $_SESSION[self::SESSION_TIME] = time();
    }

    /**
     * Validate a provided token.
     */
    public static function validate(?string $providedToken, bool $rotateOnSuccess = true): bool
    {
        self::start();
        $valid = is_string($providedToken)
            && hash_equals($_SESSION[self::SESSION_KEY] ?? '', $providedToken);
        if ($valid && $rotateOnSuccess) {
            self::regenerate();
        }
        return $valid;
    }

    public static function hiddenField(): string
    {
        $token = self::token();
        $safeToken = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_csrf" value="' . $safeToken . '">';
    }
}

