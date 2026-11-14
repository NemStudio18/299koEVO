<?php

namespace OAuth\Controllers;

use Core\Controllers\PublicController;
use Core\Navigation\AccessPolicyEvaluator;
use Core\Navigation\ConditionContext;
use Core\Navigation\GenericResource;
use Core\Router\Router;
use Core\Auth\User;
use Utils\Show;
use Utils\Util;

defined('ROOT') or exit('Access denied!');

class OAuthPublicController extends PublicController
{
    private function getConfig(): array
    {
        $file = DATA_PLUGIN . 'oauth' . DS . 'config.json';
        $legacyFile = DATA_PLUGIN . 'auth' . DS . 'auth_conf.json';

        if (is_file($file)) {
            return Util::readJsonFile($file, true) ?: [];
        }
        if (is_file($legacyFile)) {
            return Util::readJsonFile($legacyFile, true) ?: [];
        }
        return [];
    }

    public function passwordRecovery()
    {
        $currentUser = \Core\Auth\UsersManager::getCurrentUser();
        if ($currentUser) {
            return $this->core->redirect('/profile?tab=security');
        }
        return $this->core->redirect('/users/lost-password');
    }

    private function createState(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function facebookLogin()
    {
        $config = $this->getConfig();
        if (empty($config['facebook_enabled']) || empty($config['facebook_app_id'])) {
            $this->core->error404();
            return;
        }

        $state = $this->createState();
        $_SESSION['oauth_facebook_state'] = $state;
        $_SESSION['oauth_facebook_state_ts'] = time();

        $router = Router::getInstance();
        $callbackUrl = $this->getBaseUrl() . $router->generate('oauth-facebook-callback');

        $url = 'https://www.facebook.com/v24.0/dialog/oauth?' . http_build_query([
            'client_id'     => $config['facebook_app_id'],
            'redirect_uri'  => $callbackUrl,
            'state'         => $state,
            'scope'         => 'email,public_profile'
        ]);

        header('Location: ' . $url);
        exit;
    }

    public function facebookCallback()
    {
        $config = $this->getConfig();
        if (empty($config['facebook_enabled']) || empty($config['facebook_app_id']) || empty($config['facebook_app_secret'])) {
            $this->core->error404();
            return;
        }

        $state = $_GET['state'] ?? '';
        $storedState = $_SESSION['oauth_facebook_state'] ?? ($_SESSION['auth_facebook_state'] ?? '');
        $storedTs = $_SESSION['oauth_facebook_state_ts'] ?? ($_SESSION['auth_facebook_state_ts'] ?? 0);

        if ($state === '' || $state !== $storedState) {
            Show::msg('Échec de validation de sécurité (CSRF invalide).', 'error');
            $this->core->redirect($this->router->generate('login'));
            return;
        }
        if (time() - $storedTs > 300) {
            Show::msg('Session OAuth expirée. Veuillez réessayer.', 'error');
            $this->core->redirect($this->router->generate('login'));
            return;
        }

        $code = $_GET['code'] ?? '';
        if (!$code) {
            Show::msg('Code d\'autorisation manquant.', 'error');
            $this->core->redirect($this->router->generate('login'));
            return;
        }

        $tokenUrl = 'https://graph.facebook.com/v24.0/oauth/access_token';
        $router = Router::getInstance();
        $callbackUrl = $this->getBaseUrl() . $router->generate('oauth-facebook-callback');

        $postData = http_build_query([
            'client_id'     => $config['facebook_app_id'],
            'client_secret' => $config['facebook_app_secret'],
            'redirect_uri'  => $callbackUrl,
            'code'          => $code
        ]);

        $opts = [
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postData,
                'timeout' => 10
            ]
        ];
        $ctx = stream_context_create($opts);
        $response = @file_get_contents($tokenUrl, false, $ctx);
        $tokenData = $response ? json_decode($response, true) : null;
        if (!$tokenData || isset($tokenData['error'])) {
            Show::msg('Erreur lors de la négociation du jeton Facebook.', 'error');
            $this->core->redirect($this->router->generate('login'));
            return;
        }

        $userUrl = 'https://graph.facebook.com/me?fields=id,name,email&access_token=' . urlencode($tokenData['access_token'] ?? '');
        $userResponse = @file_get_contents($userUrl);
        $userData = $userResponse ? json_decode($userResponse, true) : null;
        if (!$userData || isset($userData['error'])) {
            Show::msg('Impossible de récupérer votre profil Facebook.', 'error');
            $this->core->redirect($this->router->generate('login'));
            return;
        }

        $this->loginOrRegisterUser($userData, 'facebook');
    }

