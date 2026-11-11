<?php

namespace Auth\Controllers;

use Core\Controllers\PublicController;
use Core\Responses\PublicResponse;
use Core\Auth\User;

defined('ROOT') or exit('Access denied!');

class AuthPublicController extends PublicController
{
    private function getConfig(): array
    {
        $file = DATA_PLUGIN . 'auth' . DS . 'auth_conf.json';
        return \Utils\Util::readJsonFile($file, true) ?: [];
    }

    private function createState(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function facebookLogin()
    {
        $config = $this->getConfig();
        if (!$config['facebook_enabled'] || empty($config['facebook_app_id'])) {
            $this->core->error404();
        }

        $state = $this->createState();
        $_SESSION['auth_facebook_state'] = $state;
        $_SESSION['auth_facebook_state_ts'] = time();

        $router = \Core\Router\Router::getInstance();
        $callbackUrl = $this->getBaseUrl() . $router->generate('auth-facebook-callback');

        $url = 'https://www.facebook.com/v24.0/dialog/oauth?' . http_build_query([
            'client_id' => $config['facebook_app_id'],
            'redirect_uri' => $callbackUrl,
            'state' => $state,
            'scope' => 'email,public_profile'
        ]);

        header('Location: ' . $url);
        exit;
    }

    public function facebookCallback()
    {
        $config = $this->getConfig();
        if (!$config['facebook_enabled'] || empty($config['facebook_app_id']) || empty($config['facebook_app_secret'])) {
            $this->core->error404();
        }

        $state = $_GET['state'] ?? '';
        $storedState = $_SESSION['auth_facebook_state'] ?? '';
        $storedTs = $_SESSION['auth_facebook_state_ts'] ?? 0;

        if ($state !== $storedState) {
            die('CSRF');
        }
        if (time() - $storedTs > 300) { // 5 minutes validity
            die('State expired');
        }

        $code = $_GET['code'] ?? '';
        if (!$code) {
            die('No code');
        }

        // Échange code -> token
        $tokenUrl = 'https://graph.facebook.com/v24.0/oauth/access_token';
        $router = \Core\Router\Router::getInstance();
        $callbackUrl = $this->getBaseUrl() . $router->generate('auth-facebook-callback');

        $postData = http_build_query([
            'client_id' => $config['facebook_app_id'],
            'client_secret' => $config['facebook_app_secret'],
            'redirect_uri' => $callbackUrl,
            'code' => $code
        ]);

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postData
            ]
        ];
        $ctx = stream_context_create($opts);
        $response = file_get_contents($tokenUrl, false, $ctx);
        $tokenData = json_decode($response, true);
        if (!$tokenData || isset($tokenData['error'])) {
            die('Token error: ' . ($tokenData['error_description'] ?? 'Unknown'));
        }

        // Récup user info
        $userUrl = 'https://graph.facebook.com/me?fields=id,name,email&access_token=' . $tokenData['access_token'];
        $userData = json_decode(file_get_contents($userUrl), true);
        if (!$userData || isset($userData['error'])) {
            die('User error');
        }

        // Facebook doesn't always return 'email_verified', but usually trusted if email is present.
        // We can check if email is present.

