<?php

namespace Core\Auth;

use Core\Auth\User;
use Core\Auth\PasswordRecovery;
use Core\Auth\Group;
use Core\Auth\RegistrationRequest;
use Core\Auth\Permissions;
use Core\Core;
use Core\Logger;
use Core\Lang;
use Core\Router\Router;
use Utils\Util;

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
    private string $groupsFile;
    private string $registrationsFile;
    private Logger $logger;

    public function __construct()
    {
        $this->usersFile = DATA_CORE_AUTH . 'users.json';
        $this->legacyUsersFile = DATA_PLUGIN . 'users/users.json';
        $this->tokensFile = DATA_CORE_AUTH . 'pwd.json';
        $this->legacyTokensFile = DATA_PLUGIN . 'users/pwd.json';
        $this->groupsFile = DATA_CORE_AUTH . 'groups.json';
        $this->registrationsFile = DATA_CORE_AUTH . 'registrations.json';

        $this->bootstrapStorage();
        $this->bootstrapGroups();
        $this->bootstrapRegistrations();
        $this->logger = Core::getInstance()->getLogger();
    }

    /**
     * Authentifie un utilisateur.
     */
    public function login(string $mail, string $password, bool $useCookies = false): bool
    {
        if ($mail === '') {
            $this->logger->warning('Auth: login failed - empty email');
            return false;
        }

        if ($password === '') {
            $this->logger->warning('Auth: login failed - empty password for ' . $mail);
            return false;
        }

        $user = User::find('email', $mail);
        if ($user === null) {
            $this->logger->warning('Auth: login failed - user not found for ' . $mail);
            return false;
        }

        if (($user->status ?? 'active') !== 'active') {
            $this->logger->warning('Auth: login failed - account status "' . ($user->status ?? 'unknown') . '" for ' . $mail);
            return false;
        }

        if (!$this->verifyPassword($password, $user->pwd, $user)) {
            $this->logger->warning('Auth: login failed - wrong password for ' . $mail);
            return false;
        }

        $user->token = $this->generateToken();
        $user->save();

        $this->logon($user);
        $this->logger->info('Auth: user logged in - ' . $mail);

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
        return password_hash($data, PASSWORD_DEFAULT);
    }

    public function verifyPassword(string $password, string $hash, ?User $user = null): bool
    {
        if ($hash === '') {
            return false;
        }

        if (password_verify($password, $hash)) {
            if ($user !== null && password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                $user->pwd = $this->encrypt($password);
                $user->save();
            }
            return true;
        }

        if ($this->isLegacyHash($hash) && hash_hmac('sha1', $password, KEY) === $hash) {
            if ($user !== null) {
                $user->pwd = $this->encrypt($password);
                $user->save();
            }
            return true;
        }

        return false;
    }

    protected function isLegacyHash(string $hash): bool
    {
        return preg_match('/^[0-9a-f]{40}$/i', $hash) === 1;
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

    public function forceLogin(User $user): void
    {
        $user->token = $this->generateToken();
        $user->save();
        $this->logon($user);
    }

    private function bootstrapStorage(): void
    {
        $this->ensureDirectory(DATA_CORE_AUTH);

        $this->mirrorLegacyFile($this->legacyUsersFile, $this->usersFile);
        $this->mirrorLegacyFile($this->legacyTokensFile, $this->tokensFile);

        User::setFilePath($this->usersFile);
        Group::setFilePath($this->groupsFile);
        RegistrationRequest::setFilePath($this->registrationsFile);
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

    private function bootstrapGroups(): void
    {
        if (!file_exists($this->groupsFile)) {
            Util::writeJsonFile($this->groupsFile, []);
        }

        $groups = Util::readJsonFile($this->groupsFile, true);
        if (!is_array($groups) || count($groups) === 0) {
            $groups = [
                [
                    'id' => 1,
                    'slug' => 'admin',
                    'name' => 'Administrators',
                    'permissions' => [Permissions::ALL],
                    'system' => true,
                ],
                [
                    'id' => 2,
                    'slug' => 'moderator',
                    'name' => 'Moderators',
                    'permissions' => ['admin.access', 'pages.manage', 'media.manage'],
                    'system' => true,
                ],
                [
                    'id' => 3,
                    'slug' => 'member',
                    'name' => 'Members',
                    'permissions' => ['profile.view', 'profile.edit'],
                    'system' => true,
                ],
            ];
            Util::writeJsonFile($this->groupsFile, $groups);
        } else {
            $this->ensureEssentialGroups($groups);
        }
    }

    private function ensureEssentialGroups(array $groups): void
    {
        $slugs = array_map(fn ($group) => $group['slug'] ?? null, $groups);
        $changed = false;

        $ensure = function (array $definition) use (&$groups, &$slugs, &$changed): void {
            if (!in_array($definition['slug'], $slugs, true)) {
                $definition['id'] = $this->generateNextGroupId($groups);
                $groups[] = $definition;
                $slugs[] = $definition['slug'];
                $changed = true;
            }
        };

        $ensure([
            'slug' => 'admin',
            'name' => 'Administrators',
            'permissions' => [Permissions::ALL],
            'system' => true,
        ]);

        $ensure([
            'slug' => 'moderator',
            'name' => 'Moderators',
            'permissions' => ['admin.access', 'pages.manage', 'media.manage'],
            'system' => true,
        ]);

        $ensure([
            'slug' => 'member',
            'name' => 'Members',
            'permissions' => ['profile.view', 'profile.edit'],
            'system' => true,
        ]);

        if ($changed) {
            Util::writeJsonFile($this->groupsFile, $groups);
        }
    }

    private function generateNextGroupId(array $groups): int
    {
        $ids = array_map(fn ($group) => (int) ($group['id'] ?? 0), $groups);
        return empty($ids) ? 1 : max($ids) + 1;
    }

    private function bootstrapRegistrations(): void
    {
        if (!file_exists($this->registrationsFile)) {
            Util::writeJsonFile($this->registrationsFile, []);
        }
    }

    public function getGroups(): array
    {
        return Group::all();
    }

    public function getGroupBySlug(string $slug): ?Group
    {
        return Group::find('slug', $slug);
    }

    public function getDefaultGroupSlug(): string
    {
        return $this->coreConfig('registrationDefaultGroup') ?? 'member';
    }

    public function getDefaultGroupId(): int
    {
        $group = $this->getGroupBySlug($this->getDefaultGroupSlug());
        if ($group !== null) {
            return $group->attributes['id'];
        }
        $first = Group::first();
        return $first ? ($first->attributes['id'] ?? 1) : 1;
    }

    private function coreConfig(string $key)
    {
        return Core::getInstance()->getConfigVal($key);
    }

    public function isRegistrationEnabled(): bool
    {
        return (bool) $this->coreConfig('allowRegistrations');
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
            $this->logger->warning('Auth: auto-login failed - user not found for ' . $mail);
            setcookie('koAutoConnect', '/', 1, '/');
            return false;
        }

        if ($user->pwd !== $cryptedPwd) {
            $this->logger->warning('Auth: auto-login failed - password mismatch for ' . $mail);
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