    public function googleLogin()
    {
        $config = $this->getConfig();
        if (empty($config['google_enabled']) || empty($config['google_client_id'])) {
            $this->core->error404();
            return;
        }

        $state = $this->createState();
        $_SESSION['oauth_google_state'] = $state;
        $_SESSION['oauth_google_state_ts'] = time();

        $router = Router::getInstance();
        $callbackUrl = $this->getBaseUrl() . $router->generate('oauth-google-callback');

        $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id'             => $config['google_client_id'],
            'redirect_uri'          => $callbackUrl,
            'response_type'         => 'code',
            'scope'                 => 'openid email profile',
            'state'                 => $state,
            'access_type'           => 'online',
            'prompt'                => 'select_account'
        ]);

        header('Location: ' . $url);
        exit;
    }

    public function googleCallback()
    {
        $config = $this->getConfig();
        if (empty($config['google_enabled']) || empty($config['google_client_id']) || empty($config['google_client_secret'])) {
            $this->core->error404();
            return;
        }

        $state = $_GET['state'] ?? '';
        $storedState = $_SESSION['oauth_google_state'] ?? ($_SESSION['auth_google_state'] ?? '');
        $storedTs = $_SESSION['oauth_google_state_ts'] ?? ($_SESSION['auth_google_state_ts'] ?? 0);

        if ($state === '' || $state !== $storedState) {
            Show::msg('Échec de validation de sécurité Google (CSRF invalide).', 'error');
            $this->core->redirect($this->router->generate('login'));
            return;
        }
        if (time() - $storedTs > 300) {
            Show::msg('Session Google OAuth expirée. Veuillez réessayer.', 'error');
            $this->core->redirect($this->router->generate('login'));
            return;
        }

        $code = $_GET['code'] ?? '';
        if (!$code) {
            Show::msg('Code d\'autorisation Google manquant.', 'error');
            $this->core->redirect($this->router->generate('login'));
            return;
        }

        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $router = Router::getInstance();
        $callbackUrl = $this->getBaseUrl() . $router->generate('oauth-google-callback');

        $postData = http_build_query([
            'code'          => $code,
            'client_id'     => $config['google_client_id'],
            'client_secret' => $config['google_client_secret'],
            'redirect_uri'  => $callbackUrl,
            'grant_type'    => 'authorization_code'
        ]);

        $opts = [
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postData,
                'timeout' => 10
            ]
        ];
        $ctx = stream_context_create($opts);
        $response = @file_get_contents($tokenUrl, false, $ctx);
        $tokenData = $response ? json_decode($response, true) : null;
        if (!$tokenData || isset($tokenData['error'])) {
            Show::msg('Erreur lors de la négociation du jeton Google.', 'error');
            $this->core->redirect($this->router->generate('login'));
            return;
        }

        $accessToken = $tokenData['access_token'] ?? '';
        $userUrl = 'https://openidconnect.googleapis.com/v1/userinfo';
        $opts = [
            'http' => [
                'method'  => 'GET',
                'header'  => 'Authorization: Bearer ' . $accessToken,
                'timeout' => 10
            ]
        ];
        $ctx = stream_context_create($opts);
        $userResponse = @file_get_contents($userUrl, false, $ctx);
        $userData = $userResponse ? json_decode($userResponse, true) : null;
        if (!$userData || isset($userData['error'])) {
            Show::msg('Impossible de récupérer votre profil Google.', 'error');
            $this->core->redirect($this->router->generate('login'));
            return;
        }

        if (empty($userData['email_verified'])) {
            Show::msg('Votre adresse email n\'est pas vérifiée par Google.', 'error');
            $this->core->redirect($this->router->generate('login'));
            return;
        }

        $this->loginOrRegisterUser($userData, 'google');
    }

    private function loginOrRegisterUser(array $data, string $provider)
    {
        $providerId = (string) ($data['sub'] ?? ($data['id'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $name = trim((string) ($data['name'] ?? ''));

        if ($email === '') {
            Show::msg('Adresse email requise pour la connexion OAuth.', 'error');
            $this->core->redirect($this->router->generate('login'));
            return;
        }

        // Recherche d'un compte existant avec le même email
        $existing = User::find('email', $email);

        if ($existing) {
            $providers = $existing->attributes['oauth_providers'] ?? [];
            if (!is_array($providers)) {
                $providers = [];
            }
            if (isset($providers[$provider]) && (string)$providers[$provider] === $providerId) {
                // Déjà associé : connexion directe
                $this->core->auth()->forceLogin($existing);
                Show::msg('Connexion réussie.', 'success');
            } else {
                // Si l'utilisateur est déjà connecté, associer ce compte
                $currentUser = $this->core->auth()->getCurrentUser();
                if ($currentUser && (string)$currentUser->getId() === (string)$existing->getId()) {
                    $providers[$provider] = $providerId;
                    $existing->attributes['oauth_providers'] = $providers;
                    $existing->save();
                    Show::msg('Compte ' . ucfirst($provider) . ' associé avec succès.', 'success');
                } else {
                    Show::msg('Un compte existe déjà avec cette adresse email. Connectez-vous avec votre mot de passe pour associer votre compte social.', 'warning');
                    $this->core->redirect($this->router->generate('login'));
                    return;
                }
            }
        } else {
            // Création automatique du compte
            $username = $this->generateUniqueUsername($name ?: 'user_' . substr($providerId, 0, 8));

            $newUser = new User();
            $newUser->username = $username;
            $newUser->email = $email;
            $newUser->pwd = ''; // Aucun mot de passe local requis
            $newUser->status = 'active';
            $newUser->attributes['oauth_providers'] = [$provider => $providerId];

            $defaultGroupSlug = $this->core->auth()->getDefaultGroupSlug();
            if ($defaultGroupSlug) {
                $group = $this->core->auth()->getGroupBySlug($defaultGroupSlug);
                if ($group) {
                    $newUser->group_id = $group->attributes['id'];
                }
            }

            $newUser->save();

            // Hook notification inscription
            $this->core->callHook('auth:registered', [
                'user'     => $newUser,
                'provider' => $provider
            ]);

            $this->core->auth()->forceLogin($newUser);
            Show::msg('Bienvenue ! Votre compte a été créé avec succès.', 'success');
        }

        $this->core->redirect($this->router->generate('profile'));
    }

    private function generateUniqueUsername(string $base): string
    {
        $base = preg_replace('/[^a-z0-9_]/i', '', $base);
        $base = strtolower((string) $base);
        if ($base === '') {
            $base = 'user';
        }

        $username = $base;
        $counter = 1;
        while (User::find('username', $username) !== null) {
            $username = $base . '_' . $counter;
            $counter++;
        }

        return $username;
    }

    private function getBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }

    public function saveThemePreference()
    {
        $evaluator = AccessPolicyEvaluator::getInstance();
        $ctx = new ConditionContext($this->user);

        if (!$evaluator->canAccess(new GenericResource('oauth:personal'), $ctx)) {
            http_response_code(403);
            echo json_encode(['status' => 'forbidden']);
            exit;
        }

        $theme = $_POST['theme_preference'] ?? '';
        if (!in_array($theme, ['dark', 'light', 'auto'], true)) {
            http_response_code(400);
            echo json_encode(['status' => 'bad_request']);
            exit;
        }

        $user = $this->core->auth()->getCurrentUser();
        if ($user) {
            $user->attributes['theme_preference'] = $theme;
            if ($user->save()) {
                $_SESSION['user'] = $user;
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success']);
                exit;
            }
        }

        http_response_code(500);
        echo json_encode(['status' => 'error']);
        exit;
    }
}
