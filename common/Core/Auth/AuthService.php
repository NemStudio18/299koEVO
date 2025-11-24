<?php

namespace Core\Auth;

use Core\Auth\User;
use Core\Auth\PasswordRecovery;
use Utils\Util;
use Core\Router\Router;
use Core\Lang;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier
 *
 * AuthService centralise la gestion des utilisateurs, des sessions
 * et des artefacts d'authentification au sein du core.
 */
defined('ROOT') or exit('Access denied!');

class AuthService
{
    private string $usersFile;
    private string $legacyUsersFile;
    private string $tokensFile;
    private string $legacyTokensFile;

    public function __construct()
    {
        $this->usersFile = DATA_CORE_AUTH . 'users.json';
        $this->legacyUsersFile = DATA_PLUGIN . 'users/users.json';
        $this->tokensFile = DATA_CORE_AUTH . 'pwd.json';
        $this->legacyTokensFile = DATA_PLUGIN . 'users/pwd.json';

        $this->bootstrapStorage();
    }

    /**
     * Authentifie un utilisateur.
     */
    public function login(string $mail, string $password, bool $useCookies = false): bool
    {
        $user = User::find('email', $mail);
        if ($user === null) {
            return false;
        }

        if ($user->pwd !== $this->encrypt($password)) {
            return false;
        }

        $user->token = $this->generateToken();
        $user->save();

        $this->logon($user);

        if ($useCookies) {
            $this->setRememberCookies($user);
        }

        return true;
    }

    /**
     * Indique si un utilisateur est connecté (session ou cookies).
     */
    public function isLogged(): bool
    {
        if ($this->getCurrentUser() === null) {
            if (isset($_COOKIE['koAutoConnect']) && is_string($_COOKIE['koAutoConnect'])) {
                return $this->loginByCookies();
            }

            return false;
        }

        return true;
    }

    /**
     * Retourne l'utilisateur courant ou null.
     */
    public function getCurrentUser(): ?User
    {
        if (!isset($_SESSION['email'])) {
            return null;
        }

        $user = User::find('email', $_SESSION['email']);
        if ($user !== null && isset($_SESSION['token']) && $_SESSION['token'] === $user->token) {
            return $user;
        }

        return null;
    }

    /**
     * Hachage des données sensibles (mots de passe).
     */
    public function encrypt(string $data): string
    {
        return hash_hmac('sha1', $data, KEY);
    }

    /**
     * Génère un token pseudo-aléatoire.
     */
    public function generateToken(): string
    {
        return sha1(uniqid(mt_rand(), true));
    }

    /**
     * Fournit un gestionnaire de récupération de mot de passe aligné sur le nouveau stockage.
     */
    public function getPasswordRecovery(): PasswordRecovery
    {
        return new PasswordRecovery($this->tokensFile, $this->legacyTokensFile);
    }

    private function bootstrapStorage(): void
    {
        $this->ensureDirectory(DATA_CORE_AUTH);

        $this->mirrorLegacyFile($this->legacyUsersFile, $this->usersFile);
        $this->mirrorLegacyFile($this->legacyTokensFile, $this->tokensFile);

        User::setFilePath($this->usersFile);
    }

    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    private function mirrorLegacyFile(string $legacy, string $target): void
    {
        if (file_exists($target)) {
            return;
        }

        if (file_exists($legacy)) {
            $payload = Util::readJsonFile($legacy, true);
            Util::writeJsonFile($target, $payload);
            return;
        }

        Util::writeJsonFile($target, []);
    }

    private function logon(User $user): void
    {
        $_SESSION['email'] = $user->email;
        $_SESSION['token'] = $user->token;
    }

    private function loginByCookies(): bool
    {
        $parts = explode('//', $_COOKIE['koAutoConnect']);
        $mail = $parts[0] ?? '';
        $cryptedPwd = $parts[1] ?? '';

        $user = User::find('email', $mail);
        if ($user === null) {
            setcookie('koAutoConnect', '/', 1, '/');
            return false;
        }

        if ($user->pwd !== $cryptedPwd) {
            setcookie('koAutoConnect', '/', 1, '/');
            return false;
        }

        $user->token = $this->generateToken();
        $user->save();
        $this->logon($user);

        return true;
    }

    private function setRememberCookies(User $user): void
    {
        setcookie(
            'koAutoConnect',
            $user->email . '//' . $user->pwd,
            [
                'expires' => time() + 60 * 24 * 3600,
                'secure' => true,
                'httponly' => true,
                'path' => '/'
            ]
        );
    }

    /**
     * Register routes for Auth module
     * @param Router $router
     */
    public function registerRoutes(Router $router): void {
        require_once COMMON . 'Core/Auth/routes.php';
    }

    /**
     * Get admin navigation entries for users module
     * @return array
     */
    public function getAdminNavEntries(): array {
        return [
            [
                'name' => 'users',
                'icon' => 'fa-regular fa-user',
                'label' => Lang::get('users.name')
            ]
        ];
    }
}


