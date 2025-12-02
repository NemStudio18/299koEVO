<?php

namespace Core\Auth\Controllers;

use Core\Controllers\PublicController;
use Core\Responses\StringResponse;
use Core\Responses\PublicResponse;
use Core\Auth\UsersManager;
use Core\Auth\User;
use Core\Auth\PasswordRecovery;
use Core\Auth\RegistrationRequest;
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
        $response = new PublicResponse();
        $response->setTitle(Lang::get('core-connection'));
        $response->hidePageTitle(true);
        $tpl = $response->createPluginTemplate('users', 'login');
        $tpl->set('loginLink', $this->router->generate('login-send'));
        $tpl->set('lostLink', $this->router->generate('lost-password'));
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
        if (($_POST['_email'] ?? '') !== '') {
            return $this->register();
        }

        $username = trim($_POST['username'] ?? '');
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?: '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if ($email === '' || $password === '' || $username === '') {
            Show::msg(Lang::get('users.registration-invalid'), 'error');
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
            Show::msg(Lang::get('users.registration-username-exists'), 'error');
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
            $groupSlug = $this->core->auth()->getDefaultGroupSlug();
            if ($groupSlug && $this->core->auth()->getGroupBySlug($groupSlug) !== null) {
                $user->group_id = $this->core->auth()->getGroupBySlug($groupSlug)->attributes['id'];
            }
            $user->status = 'active';
            $user->permissions = [];
            $user->save();

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
            $registration->group_slug = $this->core->auth()->getDefaultGroupSlug();
            $registration->permissions = [];
            $registration->created_at = time();
            $registration->expires_at = time() + 86400;
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
        if (empty($_POST['adminEmail']) || empty($_POST['adminPwd']) || $_POST['_email'] !== '') {
            // Empty field or robot
            return $this->login();
        }
        $useCookies = $_POST['remember'] ?? false;
        $logged = UsersManager::login(trim($_POST['adminEmail']), $_POST['adminPwd'], $useCookies);
        if ($logged) {
            Show::msg(Lang::get("users.now-connected"), 'success');
            // Redirect to default admin plugin
            $defaultAdminPlugin = $this->core->getConfigVal('defaultAdminPlugin');
            if ($defaultAdminPlugin) {
                $this->core->redirect($this->router->generate('admin') . '/' . $defaultAdminPlugin);
            } else {
                $this->core->redirect($this->router->generate('admin'));
            }
        } else {
            Show::msg(Lang::get("users.bad-credentials"), 'error');
            $this->core->redirect($this->router->generate('login'));
        }
    }

    public function logout()
    {
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
        // Restart session for flash messages
        session_start();
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
        if (empty($_POST['email']) || $_POST['_email'] !== '') {
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
        if ($registration === null || $registration->isExpired()) {
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
        $groupSlug = $registration->attributes['group_slug'] ?? null;
        if ($groupSlug && $this->core->auth()->getGroupBySlug($groupSlug) !== null) {
            $user->group_id = $this->core->auth()->getGroupBySlug($groupSlug)->attributes['id'];
        }
        $user->status = 'active';
        $user->permissions = $registration->attributes['permissions'] ?? [];
        $user->save();

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

        $response = new PublicResponse();
        $response->setTitle(Lang::get('users-profile-title'));
        $response->hidePageTitle(true);
        $tpl = $response->createPluginTemplate('users', 'profile');
        $tpl->set('profileSaveLink', $this->router->generate('profile-save'));
        $tpl->set('user', $this->user);
        $response->addTemplate($tpl);
        return $response;
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
            $this->core->redirect($this->router->generate('profile'));
        }

        if ($email !== $this->user->email && $this->emailExists($email)) {
            Show::msg(Lang::get('users.registration-email-exists'), 'error');
            $this->core->redirect($this->router->generate('profile'));
        }

        if ($username !== '' && $username !== $this->user->username() && $this->usernameExists($username, $this->user->attributes['id'])) {
            Show::msg(Lang::get('users.registration-username-exists'), 'error');
            $this->core->redirect($this->router->generate('profile'));
        }

        if ($newPassword !== '') {
            if ($newPassword !== $confirmPassword) {
                Show::msg(Lang::get('users.registration-password-mismatch'), 'error');
                $this->core->redirect($this->router->generate('profile'));
            }
            if (!UsersManager::verify($currentPassword, $this->user->pwd, $this->user)) {
                Show::msg(Lang::get('users.profile-current-password-invalid'), 'error');
                $this->core->redirect($this->router->generate('profile'));
            }
            if (strlen($newPassword) < 8) {
                Show::msg(Lang::get('users.registration-password-length'), 'error');
                $this->core->redirect($this->router->generate('profile'));
            }
            $this->user->pwd = UsersManager::encrypt($newPassword);
        }

        if ($username !== '') {
            $this->user->username = $username;
        }
        $this->user->email = $email;
        $this->user->save();
        $this->core->auth()->forceLogin($this->user);

        Show::msg(Lang::get('users.profile-updated'), 'success');
        $this->core->redirect($this->router->generate('profile'));
    }

    protected function sendMail($user, $pwd): bool
    {
        $link = $this->router->generate('lost-password-confirm', ['token' => $user->token]);
        $to = $user->email;
        $from = '299ko@' . $_SERVER['SERVER_NAME'];
        $reply = $from;
        $subject = Lang::get('users-lost-password-subject', $this->core->getConfigVal('siteName'));
        $msg = Lang::get('users-lost-password-content', $pwd, $link);
        $mail = Util::sendEmail($from, $reply, $to, $subject, $msg);
        if ($mail) {
            logg('User ' . $user->mail . ' asked to reset password');
        }
        return $mail;
    }

    protected function sendRegistrationMail(RegistrationRequest $request): bool
    {
        $link = $this->router->generate('register-confirm', ['token' => $request->attributes['token'] ?? '']);
        $to = $request->attributes['email'] ?? '';
        $from = '299ko@' . $_SERVER['SERVER_NAME'];
        $reply = $from;
        $subject = Lang::get('users.registration-mail-subject', $this->core->getConfigVal('siteName'));
        $msg = Lang::get('users.registration-mail-content', $request->attributes['username'] ?? '', $link);
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
        return false;
    }
}
