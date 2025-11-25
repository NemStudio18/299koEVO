<?php

namespace Core\Auth\Controllers;

use Core\Controllers\PublicController;
use Core\Responses\StringResponse;
use Core\Responses\PublicResponse;
use Core\Auth\UsersManager;
use Core\Auth\User;
use Core\Auth\PasswordRecovery;
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
        $response = new StringResponse();
        $tpl = $response->createPluginTemplate('users', 'login');
        $tpl->set('loginLink', $this->router->generate('login-send'));

        $tpl->set('lostLink', $this->router->generate('lost-password'));
        $response->addTemplate($tpl);
        return $response;
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
}
