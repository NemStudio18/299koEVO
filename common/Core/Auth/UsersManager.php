<?php

namespace Core\Auth;

use Core\Auth\AuthService;
use Core\Auth\User;
use Core\Auth\PasswordRecovery;
use Core\Core;

/**
 * Façade statique conservée pour compatibilité.
 * Délègue toute la logique à AuthService (core).
 */
defined('ROOT') or exit('Access denied!');

class UsersManager
{
    protected static function service(): AuthService
    {
        return Core::getInstance()->auth();
    }

    public static function login(string $mail, string $password, bool $useCookies = false): bool
    {
        return self::service()->login($mail, $password, $useCookies);
    }

    public static function isLogged(): bool
    {
        return self::service()->isLogged();
    }

    public static function getCurrentUser(): ?User
    {
        return self::service()->getCurrentUser();
    }

    public static function encrypt(string $data): string
    {
        return self::service()->encrypt($data);
    }

    public static function verify(string $password, string $hash, ?User $user = null): bool
    {
        return self::service()->verifyPassword($password, $hash, $user);
    }

    public static function generateToken(): string
    {
        return self::service()->generateToken();
    }

    public static function passwordRecovery(): PasswordRecovery
    {
        return self::service()->getPasswordRecovery();
    }
}


