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
     * Recherche un utilisateur par email, username ou couple Nom_Prénom / Prénom_Nom.
     */
    public function findUserByIdentifier(string $identifier): ?User
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        // 1. Recherche par email direct
        $user = User::find('email', $identifier);
        if ($user !== null) {
            return $user;
        }

        // 2. Recherche par username direct
        $user = User::find('username', $identifier);
        if ($user !== null) {
            return $user;
        }

        // 3. Recherche avancée insensible à la casse et par combinaisons Nom_Prénom
        $allUsers = User::queryBuilder()->get();
        $lowerId = strtolower($identifier);
        $cleanId = strtolower(preg_replace('/[^a-z0-9]/i', '', $identifier));

        foreach ($allUsers as $u) {
            $uEmail = strtolower($u->email ?? '');
            $uUsername = strtolower($u->username ?? '');
            $uFirstname = strtolower($u->attributes['firstname'] ?? '');
            $uLastname = strtolower($u->attributes['lastname'] ?? '');

            if ($uEmail === $lowerId || $uUsername === $lowerId) {
                return $u;
            }

            // Combinaisons nom_prenom, prenom_nom, nom prenom, prenom nom, nom-prenom
            $combos = [
                $uLastname . '_' . $uFirstname,
                $uFirstname . '_' . $uLastname,
                $uLastname . ' ' . $uFirstname,
                $uFirstname . ' ' . $uLastname,
                $uLastname . '-' . $uFirstname,
                $uFirstname . '-' . $uLastname,
            ];

            foreach ($combos as $combo) {
                if ($combo !== '' && $combo !== '_' && $combo !== ' ' && $combo !== '-') {
                    if ($lowerId === $combo) {
                        return $u;
                    }
                }
            }

            // Correspondance sans ponctuation (ex: "dupontjean" ou "jeandupont")
            $cleanUser1 = strtolower(preg_replace('/[^a-z0-9]/i', '', $uLastname . $uFirstname));
            $cleanUser2 = strtolower(preg_replace('/[^a-z0-9]/i', '', $uFirstname . $uLastname));
            $cleanUser3 = strtolower(preg_replace('/[^a-z0-9]/i', '', $uUsername));

            if ($cleanId !== '' && ($cleanId === $cleanUser1 || $cleanId === $cleanUser2 || $cleanId === $cleanUser3)) {
                return $u;
            }
        }

        return null;
    }

    /**
     * Recherche une demande d'inscription en attente par identifiant ou email.
     */
    public function findPendingRegistrationByIdentifier(string $identifier): ?RegistrationRequest
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        $allRequests = RegistrationRequest::queryBuilder()->get();
        $lowerId = strtolower($identifier);
        $cleanId = strtolower(preg_replace('/[^a-z0-9]/i', '', $identifier));

        foreach ($allRequests as $r) {
            $rEmail = strtolower($r->attributes['email'] ?? '');
            $rUsername = strtolower($r->attributes['username'] ?? '');
            $rFirstname = strtolower($r->attributes['firstname'] ?? '');
            $rLastname = strtolower($r->attributes['lastname'] ?? '');

            if ($rEmail === $lowerId || $rUsername === $lowerId) {
                return $r;
            }

            $combos = [
                $rLastname . '_' . $rFirstname,
                $rFirstname . '_' . $rLastname,
                $rLastname . ' ' . $rFirstname,
                $rFirstname . ' ' . $rLastname,
            ];

            foreach ($combos as $combo) {
                if ($lowerId === $combo) {
                    return $r;
                }
            }

            $clean1 = strtolower(preg_replace('/[^a-z0-9]/i', '', $rLastname . $rFirstname));
            $clean2 = strtolower(preg_replace('/[^a-z0-9]/i', '', $rFirstname . $rLastname));
            if ($cleanId !== '' && ($cleanId === $clean1 || $cleanId === $clean2)) {
                return $r;
            }
        }

        return null;
    }

    /**
     * Authentifie un utilisateur avec retour détaillé sur la cause exacte du succès ou de l'échec.
     */
    public function loginWithDetails(string $identifier, string $password, bool $useCookies = false): array
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return [
                'success' => false,
                'reason' => 'empty_identifier',
                'message' => 'Veuillez saisir votre adresse email ou votre identifiant (Nom_Prénom).'
            ];
        }

        if ($password === '') {
            return [
                'success' => false,
                'reason' => 'empty_password',
                'message' => 'Veuillez renseigner votre mot de passe.'
            ];
        }

        $user = $this->findUserByIdentifier($identifier);

        // Utilisateur non trouvé parmi les comptes actifs
        if ($user === null) {
            $pending = $this->findPendingRegistrationByIdentifier($identifier);
            if ($pending !== null) {
                $vMode = $pending->attributes['validation_mode'] ?? 'email';
                $msg = ($vMode === 'admin')
                    ? "Votre compte a bien été enregistré mais est actuellement en attente d'approbation par un administrateur."
                    : "Votre compte est en attente de confirmation. Veuillez cliquer sur le lien reçu par email.";
                return [
                    'success' => false,
                    'reason' => 'pending_registration',
                    'message' => $msg
                ];
            }

            \Core\Storage\EmergencyLogger::log("AUTH_LOGIN: User not found for " . $identifier);
            $this->logger->warning('Auth: login failed - user not found for ' . $identifier);
            Core::getInstance()->callHook('auth:login-failed', ['email' => $identifier, 'reason' => 'user_not_found']);

            return [
                'success' => false,
                'reason' => 'user_not_found',
                'message' => "Aucun compte n'a été trouvé pour « " . htmlspecialchars($identifier) . " ». Vérifiez votre saisie ou créez un compte."
            ];
        }

        \Core\Storage\EmergencyLogger::log("AUTH_LOGIN: Found user " . $user->email . " (status=" . ($user->status ?? 'null') . ")");

        // Statut du compte
        if (($user->status ?? 'active') !== 'active') {
            $status = $user->status ?? 'inactif';
            $this->logger->warning('Auth: login failed - account status "' . $status . '" for ' . $identifier);
            Core::getInstance()->callHook('auth:login-failed', ['email' => $identifier, 'reason' => 'inactive_account', 'user_id' => $user->attributes['id']]);

            $msg = ($status === 'pending')
                ? "Votre compte est en attente d'approbation par un administrateur."
                : "Ce compte utilisateur est actuellement désactivé. Veuillez contacter l'administrateur.";

            return [
                'success' => false,
                'reason' => 'inactive_account',
                'message' => $msg
            ];
        }

        // Vérification du mot de passe
        if (!$this->verifyPassword($password, $user->pwd, $user)) {
            \Core\Storage\EmergencyLogger::log("AUTH_LOGIN: Invalid password for " . $identifier);
            $this->logger->warning('Auth: login failed - wrong password for ' . $identifier);
            Core::getInstance()->callHook('auth:login-failed', ['email' => $identifier, 'reason' => 'wrong_password', 'user_id' => $user->attributes['id']]);

            return [
                'success' => false,
                'reason' => 'wrong_password',
                'message' => "Mot de passe incorrect pour le compte « " . htmlspecialchars($identifier) . " ». Vous pouvez réinitialiser votre mot de passe ci-dessous."
            ];
        }

        // Security: Regenerate session ID to prevent fixation
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }

        $this->registerSession($user);
        \Core\Storage\EmergencyLogger::log("AUTH_LOGIN: Session registered for " . $user->email);

        $this->logon($user);
        \Core\Storage\EmergencyLogger::log("AUTH_LOGIN: Session updated email=" . $_SESSION['email'] . " token=" . $_SESSION['token']);
        $this->logger->info('Auth: user logged in - ' . $user->email);

        // Trigger user:login hook for gamification and others
        Core::getInstance()->callHook('user:login', ['user_id' => $user->attributes['id']]);

        if ($useCookies) {
            $this->setRememberCookies($user);
        }

        $this->registerFingerprint($user);

        return [
            'success' => true,
            'reason' => 'ok',
            'message' => 'Connexion réussie',
            'user' => $user
        ];
    }

    /**
     * Authentifie un utilisateur (compatibilité).
     */
    public function login(string $mail, string $password, bool $useCookies = false): bool
    {
        $result = $this->loginWithDetails($mail, $password, $useCookies);
        return $result['success'] ?? false;
    }

    /**
     * Indique si un utilisateur est connecté (session ou cookies).
     */
    public function isLogged(): bool
    {
        return $this->getCurrentUser() !== null;
    }

    private ?User $currentUserCache = null;
    private bool $currentUserChecked = false;

    /**
     * Retourne l'utilisateur courant ou null.
     */
    public function getCurrentUser(): ?User
    {
        if ($this->currentUserChecked) {
            return $this->currentUserCache;
        }

        // Si la session PHP n'est pas active, tenter la reconnexion automatique via cookie "Rester connecté"
        if (!isset($_SESSION['email'])) {
            if (isset($_COOKIE['koAutoConnect']) && is_string($_COOKIE['koAutoConnect']) && trim($_COOKIE['koAutoConnect']) !== '') {
                if ($this->loginByCookies()) {
                    return $this->currentUserCache;
                }
            }
            $this->markUserChecked(null);
            return null;
        }

        // On charge d'abord l'utilisateur RÉEL de la session
        /** @var User|null $realUser */
        $realUser = User::find('email', $_SESSION['email']);
        
        \Core\Storage\EmergencyLogger::log("getCurrentUser: Starting. SESSION[email]=" . ($_SESSION['email'] ?? 'NONE') . ", realUser found=" . ($realUser ? $realUser->username() : 'NO'));
        
        if (!$realUser) {
            \Core\Storage\EmergencyLogger::log("getCurrentUser: User not found in users.json for session email: " . $_SESSION['email']);
        }

        // Fallback pour les utilisateurs de test (Mode Démo)
        if ($realUser === null) {
            $testUsersFile = DATA_PLUGIN . 'demo/test_users.json';
            if (is_file($testUsersFile)) {
                $testUsersData = Util::readJsonFile($testUsersFile, true);
                if (is_array($testUsersData)) {
                    foreach ($testUsersData as $tu) {
                        if (($tu['email'] ?? '') === $_SESSION['email']) {
                            $realUser = new User($tu);
                            break;
                        }
                    }
                }
            }
        }

        if ($realUser === null) {
            $this->markUserChecked(null);
            return null;
        }

        // Mode Test Admin : Impersonnalisation uniquement si le "vrai" user est admin
        $testMode = \Core\Auth\AdminTestMode::getInstance();
        if ($testMode->isActive() && $testMode->getImpersonatedUserId()) {
            $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
            if ($evaluator->isAdmin(new \Core\Navigation\ConditionContext($realUser))) {
                /** @var User|null $targetUser */
                $targetUser = User::findPK($testMode->getImpersonatedUserId());
                
                // Fallback : chercher dans les utilisateurs de test
                if (!$targetUser) {
                    $testUsersFile = DATA_PLUGIN . 'demo/test_users.json';
                    if (is_file($testUsersFile)) {
                        $testUsersData = Util::readJsonFile($testUsersFile, true);
                        if (is_array($testUsersData)) {
                            foreach ($testUsersData as $tu) {
                                if (($tu['id'] ?? '') == $testMode->getImpersonatedUserId()) {
                                    $targetUser = new User();
                                    $targetUser->attributes = $tu;
                                    break;
                                }
                            }
                        }
                    }
                }
                
                if ($targetUser) {
                    \Core\Storage\EmergencyLogger::log("getCurrentUser: IMPERSONATION ACTIVE! targetUser found: " . $targetUser->username() . " (id: " . $targetUser->getId() . ")");
                    $this->markUserChecked($targetUser);
                    return $targetUser;
                } else {
                    \Core\Storage\EmergencyLogger::log("getCurrentUser: IMPERSONATION FAILED! targetUserId=" . $testMode->getImpersonatedUserId() . " not found.");
                }
            } else {
                \Core\Storage\EmergencyLogger::log("getCurrentUser: IMPERSONATION SKIPPED - realUser is NOT admin.");
            }
        }

        $user = $realUser;

        if (isset($_SESSION['token'])) {
            $sessionToken = $_SESSION['token'];
            $tokens = $user->tokens ?? [];
            if (!is_array($tokens)) {
                $tokens = $user->token ? [['token' => $user->token, 'created_at' => time()]] : [];
            }
            
            $validToken = null;
            $updatedTokens = [];
            $now = time();
            $ttl = (int)Core::getInstance()->getConfigVal('sessionLifetime') ?: 604800;
            $needsSave = false;

            foreach ($tokens as &$t) {
                // Vérifier si le token est une chaîne (legacy) ou un objet (nouveau format)
                $tokenValue = is_array($t) ? ($t['token'] ?? '') : $t;
                $createdAt = is_array($t) ? ($t['created_at'] ?? $now) : $now;

                // Nettoyage automatique des tokens expirés
                if ($createdAt + $ttl < $now) {
                    $needsSave = true;
                    continue;
                }

                if ($tokenValue === $sessionToken) {
                    $validToken = $t;
                    // Mettre à jour last_seen si c'est un tableau (seulement si plus de 5min d'écart)
                    if (is_array($t)) {
                        $lastSeen = $t['last_seen'] ?? 0;
                        if ($now - $lastSeen > 300) {
                            $t['last_seen'] = $now;
                            $t['ip_hash'] = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
                            $needsSave = true;
                        }
                    }
                }
                $updatedTokens[] = $t;
            }

            if ($validToken) {
                // Sauvegarder si nécessaire
                if ($needsSave || count($updatedTokens) !== count($tokens)) {
                    $user->tokens = $updatedTokens;
                    $user->save();
                }
                $this->markUserChecked($user);
                return $user;
            }
        }

        $this->markUserChecked(null);
        return null;
    }

    private function markUserChecked(?User $user): void
    {
        $this->currentUserCache = $user;
        $this->currentUserChecked = true;
    }

    public function clearUserCache(): void
    {
        $this->currentUserChecked = false;
        $this->currentUserCache = null;
    }

    /**
     * Hachage des données sensibles (mots de passe).
     */
    public function encrypt(string $data): string
    {
        return password_hash($data, PASSWORD_DEFAULT);
    }

    public function verifyPassword(string $password, ?string $hash, ?User $user = null): bool
    {
        if ($hash === null || $hash === '') {
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
        $this->registerSession($user);
        $this->registerFingerprint($user);
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
        $slugs = array_map(fn($group) => $group['slug'] ?? null, $groups);
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
        $ids = array_map(fn($group) => (int) ($group['id'] ?? 0), $groups);
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
        $_SESSION['token'] = $user->token; // Le dernier token généré par registerSession
        $this->markUserChecked($user);
    }

    /**
     * Déconnecte l'utilisateur en supprimant son token actuel de la liste.
     */
    public function logout(): bool
    {
        $user = $this->getCurrentUser();
        if ($user !== null) {
            $sessionToken = $_SESSION['token'] ?? null;
            $tokens = $user->tokens ?? [];
            if (is_array($tokens) && $sessionToken !== null) {
                // Retirer seulement le token de la session actuelle
                $user->tokens = array_values(array_filter($tokens, function($t) use ($sessionToken) {
                    $val = is_array($t) ? ($t['token'] ?? '') : $t;
                    return $val !== $sessionToken;
                }));
                
                // Si c'était le token 'principal' legacy, on le nettoie aussi
                if ($user->token === $sessionToken) {
                    $user->token = count($user->tokens) > 0 
                        ? (is_array($user->tokens[0]) ? $user->tokens[0]['token'] : $user->tokens[0]) 
                        : null;
                }
                $user->save();
            }
        }

        // Détruire la session PHP
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        @session_destroy();
        
        // Redémarrer une session propre pour les messages flash
        @session_start();
        unset($_SESSION['_csrf_token']);
        
        // Supprimer le cookie de connexion automatique
        setcookie('koAutoConnect', '', time() - 3600, '/');
        
        return true;
    }

    private function loginByCookies(): bool
    {
        if (empty($_COOKIE['koAutoConnect']) || !is_string($_COOKIE['koAutoConnect'])) {
            return false;
        }

        $raw = trim($_COOKIE['koAutoConnect']);
        $user = null;
        $matched = false;
        $secret = Core::getInstance()->getConfigVal('siteKey') ?: '299ko_auth_secret';

        // 1. Essai décodage JSON / base64 (format moderne sécurisé HMAC sans risque de collision '/')
        $decoded = @base64_decode($raw, true);
        if ($decoded !== false) {
            $data = @json_decode($decoded, true);
            if (is_array($data) && !empty($data['email']) && !empty($data['hash'])) {
                $user = User::find('email', $data['email']);
                if ($user !== null) {
                    $userPwd = $user->getPwd();
                    if ($userPwd) {
                        $expectedHash = hash_hmac('sha256', $user->email . ':' . $userPwd, $secret);
                        if (hash_equals($expectedHash, $data['hash'])) {
                            $matched = true;
                        }
                    }
                }
            }
        }

        // 2. Fallback format legacy (email//pwd avec explode limité à 2)
        if (!$matched) {
            $parts = explode('//', $raw, 2);
            $mail = $parts[0] ?? '';
            $cryptedPwd = $parts[1] ?? '';
            if ($mail && $cryptedPwd) {
                $user = User::find('email', $mail);
                if ($user !== null) {
                    $userPwd = $user->getPwd();
                    if ($userPwd && $userPwd === $cryptedPwd) {
                        $matched = true;
                    }
                }
            }
        }

        if (!$matched || $user === null) {
            $this->logger->warning('Auth: auto-login failed from cookie');
            setcookie('koAutoConnect', '', time() - 3600, '/');
            return false;
        }

        $this->registerSession($user);
        $this->logon($user);

        // Renouveler le cookie au format moderne sécurisé
        $this->setRememberCookies($user);

        // Trigger user:login hook for gamification and others (auto-login)
        Core::getInstance()->callHook('user:login', ['user_id' => $user->attributes['id']]);

        return true;
    }

    /**
     * Enregistre une nouvelle session pour l'utilisateur (Multi-session).
     */
    private function registerSession(User $user): void
    {
        $newToken = $this->generateToken();
        $tokens = $user->tokens ?? [];
        
        // Migration d'un ancien format token unique vers tableau multi-session
        if (!is_array($tokens)) {
            $oldToken = $user->token;
            $tokens = $oldToken ? [['token' => $oldToken, 'created_at' => time()]] : [];
        }

        $tokens[] = [
            'token' => $newToken,
            'created_at' => time(),
            'last_seen' => time(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'ip_hash' => hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1')
        ];

        // Limite de 10 sessions simultanées pour éviter que users.json ne gonfle indéfiniment
        if (count($tokens) > 10) {
            array_shift($tokens);
        }

        $user->tokens = $tokens;
        $user->token = $newToken; // Utilisé par logon() juste après
        
        // Empêcher la sauvegarde sur disque pour les utilisateurs fictifs
        if ((int)($user->attributes['id'] ?? 0) < 900000) {
            $user->save();
        }
    }

    private function setRememberCookies(User $user): void
    {
        $pwd = $user->getPwd();
        if (!$pwd) {
            return;
        }

        $secret = Core::getInstance()->getConfigVal('siteKey') ?: '299ko_auth_secret';
        $payload = base64_encode(json_encode([
            'email' => $user->email,
            'hash' => hash_hmac('sha256', $user->email . ':' . $pwd, $secret)
        ]));

        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                    || ($_SERVER['SERVER_PORT'] == 443)
                    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        $isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '.base') !== false;
        if ($isLocal) {
            $isSecure = false;
        }

        setcookie(
            'koAutoConnect',
            $payload,
            [
                'expires' => time() + 60 * 24 * 3600, // 60 jours
                'secure' => $isSecure,
                'httponly' => true,
                'path' => '/',
                'samesite' => 'Lax'
            ]
        );
    }

    /**
     * Enregistre l'empreinte de l'appareil et détecte les multi-comptes.
     */
    private function registerFingerprint(User $user): void
    {
        $hash = $this->getFingerprintHash();
        $userId = $user->attributes['id'] ?? null;

        if ($userId === null) return;

        // Détection multi-compte
        $otherUsers = UserFingerprint::findOtherUsers($hash, $userId);
        if (!empty($otherUsers)) {
            if (!$user->isSuspicious()) {
                $user->is_suspicious = true;
                $user->save();
                
                // Audit log (sans donner d'infos sensibles)
                $this->logAudit("Multi-account detected for user ID $userId. Shared fingerprint with user IDs: " . implode(', ', $otherUsers));
            }
        }

        // Sauvegarder l'empreinte pour cet utilisateur
        UserFingerprint::create($userId, $hash);

        // Nettoyage occasionnel (1 chance sur 100 à chaque login)
        if (mt_rand(1, 100) === 1) {
            UserFingerprint::cleanup(90);
        }
    }

    private function getFingerprintHash(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        return hash('sha256', $ip . $ua);
    }

    private function logAudit(string $message): void
    {
        $logPath = DATA . 'logs' . DS . 'auth_audit.log';
        $entry = "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;
        @file_put_contents($logPath, $entry, FILE_APPEND);
    }

    /**
     * Register routes for Auth module
     * @param Router $router
     */
    public function registerRoutes(Router $router): void
    {
        require_once COMMON . 'Core/Auth/routes.php';
    }

    /**
     * Get admin navigation entries for users module
     * @return array
     */
    public function getAdminNavEntries(): array
    {
        return [
            [
                'name' => 'users',
                'icon' => 'fa-regular fa-user',
                'label' => Lang::get('users.name')
            ]
        ];
    }
}


