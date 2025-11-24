<?php

namespace Core\Auth\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Auth\User;
use Core\Auth\UsersManager;
use Utils\Show;
use Core\Lang;
use Core\Core;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 *
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class UsersAdminManagementController extends AdminController {

    public function addUser() {

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('users', 'usersadd');

        $tpl->set('link', $this->router->generate('users-add-send'));

        $response->addTemplate($tpl);
        return $response;
    }

    public function addUserSend() {
        if (!$this->user->isAuthorized()) {
            return $this->addUser();
        }
        $mail = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?? false;
        $pwd = filter_input(INPUT_POST, 'pwd', FILTER_UNSAFE_RAW) ?? false;
        if (!$mail || !$pwd) {
            Show::msg(Lang::get('users-bad-entries'), 'error');
            return $this->addUser();
        }
        if (User::find('email',$mail) !== null) {
            Show::msg(Lang::get('users-already-exists'), 'error');
            return $this->addUser();
        }
        $user = new User();
        $user->email = $mail;
        $user->pwd = UsersManager::encrypt($pwd);
        $user->save();
        Show::msg(Lang::get('users-added'), 'success');
        Core::getInstance()->getLogger()->log('INFO', 'User added: '. $mail);
        $this->core->redirect($this->router->generate('users-admin-home'));
    }

    public function edit(int $id) {
        $user = User::findPK($id);
        if ($user === null) {
            $this->core->redirect($this->router->generate('users-admin-home'));
        }
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('users', 'usersedit');

        $tpl->set('link', $this->router->generate('users-edit-send'));
        $tpl->set('user', $user);

        $response->addTemplate($tpl);
        return $response;
    }

    public function editUserSend() {
        if (!$this->user->isAuthorized()) {
            return $this->addUser();
        }
        $mail = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?? false;
        $pwd = filter_input(INPUT_POST, 'pwd', FILTER_UNSAFE_RAW) ?? false;
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?? false;
        $user = User::findPK($id);
        if (!$mail || !$id || $user === null) {
            Show::msg(Lang::get('users-credentials-issue'), 'error');
            $this->core->redirect($this->router->generate('users-admin-home'));
        }
        // Check if mail is already taken
        foreach (User::all() as $u) {
            if ($u->email === $mail && $u->id !== $id) {
                Show::msg(Lang::get('users-already-exists'), 'error');
                $this->core->redirect($this->router->generate('users-admin-home'));
            }
        }

        if ($pwd !== false && $pwd !== '') {
            // Change password
            $user->pwd = UsersManager::encrypt($pwd);
        }
        $user->email = $mail;
        $user->save();
        Show::msg(Lang::get('users-edited'), 'success');
        Core::getInstance()->getLogger()->log('INFO', 'User edited: '. $mail);
        $this->core->redirect($this->router->generate('users-admin-home'));
    }

    public function delete(int $id, $token) {
        if (!$this->user->isAuthorized()) {
            $this->core->redirect($this->router->generate('users-admin-home'));
        }
        $user = User::findPK($id);
        if ($user === null) {
            Show::msg(Lang::get('users-credentials-issue'), 'error');
            $this->core->redirect($this->router->generate('users-admin-home'));
        }
        $mail = $user->email;
        if ($user->delete()) {
            Show::msg(Lang::get('users-deleted'), 'success');
            Core::getInstance()->getLogger()->log('INFO', 'User deleted: '. $mail);
            $this->core->redirect($this->router->generate('users-admin-home'));
        }
        Show::msg(Lang::get('core-changes-not-saved'), 'error');
            $this->core->redirect($this->router->generate('users-admin-home'));
    }
}