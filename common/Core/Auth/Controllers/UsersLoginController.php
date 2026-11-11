<?php

namespace Core\Auth\Controllers;

use Core\Controllers\PublicController;
use Core\Responses\StringResponse;
use Core\Responses\PublicResponse;
use Core\Auth\UsersManager;
use Core\Auth\User;
use Core\Auth\PasswordRecovery;
use Core\Auth\RegistrationRequest;
use Core\Plugin\PluginsManager;
use Core\Security\Csrf;
use Utils\Show;
use Core\Lang;
use Utils\Util;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 *
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class UsersLoginController extends PublicController
{

    public function login()
    {
        // Traiter les messages personnalisés pour les redirections d'actions (Phase Guest Actions)
        $msgType = $_GET['msg'] ?? null;
        $poiName = $_GET['poi_name'] ?? null;
        $returnUrl = $_GET['return_url'] ?? $_GET['redirect'] ?? null;
        
        // Stocker l'URL de retour en session pour rediriger après connexion
        if ($returnUrl) {
            $_SESSION['login_return_url'] = $returnUrl;
        }
        
        if ($msgType) {
            $poiLabel = $poiName ? htmlspecialchars(urldecode($poiName)) : 'cet élément';
            switch ($msgType) {
                case 'participate':
                    Show::msg("Pour confirmer votre participation, veuillez vous connecter ou créer un compte.", 'info');
                    break;
                case 'interest':
                    Show::msg("Pour suivre cet événement, connectez-vous ou créez un compte.", 'info');
                    break;
                case 'like':
                case 'react':
                    Show::msg("Pour réagir à cette publication, veuillez vous connecter ou créer un compte.", 'info');
                    break;
                case 'comment':
                    Show::msg("Pour publier un commentaire, veuillez vous connecter ou créer un compte.", 'info');
                    break;
                case 'favorite':
                case 'subscribe':
                    Show::msg("Pour vous abonner à <strong>" . $poiLabel . "</strong>, veuillez vous connecter ou créer un compte.", 'info');
                    break;
                case 'report':
                    Show::msg("Pour signaler un problème sur <strong>" . $poiLabel . "</strong>, merci de vous connecter ou de créer un compte.", 'info');
                    break;
                case 'claim':
                    Show::msg("Pour revendiquer <strong>" . $poiLabel . "</strong>, merci de vous connecter ou de créer un compte.", 'info');
                    break;
                case 'edit':
                    Show::msg("Pour proposer une modification sur <strong>" . $poiLabel . "</strong>, merci de vous connecter ou de créer un compte.", 'info');
                    break;
            }
        }

        $response = new PublicResponse();
        $response->setTitle(Lang::get('core-connection'));
        $response->hidePageTitle(true);
        $tpl = $response->createPluginTemplate('users', 'login');
        $tpl->set('loginLink', $this->router->generate('login-send'));
        $tpl->set('lostLink', $this->router->generate('lost-password'));
        $this->attachAntispam($tpl, 'antispamLogin');
        $response->addTemplate($tpl);
        return $response;
    }

    public function register()
    {
        $this->ensureRegistrationEnabled();
        $response = new PublicResponse();
        $response->setTitle(Lang::get('users-registration-title'));
        $response->hidePageTitle(true);
        $tpl = $response->createPluginTemplate('users', 'register');
        $tpl->set('registerLink', $this->router->generate('register-send'));
        $this->attachAntispam($tpl, 'antispamRegister');
        $response->addTemplate($tpl);
        return $response;
    }

    public function registerSend()
    {
        $this->ensureRegistrationEnabled();
        if (!Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg(Lang::get("users.bad-credentials"), 'error');
            $this->core->redirect($this->router->generate('register'));
        }
        if (($_POST['_hp_website'] ?? '') !== '') {
            return $this->register();
        }
        if (!$this->isAntispamValid('antispamRegister')) {
            Show::msg(Lang::get('antispam.invalid-captcha') ?: 'Antispam invalide, veuillez réessayer.', 'error');
            $this->core->redirect($this->router->generate('register'));
            return;
        }

        // Hook de validation pour les plugins (ex: Auth pour les checkboxes)
        $this->core->callHook('users:register-validation');

        $lastname = trim($_POST['lastname'] ?? '');
        $firstname = trim($_POST['firstname'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $username = trim($_POST['username'] ?? '');

        if ($username === '' && ($firstname !== '' || $lastname !== '')) {
            $username = trim($firstname . ' ' . $lastname);
        }

        if ($email === '' || $password === '' || mb_strlen($username) < 3 || mb_strlen($username) > 60) {
            Show::msg('Veuillez renseigner un pseudo (3 à 60 caractères), une adresse email valide et un mot de passe.', 'error');
            return $this->register();
        }

        if ($password !== $passwordConfirm) {
            Show::msg(Lang::get('users.registration-password-mismatch'), 'error');
            return $this->register();
        }

        if (strlen($password) < 8) {
            Show::msg(Lang::get('users.registration-password-length'), 'error');
            return $this->register();
        }

        if ($this->emailExists($email)) {
            Show::msg(Lang::get('users.registration-email-exists'), 'error');
            return $this->register();
        }

        if ($this->usernameExists($username)) {
            Show::msg(Lang::get('users.registration-username-exists') ?: 'Ce pseudo est déjà utilisé.', 'error');
            return $this->register();
        }

        $this->cleanupPendingRequests($email);

        $validationMode = $this->core->getConfigVal('registrationValidationMode') ?? 'email';
        
        if ($validationMode === 'none') {
            // Mode none : inscription directe sans vérification
            $user = new User();
            $user->username = $username;
            $user->email = $email;
            $user->pwd = UsersManager::encrypt($password);
            $user->attributes['firstname'] = $firstname;
            $user->attributes['lastname'] = $lastname;
            $groupSlug = $this->core->auth()->getDefaultGroupSlug();
            if ($groupSlug && $this->core->auth()->getGroupBySlug($groupSlug) !== null) {
                $user->group_id = $this->core->auth()->getGroupBySlug($groupSlug)->attributes['id'];
            }
            $user->status = 'active';
            $user->permissions = [];
            $user->attributes['created_at'] = date('Y-m-d H:i:s');
            $user->save();
            $this->core->callHook('users:after-registration-success', ['user' => $user]);

            $this->core->auth()->forceLogin($user);
            Show::msg(Lang::get('users.registration-success'), 'success');
            $this->core->redirect($this->router->generate('profile'));
        } else {
            // Modes email ou admin : créer une demande d'inscription
            $registration = new RegistrationRequest();
            $registration->token = sha1(uniqid(mt_rand(), true));
            $registration->username = $username;
            $registration->email = $email;
            $registration->pwd = UsersManager::encrypt($password);
            $registration->firstname = $firstname;
            $registration->lastname = $lastname;
            $registration->group_slug = $this->core->auth()->getDefaultGroupSlug();
            $registration->permissions = [];
            $registration->created_at = time();
            // L'expiration ne s'applique que pour la validation par email (token)
            // Pour la validation admin, pas d'expiration (0 = pas d'expiration)
            $registration->expires_at = ($validationMode === 'email') ? (time() + 86400) : 0;
            $registration->validation_mode = $validationMode;
            $registration->status = ($validationMode === 'admin') ? 'pending' : 'pending_email';
            $registration->save();

            if ($validationMode === 'email') {
                // Mode email : envoyer le token par email
                if ($this->sendRegistrationMail($registration)) {
                    Show::msg(Lang::get('users.registration-mail-sent'), 'success');
                } else {
                    Show::msg(Lang::get('users.registration-mail-error'), 'error');
                }
            } else {
                // Mode admin : en attente de validation par un administrateur
                Show::msg(Lang::get('users.registration-pending-admin'), 'success');
            }

            $this->core->redirect($this->router->generate('home'));
        }
    }

    public function loginSend()
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg(Lang::get("users.bad-credentials"), 'error');
            $this->core->redirect($this->router->generate('login'));
        }
        if (!$this->isAntispamValid('antispamLogin')) {
            Show::msg(Lang::get('antispam.invalid-captcha') ?: 'Antispam invalide, veuillez réessayer.', 'error');
            $this->core->redirect($this->router->generate('login'));
            return;
        }
        $identifier = trim($_POST['adminEmail'] ?? '');
        $password = $_POST['adminPwd'] ?? '';

        if (empty($identifier) || empty($password) || ($_POST['_hp_website'] ?? '') !== '') {
            $this->core->getLogger()->warning("Auth: login-send ignored - empty field or honeypot filled");
            Show::msg('Veuillez renseigner votre pseudo ou adresse email et votre mot de passe.', 'error');
            return $this->login();
        }

        $useCookies = !empty($_POST['remember']);
        $authResult = $this->core->auth()->loginWithDetails($identifier, $password, $useCookies);

        if ($authResult['success']) {
            Show::msg(Lang::get("users.now-connected"), 'success');
            
            // Vérifier si l'utilisateur est admin
            $user = $authResult['user'] ?? UsersManager::getCurrentUser();
            $isAdmin = $user && ($user->isSuperAdmin() || $user->getRole() === 'admin');
            
            // Fallback to Evaluator if direct checks fail (for complex permission setups)
            if (!$isAdmin && $user) {
                $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
                $isAdmin = $evaluator->isAdmin(new \Core\Navigation\ConditionContext($user));
            }
            
            if ($isAdmin) {
                // Admin : rediriger systématiquement vers le tableau de bord général
                $this->core->redirect($this->router->generate('admin'));
            } else {
                // Utilisateur normal : rediriger vers l'URL de retour ou le profil
                $returnUrl = $_SESSION['login_return_url'] ?? null;
                unset($_SESSION['login_return_url']);
                if ($returnUrl) {
                    $this->core->redirect($returnUrl);
                } else {
                    $this->core->redirect($this->router->generate('profile'));
                }
            }
        } else {
            // Message d'erreur explicite et ciblé
            Show::msg($authResult['message'], 'error');
            $this->core->redirect($this->router->generate('login'));
        }
    }

    public function logout()
    {
        UsersManager::logout();
        Show::msg(Lang::get("users.now-disconnected"), 'success');
        $this->core->redirect($this->router->generate('home'));
    }

    public function lostPassword()
    {
        $response = new StringResponse();
        $tpl = $response->createPluginTemplate('users', 'lostpwd');
        $tpl->set('lostPwdLink', $this->router->generate('lost-password-send'));

        $response->addTemplate($tpl);
        return $response;
    }

    public function lostPasswordSend()
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg(Lang::get("users.bad-credentials"), 'error');
            $this->core->redirect($this->router->generate('login'));
        }
        if (empty($_POST['email']) || ($_POST['_hp_website'] ?? '') !== '') {
            // Empty field or robot
            return $this->login();
        }
        $user = User::find('email',trim($_POST['email']));
        if ($user === false) {
            Show::msg(Lang::get("users.bad-credentials"), 'error');
            $this->core->redirect($this->router->generate('login'));
        }
        $passRecovery = new PasswordRecovery();
        $pwd = $passRecovery->generatePassword();
        $passRecovery->insertToken($user->email, $user->token, $pwd);
        $successMail = $this->sendMail($user, $pwd);
        if ($successMail) {
            Show::msg(Lang::get("users-lost-password-mail-sent"), 'success');
            $response = new PublicResponse();
            $tpl = $response->createPluginTemplate('users', 'lostpwd-step2');
            $response->addTemplate($tpl);
            return $response;
        }
        Show::msg(Lang::get("users-lost-password-mail-not-sent"), 'error');
        $this->core->redirect($this->router->generate('home'));
    }

    public function lostPasswordConfirm($token)
    {
        sleep(2);
        $passRecovery = new PasswordRecovery();
        $usrToken = $passRecovery->getTokenFromToken($token);
        if ($usrToken === false) {
            Show::msg(Lang::get("users-lost-bad-token-link"), 'error');
            $this->core->redirect($this->router->generate('login'));
        }
        $user = User::find('email',$usrToken['mail']);
        if ($user === false) {
            Show::msg(Lang::get("users-lost-bad-token-link"), 'error');
            $this->core->redirect($this->router->generate('login'));
        }
        $user->pwd = UsersManager::encrypt($usrToken['pwd']);
        $user->save();
        $passRecovery->deleteToken($token);
        Show::msg(Lang::get("users-lost-password-success"), 'success');
            $this->core->redirect($this->router->generate('login'));
    }

    public function confirmRegistration(string $token)
    {
        $registration = RegistrationRequest::findPK($token);
        if ($registration === null) {
            Show::msg(Lang::get('users.registration-invalid-token'), 'error');
            $this->core->redirect($this->router->generate('home'));
        }
        
        // Vérifier l'expiration manuellement
        $expiresAt = $registration->attributes['expires_at'] ?? 0;
        if ($expiresAt > 0 && $expiresAt < time()) {
            Show::msg(Lang::get('users.registration-invalid-token'), 'error');
            $this->core->redirect($this->router->generate('home'));
        }

        // Vérifier que c'est bien une validation par email
        $validationMode = $registration->attributes['validation_mode'] ?? 'email';
        if ($validationMode !== 'email') {
            Show::msg(Lang::get('users.registration-invalid-token'), 'error');
            $this->core->redirect($this->router->generate('home'));
        }

        if ($this->emailExists($registration->attributes['email'] ?? '')) {
            $registration->delete();
            Show::msg(Lang::get('users.registration-email-exists'), 'error');
            $this->core->redirect($this->router->generate('login'));
        }

        $user = new User();
        $user->username = $registration->attributes['username'] ?? '';
        $user->email = $registration->attributes['email'] ?? '';
        $user->pwd = $registration->attributes['pwd'] ?? '';
        $user->attributes['firstname'] = $registration->attributes['firstname'] ?? '';
        $user->attributes['lastname'] = $registration->attributes['lastname'] ?? '';
        $groupSlug = $registration->attributes['group_slug'] ?? null;
        if ($groupSlug && $this->core->auth()->getGroupBySlug($groupSlug) !== null) {
            $user->group_id = $this->core->auth()->getGroupBySlug($groupSlug)->attributes['id'];
        }
        $user->status = 'active';
        $user->permissions = $registration->attributes['permissions'] ?? [];
        $user->attributes['created_at'] = date('Y-m-d H:i:s');
        $user->save();
        $this->core->callHook('users:after-registration-success', ['user' => $user]);

        $registration->delete();

        $this->core->auth()->forceLogin($user);
        Show::msg(Lang::get('users.registration-confirmed'), 'success');
        $this->core->redirect($this->router->generate('profile'));
    }

    public function profile()
    {
        if ($this->user === null) {
            Show::msg(Lang::get('users.profile-login-required'), 'error');
            $this->core->redirect($this->router->generate('login'));
        }

        // Récupérer les statistiques utilisateur (via hooks pour permettre aux plugins d'injecter des données)
        // Récupérer les statistiques utilisateur (via hooks pour permettre aux plugins d'injecter des données)
        $stats = $this->core->callHook('user:profile-stats', []);
        
        $this->core->getLogger()->log(\Core\Logger::LEVEL_DEBUG, "Profile Stats Count: " . (is_array($stats) ? count($stats) : "Not an array"));

        $this->core->getLogger()->log(\Core\Logger::LEVEL_DEBUG, "Profile Stats Count: " . (is_array($stats) ? count($stats) : "Not an array"));

        $response = new PublicResponse();
        $response->setTitle(Lang::get('users-profile-title'));
        $response->hidePageTitle(true);
        $tpl = $response->createPluginTemplate('users', 'profile');
        $tpl->set('profileSaveLink', $this->router->generate('profile-save'));
        $tpl->set('profileDeleteLink', $this->router->generate('profile-delete'));
        $tpl->set('profilePhotoUploadLink', $this->router->generate('profile-photo-upload'));
        $tpl->set('profilePhotoDeleteLink', $this->router->generate('profile-photo-delete'));
        
        // Récupérer l'URL de la photo de profil si elle existe
        $profilePhotoUrl = null;
        if (isset($this->user->attributes['profile_photo']) && !empty($this->user->attributes['profile_photo'])) {
            $photoPath = UPLOAD . 'users' . DS . 'profiles' . DS . $this->user->attributes['profile_photo'];
            if (file_exists($photoPath)) {
                $profilePhotoUrl = str_replace(ROOT, '', $photoPath);
                $profilePhotoUrl = str_replace(DS, '/', $profilePhotoUrl);
                $profilePhotoUrl = ltrim($profilePhotoUrl, '/');
            }
        }
        $tpl->set('profilePhotoUrl', $profilePhotoUrl);
        $tpl->set('hasProfilePhoto', ($profilePhotoUrl !== null));
        
        // Préparer les données d'avatar pour le template
        $avatarData = \Core\Auth\UserAvatarHelper::prepareAvatarData($this->user);
        $tpl->set('avatarData', $avatarData);
        
        // Préparer les données utilisateur avec city, district, age et sex
        $userData = $this->user->attributes; // Récupère tous les attributs par défaut
        $userData['username'] = $this->user->username();
        $userData['email'] = $this->user->email ?? '';
        
        // Récupérer les activités récentes (via hooks)
        $activities = $this->core->callHook('user:profile-activity', []);
        
        $tpl->set('activities', $activities);

        $statsSum = 0;
        if (is_array($stats)) {
            foreach ($stats as $statEntry) {
                $statsSum += (int) ($statEntry['value'] ?? 0);
            }
        }
        $hasContributions = !empty($activities) || $statsSum > 0;

        $tpl->set('user', $this->user); // Passer l'objet User complet
        $tpl->set('userData', (object)$userData); // Garder userData pour compatibilité si besoin
        $tpl->set('stats', $stats);
        $tpl->set('hasStats', $hasContributions);

        // Récupérer les paramètres du plugin auth pour la localisation
        $pluginsManager = \Core\Plugin\PluginsManager::getInstance();
        $authPlugin = $pluginsManager->getPlugin('auth');
        $authConfig = [
            'show_localisation' => true
        ];
        if ($authPlugin && $authPlugin->isInstalled()) {
            $file = DATA_PLUGIN . 'auth' . DS . 'auth_conf.json';
            if (file_exists($file)) {
                $existing = json_decode(file_get_contents($file), true) ?: [];
                $authConfig = array_merge($authConfig, $existing);
            }
        }
        $tpl->set('auth_show_localisation', $authConfig['show_localisation']);
        
        // Ajouter le router au template pour générer les URLs
        $tpl->set('ROUTER', $this->router);
        $tpl->set('_csrfToken', Csrf::token());

        // Logique de visibilité de l'email
        $showEmail = ($this->user->attributes['public_email'] ?? false) || true; // Toujours vrai pour soi-même
        // Note: Si on implémente la vue d'un profil tiers plus tard, il faudra comparer $this->user->id avec $targetUser->id
        $tpl->set('showEmail', $showEmail);
        $tpl->set('public_email', $this->user->attributes['public_email'] ?? false);

        // Données pour les réglages (toggles) sur le profil
        $tpl->set('allowMessaging', $this->user->attributes['allow_messaging'] ?? true);
        
        // Initialiser dynamicFields et appeler le hook pour que les plugins puissent injecter leurs champs
        $dynamicFields = $this->core->callHook('user:profile-dynamic-fields', []);
        $tpl->set('dynamicFields', $dynamicFields);
        
        // Les réglages spécifiques aux plugins seront injectés via HOOK.user-profile-settings dans le template
        
        $response->addTemplate($tpl);
        return $response;
    }

    /**
     * Met à jour un réglage utilisateur à la volée (AJAX)
     */
    public function profileUpdateSetting()
    {
        if ($this->user === null) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Non authentifié']);
            die();
        }

        $setting = $_POST['setting'] ?? null;
        $value = ($_POST['value'] ?? '0') === '1';

        if (!$setting) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Paramètre manquant']);
            die();
        }

        $allowedSettings = [
            'allow_messaging',
            'allow_notifications',
            'public_email'
        ];
        
        // allow plugins to register their own allowed settings
        $allowedSettings = $this->core->callHook('user:profile-allowed-settings', $allowedSettings);

        if (!in_array($setting, $allowedSettings)) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Paramètre non autorisé']);
            die();
        }

        $this->core->getLogger()->info("Profile Update Setting: $setting = " . ($value ? 'true' : 'false'));

        $this->user->attributes[$setting] = $value;
        if ($this->user->save()) {
            $this->core->getLogger()->info("Profile Update Setting: $setting saved successfully");
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success']);
            die();
        }

        $this->core->getLogger()->error("Profile Update Setting: $setting failed to save");
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Erreur lors de la sauvegarde']);
        die();
    }

    /**
     * Retourne le fragment HTML du menu avatar (AJAX)
     */
    public function profileAvatarMenu()
    {
        if ($this->user === null) {
            die();
        }
        ob_start();
        \Utils\Show::userAvatarMenu();
        $html = ob_get_clean();
        
        // Retourner uniquement le contenu de l'élément li si on veut remplacer juste le contenu,
        // mais ici on va probablement remplacer tout le li.nav-user-avatar-menu.
        echo $html;
        die();
    }

    public function profileSave()
    {
        if ($this->user === null) {
            $this->core->redirect($this->router->generate('login'));
        }
        if (!Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg(Lang::get('users.bad-credentials'), 'error');
            $this->core->redirect($this->router->generate('profile'));
        }

        $username = trim($_POST['username'] ?? '');
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?: '';
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['password_confirm'] ?? '';

        if ($email === '') {
            Show::msg(Lang::get('users.profile-invalid-email'), 'error');
            $this->core->redirect($this->router->generate('profile-edit'));
        }

        if ($email !== $this->user->email && $this->emailExists($email)) {
            Show::msg(Lang::get('users.registration-email-exists'), 'error');
            $this->core->redirect($this->router->generate('profile-edit'));
        }

        if ($username !== '' && $username !== $this->user->username() && $this->usernameExists($username, $this->user->attributes['id'])) {
            Show::msg(Lang::get('users.registration-username-exists'), 'error');
            $this->core->redirect($this->router->generate('profile-edit'));
        }

        if ($newPassword !== '') {
            if ($newPassword !== $confirmPassword) {
                Show::msg(Lang::get('users.registration-password-mismatch'), 'error');
                $this->core->redirect($this->router->generate('profile-edit'));
            }
            if (!UsersManager::verify($currentPassword, $this->user->pwd, $this->user)) {
                Show::msg(Lang::get('users.profile-current-password-invalid'), 'error');
                $this->core->redirect($this->router->generate('profile-edit'));
            }
            if (strlen($newPassword) < 8) {
                Show::msg(Lang::get('users.registration-password-length'), 'error');
                $this->core->redirect($this->router->generate('profile-edit'));
            }
            $this->user->pwd = UsersManager::encrypt($newPassword);
        }

        if ($username !== '') {
            $this->user->username = $username;
        }
        if (isset($_POST['firstname'])) {
            $this->user->attributes['firstname'] = trim($_POST['firstname']);
        }
        if (isset($_POST['lastname'])) {
            $this->user->attributes['lastname'] = trim($_POST['lastname']);
        }
        $this->user->email = $email;
        
        // Gérer l'option de messagerie privée
        $pluginsManager = \Core\Plugin\PluginsManager::getInstance();
        $messagingPlugin = $pluginsManager->getPlugin('messaging');
        $messagingEnabled = ($messagingPlugin && $messagingPlugin->isInstalled() && $messagingPlugin->getConfigVal('activate'));
        if ($messagingEnabled) {
            if (isset($_POST['allow_messaging_present'])) { // Champ masqué pour prouver la présence des toggles
                $allowPrivateMessages = isset($_POST['allow_messaging']) && $_POST['allow_messaging'] === '1';
                $this->user->attributes['allow_messaging'] = $allowPrivateMessages;
            }
        }
        
        // Gérer les champs dynamiques du profil (Auth plugin)
        $authPlugin = $pluginsManager->getPlugin('auth');
        if ($authPlugin && $authPlugin->isInstalled()) {
            $file = DATA_PLUGIN . 'auth' . DS . 'auth_conf.json';
            if (file_exists($file)) {
                $authConfig = json_decode(file_get_contents($file), true) ?: [];
                if (!empty($authConfig['dynamic_fields'])) {
                    foreach ($authConfig['dynamic_fields'] as $field) {
                        if (!($field['enabled'] ?? false)) continue;
                        
                        $id = $field['id'];
                        $val = trim($_POST[$id] ?? '');
                        if ($val === 'other') {
                            $val = trim($_POST[$id . '_other'] ?? '');
                        }
                        
                        if ($val !== '') {
                            if ($field['type'] === 'number') $val = intval($val);
                            $this->user->attributes[$id] = $val;
                        } else {
                            unset($this->user->attributes[$id]);
                        }
                    }
                }
            }
        }
        
        $this->user->save();
        $this->core->auth()->forceLogin($this->user);

        Show::msg(Lang::get('users.profile-updated'), 'success');
        $this->core->redirect($this->router->generate('profile-edit'));
    }
    
    /**
     * Page de modification du profil
     */
    public function profileEdit()
    {
        if ($this->user === null) {
            Show::msg(Lang::get('users.profile-login-required'), 'error');
            $this->core->redirect($this->router->generate('login'));
            return;
        }

        $response = new PublicResponse();
        $response->setTitle('Modifier mon profil');
        $response->hidePageTitle(true);
        $tpl = $response->createPluginTemplate('users', 'profile-edit');
        
        $tpl->set('profileSaveLink', $this->router->generate('profile-save'));
        $tpl->set('profilePhotoUploadLink', $this->router->generate('profile-photo-upload'));
        $tpl->set('profilePhotoDeleteLink', $this->router->generate('profile-photo-delete'));
        $tpl->set('profileLink', $this->router->generate('profile'));
        
        // Récupérer l'URL de la photo de profil si elle existe
        $profilePhotoUrl = null;
        if (isset($this->user->attributes['profile_photo']) && !empty($this->user->attributes['profile_photo'])) {
            $photoPath = UPLOAD . 'users' . DS . 'profiles' . DS . $this->user->attributes['profile_photo'];
            if (file_exists($photoPath)) {
                $profilePhotoUrl = str_replace(ROOT, '', $photoPath);
                $profilePhotoUrl = str_replace(DS, '/', $profilePhotoUrl);
                $profilePhotoUrl = ltrim($profilePhotoUrl, '/');
            }
        }
        $tpl->set('profilePhotoUrl', $profilePhotoUrl);
        $tpl->set('hasProfilePhoto', ($profilePhotoUrl !== null));
        
        $tpl->set('user', $this->user);
        $tpl->set('firstname', $this->user->attributes['firstname'] ?? '');
        $tpl->set('lastname', $this->user->attributes['lastname'] ?? '');
        $tpl->set('ROUTER', $this->router);
        $tpl->set('_csrfToken', Csrf::token());
        
        // Les champs et réglages spécifiques aux plugins seront gérés via des hooks dans le template
        // {% HOOK.user-profile-edit-settings(user) %}
        
        $tpl->set('user', $this->user);
        $tpl->set('ROUTER', $this->router);
        $tpl->set('_csrfToken', Csrf::token());
        
        // Les champs dynamiques seront injectés via HOOK.user-profile-edit-settings dans le template
        $tpl->set('dynamicFields', []);
        
        $response->addTemplate($tpl);
        return $response;
    }
    
    /**
     * Page de zone de danger (suppression de compte)
     */
    public function profileDanger()
    {
        if ($this->user === null) {
            Show::msg(Lang::get('users.profile-login-required'), 'error');
            $this->core->redirect($this->router->generate('login'));
            return;
        }

        $response = new PublicResponse();
        $response->setTitle('Zone de danger');
        $response->hidePageTitle(true);
        $tpl = $response->createPluginTemplate('users', 'profile-danger');
        
        $tpl->set('profileDeleteLink', $this->router->generate('profile-delete'));
        $tpl->set('profileLink', $this->router->generate('profile'));
        $tpl->set('ROUTER', $this->router);
        $tpl->set('_csrfToken', Csrf::token());
        
        $response->addTemplate($tpl);
        return $response;
    }
    
    public function profilePhotoUpload()
    {
        if ($this->user === null) {
            $this->core->redirect($this->router->generate('login'));
        }
        if (!Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg(Lang::get('users.bad-credentials'), 'error');
            $this->core->redirect($this->router->generate('profile-edit'));
        }
        
        if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
            Show::msg('Erreur lors du téléchargement de la photo.', 'error');
            $this->core->redirect($this->router->generate('profile-edit'));
        }
        
        $file = $_FILES['profile_photo'];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5 MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            Show::msg('Format de fichier non autorisé. Formats acceptés : JPG, PNG, GIF, WebP.', 'error');
            $this->core->redirect($this->router->generate('profile-edit'));
        }
        
        if ($file['size'] > $maxSize) {
            Show::msg('Le fichier est trop volumineux. Taille maximale : 5 MB.', 'error');
            $this->core->redirect($this->router->generate('profile-edit'));
        }
        
        // Créer le répertoire si nécessaire
        $uploadDir = UPLOAD . 'users' . DS . 'profiles' . DS;
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        
        // Supprimer l'ancienne photo si elle existe
        if (isset($this->user->attributes['profile_photo']) && !empty($this->user->attributes['profile_photo'])) {
            $oldPhotoPath = $uploadDir . $this->user->attributes['profile_photo'];
            if (file_exists($oldPhotoPath)) {
                @unlink($oldPhotoPath);
            }
        }
        
        // Générer un nom de fichier unique
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $this->user->attributes['id'] . '_' . time() . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Redimensionner l'image si nécessaire (max 500x500)
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            Show::msg('Le fichier n\'est pas une image valide.', 'error');
            $this->core->redirect($this->router->generate('profile'));
        }
        
        $sourceImage = null;
        switch ($file['type']) {
            case 'image/jpeg':
            case 'image/jpg':
                $sourceImage = imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($file['tmp_name']);
                break;
            case 'image/webp':
                $sourceImage = imagecreatefromwebp($file['tmp_name']);
                break;
        }
        
        if ($sourceImage === null) {
            Show::msg('Impossible de traiter l\'image.', 'error');
            $this->core->redirect($this->router->generate('profile'));
        }
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $maxDimension = 500;
        
        // Calculer les nouvelles dimensions
        if ($width > $maxDimension || $height > $maxDimension) {
            $ratio = min($maxDimension / $width, $maxDimension / $height);
            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);
            
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Préserver la transparence pour PNG et GIF
            if ($file['type'] === 'image/png' || $file['type'] === 'image/gif') {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                imagefill($newImage, 0, 0, $transparent);
            }
            
            imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($sourceImage);
            $sourceImage = $newImage;
        }
        
        // Sauvegarder l'image
        $saved = false;
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $saved = imagejpeg($sourceImage, $filepath, 85);
                break;
            case 'png':
                $saved = imagepng($sourceImage, $filepath, 6);
                break;
            case 'gif':
                $saved = imagegif($sourceImage, $filepath);
                break;
            case 'webp':
                $saved = imagewebp($sourceImage, $filepath, 85);
                break;
        }
        
        imagedestroy($sourceImage);
        
        if (!$saved) {
            Show::msg('Erreur lors de la sauvegarde de la photo.', 'error');
            $this->core->redirect($this->router->generate('profile'));
        }
        
        // Mettre à jour l'utilisateur
        $this->user->attributes['profile_photo'] = $filename;
        $this->user->save();
        $this->core->auth()->forceLogin($this->user);
        
        Show::msg('Photo de profil mise à jour avec succès.', 'success');
        $this->core->redirect($this->router->generate('profile'));
    }
    
    public function profilePhotoDelete()
    {
        if ($this->user === null) {
            $this->core->redirect($this->router->generate('login'));
        }
        if (!Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg(Lang::get('users.bad-credentials'), 'error');
            $this->core->redirect($this->router->generate('profile-edit'));
        }
        
        if (isset($this->user->attributes['profile_photo']) && !empty($this->user->attributes['profile_photo'])) {
            $uploadDir = UPLOAD . 'users' . DS . 'profiles' . DS;
            $photoPath = $uploadDir . $this->user->attributes['profile_photo'];
            if (file_exists($photoPath)) {
                @unlink($photoPath);
            }
            unset($this->user->attributes['profile_photo']);
            $this->user->save();
            $this->core->auth()->forceLogin($this->user);
            Show::msg('Photo de profil supprimée avec succès.', 'success');
        }
        
        $this->core->redirect($this->router->generate('profile-edit'));
    }
    


    protected function sendMail($user, $pwd): bool
    {
        $link = \Utils\Util::urlBuild($this->router->generate('lost-password-confirm', ['token' => $user->token]));
        $to = $user->email;
        $siteName = $this->core->getConfigVal('siteName');
        $siteUrl = $this->core->getConfigVal('siteUrl');
        $domain = parse_url($siteUrl, PHP_URL_HOST);
        $from = $siteName . ' <no-reply@' . $domain . '>';
        $reply = $from;
        $subject = Lang::get('users-lost-password-subject', $siteName);
        $msg = Lang::get('users-lost-password-content', $pwd, $link);
        $mail = Util::sendEmail($from, $reply, $to, $subject, $msg);
        if ($mail) {
            $logger = \Core\Core::getInstance()->getLogger();
            $logger->log(\Core\Logger::LEVEL_INFO, 'User ' . ($user->email ?? $user->mail ?? 'unknown') . ' asked to reset password');
        }
        return $mail;
    }

    protected function sendRegistrationMail(RegistrationRequest $request): bool
    {
        $token = $request->attributes['token'] ?? '';
        $link = \Utils\Util::urlBuild($this->router->generate('register-confirm', ['token' => $token]));
        $to = $request->attributes['email'] ?? '';
        $siteName = $this->core->getConfigVal('siteName');
        $siteUrl = $this->core->getConfigVal('siteUrl');
        $domain = parse_url($siteUrl, PHP_URL_HOST);
        $from = $siteName . ' <no-reply@' . $domain . '>';
        $reply = $from;
        $subject = Lang::get('users.registration-mail-subject', $siteName);
        $username = $request->attributes['username'] ?? '';
        $msg = Lang::get('users.registration-mail-content', $username, $link);

        // Add explicit token info in case the link is not clickable or broken
        $msg .= "\n\n" . "Pour confirmer votre inscription, veuillez cliquer sur le lien ci-dessus.";
        $msg .= "\n\n" . "Si le lien ne fonctionne pas, vous pouvez copier-coller cette adresse dans votre navigateur :\n" . $link;
        $msg .= "\n\n" . "Code de validation (si demandé) : " . $token;

        return Util::sendEmail($from, $reply, $to, $subject, $msg);
    }

    protected function ensureRegistrationEnabled(): void
    {
        if (!$this->core->auth()->isRegistrationEnabled()) {
            $this->core->error404();
        }
    }

    protected function cleanupPendingRequests(string $email): void
    {
        foreach (RegistrationRequest::all() as $pending) {
            if (($pending->attributes['email'] ?? '') === $email) {
                $pending->delete();
            }
        }
    }

    protected function emailExists(string $email): bool
    {
        return User::find('email', $email) !== null;
    }

    protected function attachAntispam($tpl, string $configKey): void
    {
        $antispamField = '';
        if (!empty($this->core->getConfigVal($configKey)) && PluginsManager::isActivePlugin('antispam')) {
            $antispamData = $this->core->callHook('antispam:get-captcha', ['field_html' => '']);
            $antispamField = $antispamData['field_html'] ?? '';
        }
        $tpl->set('antispamField', $antispamField);
        $tpl->set('antispam', $antispamField !== '');
    }

    protected function isAntispamValid(string $configKey): bool
    {
        if (empty($this->core->getConfigVal($configKey)) || !PluginsManager::isActivePlugin('antispam')) {
            return true;
        }
        $result = $this->core->callHook('antispam:validate', ['is_valid' => true]);
        return (bool) ($result['is_valid'] ?? false);
    }

    protected function usernameExists(string $username, ?int $excludeId = null): bool
    {
        foreach (User::all() as $user) {
            if ($excludeId !== null && (int) ($user->attributes['id'] ?? 0) === $excludeId) {
                continue;
            }
            if (strcasecmp($user->attributes['username'] ?? '', $username) === 0) {
                return true;
            }
        }
        foreach (RegistrationRequest::all() as $pending) {
            if ($pending->isExpired()) {
                continue;
            }
            if (strcasecmp($pending->attributes['username'] ?? '', $username) === 0) {
                return true;
            }
        }
        return false;
    }

    

    /**
     * Supprime le compte de l'utilisateur après validation
     */
    public function deleteAccount()
    {
        if ($this->user === null) {
            $this->core->redirect($this->router->generate('login'));
            return;
        }

        if (!Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg(Lang::get('users.bad-credentials'), 'error');
            $this->core->redirect($this->router->generate('profile'));
            return;
        }

        $confirmation = trim($_POST['delete_confirmation'] ?? '');
        if (strtolower($confirmation) !== 'supprimer') {
            Show::msg('Vous devez taper "supprimer" pour confirmer la suppression de votre compte.', 'error');
            $this->core->redirect($this->router->generate('profile-danger'));
            return;
        }

        try {
            // Supprimer le compte CMS
            $this->user->delete();

            // Déconnecter l'utilisateur (utiliser la même méthode que logout())
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 3600,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            session_destroy();
            setcookie('koAutoConnect', '/', 1, '/');
            // Redémarrer la session pour les flash messages
            session_start();

            Show::msg('Votre compte a été supprimé avec succès.', 'success');
            $this->core->redirect($this->router->generate('home'));
            return;
        } catch (\Exception $e) {
            error_log('UsersLoginController::deleteAccount - ' . $e->getMessage());
            Show::msg('Une erreur est survenue lors de la suppression de votre compte.', 'error');
            $this->core->redirect($this->router->generate('profile-danger'));
            return;
        }
    }
}