        // Trouver ou créer user
        $this->loginOrRegisterUser($userData, 'facebook');
    }

    public function googleLogin()
    {
        $logger = $this->core->getLogger();
        $logger->log(\Core\Logger::LEVEL_DEBUG, "AuthPublicController::googleLogin - Début");

        $config = $this->getConfig();
        $logger->log(\Core\Logger::LEVEL_DEBUG, "AuthPublicController::googleLogin - Config: " . json_encode([
            'google_enabled' => $config['google_enabled'] ?? false,
            'has_client_id' => !empty($config['google_client_id'] ?? '')
        ]));

        if (!$config['google_enabled'] || empty($config['google_client_id'])) {
            $logger->log(\Core\Logger::LEVEL_WARNING, "AuthPublicController::googleLogin - Google non activé ou Client ID manquant");
            $this->core->error404();
        }

        $state = $this->createState();
        $_SESSION['auth_google_state'] = $state;
        $_SESSION['auth_google_state_ts'] = time();

        $router = \Core\Router\Router::getInstance();
        $callbackUrl = $this->getBaseUrl() . $router->generate('auth-google-callback');

        $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $config['google_client_id'],
            'redirect_uri' => $callbackUrl,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state
        ]);

        header('Location: ' . $url);
        exit;
    }

    public function googleCallback()
    {
        $config = $this->getConfig();
        if (!$config['google_enabled'] || empty($config['google_client_id']) || empty($config['google_client_secret'])) {
            $this->core->error404();
        }

        $state = $_GET['state'] ?? '';
        $storedState = $_SESSION['auth_google_state'] ?? '';
        $storedTs = $_SESSION['auth_google_state_ts'] ?? 0;

        if ($state !== $storedState) {
            die('CSRF');
        }
        if (time() - $storedTs > 300) { // 5 minutes validity
            die('State expired');
        }

        $code = $_GET['code'] ?? '';
        if (!$code) {
            die('No code');
        }

        // Échange code -> token
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $router = \Core\Router\Router::getInstance();
        $callbackUrl = $this->getBaseUrl() . $router->generate('auth-google-callback');

        $postData = http_build_query([
            'code' => $code,
            'client_id' => $config['google_client_id'],
            'client_secret' => $config['google_client_secret'],
            'redirect_uri' => $callbackUrl,
            'grant_type' => 'authorization_code'
        ]);

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postData
            ]
        ];
        $ctx = stream_context_create($opts);
        $response = file_get_contents($tokenUrl, false, $ctx);
        $tokenData = json_decode($response, true);
        if (!$tokenData || isset($tokenData['error'])) {
            die('Token error: ' . ($tokenData['error_description'] ?? 'Unknown'));
        }

        // Récup user info
        $userUrl = 'https://openidconnect.googleapis.com/v1/userinfo?access_token=' . $tokenData['access_token'];
        $userData = json_decode(file_get_contents($userUrl), true);
        if (!$userData || isset($userData['error'])) {
            die('User error');
        }

        if (empty($userData['email_verified'])) {
            die('Email not verified by Google');
        }

        // Trouver ou créer user
        $this->loginOrRegisterUser($userData, 'google');
    }

    private function loginOrRegisterUser(array $data, string $provider)
    {
        $providerId = $data['sub'] ?? $data['id']; // Google sub, FB id
        $email = $data['email'] ?? '';
        $name = $data['name'] ?? '';

        if (empty($email)) {
            \Utils\Show::msg('Email requis pour la connexion OAuth.', 'error');
            $this->core->redirect($this->router->generate('login'));
            return;
        }

        // Chercher user existant par email
        $existing = User::find('email', $email);

        if ($existing) {
            // Utilisateur existant

            // Vérifier si ce provider est DÉJÀ lié
            $providers = $existing->attributes['oauth_providers'] ?? [];
            if (isset($providers[$provider]) && $providers[$provider] === $providerId) {
                // Déjà lié et ID correspond : Connexion autorisée
                $this->core->auth()->forceLogin($existing);
                \Utils\Show::msg('Connexion réussie.', 'success');
            } else {
                // Email existe mais pas lié à ce compte OAuth >> DANGER / PREVENTION COLLISION
                \Utils\Show::msg('Un compte existe déjà avec cet email. Veuillez vous connecter avec votre mot de passe pour lier ce compte social.', 'error');
                $this->core->redirect($this->router->generate('login'));
                return;
            }
        } else {
            // Nouvel utilisateur : inscription automatique
            $username = $this->generateUniqueUsername($name ?: 'user_' . $providerId);

            $newUser = new User();
            $newUser->username = $username;
            $newUser->email = $email;
            $newUser->pwd = ''; // Pas de mot de passe pour OAuth
            $newUser->status = 'active';
            $newUser->attributes['oauth_providers'] = [$provider => $providerId];

            // Assigner le groupe par défaut
            $defaultGroupSlug = $this->core->auth()->getDefaultGroupSlug();
            if ($defaultGroupSlug) {
                $group = $this->core->auth()->getGroupBySlug($defaultGroupSlug);
                if ($group) {
                    $newUser->group_id = $group->attributes['id'];
                }
            }

            $newUser->save();
            
            // Déclencher le hook de domaine (Découplé)
            \Core\Core::getInstance()->callHook('auth:registered', [
                'user' => $newUser,
                'provider' => $provider
            ]);

            // Connecter l'utilisateur
            $this->core->auth()->forceLogin($newUser);
            \Utils\Show::msg('Inscription et connexion réussies.', 'success');
        }

        // Redirection vers le profil ou la page d'accueil
        $this->core->redirect($this->router->generate('profile'));
    }

    private function generateUniqueUsername(string $base): string
    {
        $base = preg_replace('/[^a-z0-9_]/i', '', $base);
        $base = strtolower($base);
        if (empty($base)) {
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
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        
        if (!$evaluator->canAccess(new \Core\Navigation\GenericResource('auth:personal'), $ctx)) {
            http_response_code(403);
            exit;
        }

        $theme = $_POST['theme_preference'] ?? '';
        if (!in_array($theme, ['dark', 'light'])) {
            http_response_code(400);
            exit;
        }

        $user = $this->core->auth()->getCurrentUser();
        $user->attributes['theme_preference'] = $theme;
        
        if ($user->save()) {
            // Update session user to reflect changes immediately
            $_SESSION['user'] = $user;
            echo json_encode(['status' => 'success']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error']);
        }
        exit;
    }
}
