<?php

namespace Core\Auth\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Auth\User;
use Core\Auth\UsersManager;
use Utils\Show;
use Core\Lang;
use Core\Core;
use Core\Auth\Permissions;

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
        $tpl->set('groups', $this->core->auth()->getGroups());
        $tpl->set('permissionsDefinitions', Permissions::translated());

        $response->addTemplate($tpl);
        return $response;
    }

    public function addUserSend() {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            return $this->addUser();
        }
        $mail = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?? false;
        $pwd = filter_input(INPUT_POST, 'pwd', FILTER_UNSAFE_RAW) ?? false;
        $username = trim($_POST['username'] ?? '');
        $groupSlug = $_POST['group_slug'] ?? null;
        $status = $this->sanitizeStatus($_POST['status'] ?? 'active');
        $permissions = $_POST['permissions'] ?? [];
        if (!$mail || !$pwd || $username === '') {
            Show::msg(Lang::get('users-bad-entries'), 'error');
            return $this->addUser();
        }
        if (User::find('email',$mail) !== null) {
            Show::msg(Lang::get('users-already-exists'), 'error');
            return $this->addUser();
        }
        if ($this->usernameExists($username)) {
            Show::msg(Lang::get('users-registration-username-exists'), 'error');
            return $this->addUser();
        }
        $user = new User();
        $user->email = $mail;
        $user->pwd = UsersManager::encrypt($pwd);
        $user->username = $username;
        $user->group_id = $this->resolveGroupId($groupSlug);
        $user->status = $status;
        $user->custom_badge = trim($_POST['custom_badge'] ?? '');
        $user->permissions = $this->sanitizePermissions($permissions);
        $user->save();
        Show::msg(Lang::get('users-added'), 'success');
        Core::getInstance()->getLogger()->log('INFO', 'User added: '. $mail);
        $this->core->redirect($this->router->generate('users-admin-home'));
    }

    public function edit($id) {
        $id = (string) $id;
        $user = User::findPK($id);
        if ($user === null) {
            Show::msg('Utilisateur introuvable.', 'error');
            $this->core->redirect($this->router->generate('users-admin-home'));
            return null;
        }
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('users', 'usersedit');

        $tpl->set('link', $this->router->generate('users-edit-send'));
        $groups = $this->core->auth()->getGroups();
        $user->group_slug = $this->groupSlugFromId($groups, $user->attributes['group_id'] ?? ($user->attributes['role'] ?? null));
        $tpl->set('user', $user);
        $tpl->set('groups', $groups);
        $tpl->set('permissionsDefinitions', Permissions::translated());

        $response->addTemplate($tpl);
        return $response;
    }

    public function editUserSend() {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            return $this->addUser();
        }
        $mail = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $pwd = $_POST['pwd'] ?? '';
        $id = trim((string) ($_POST['id'] ?? ''));
        $user = User::findPK($id);
        $username = trim($_POST['username'] ?? '');
        $groupSlug = $_POST['group_slug'] ?? null;
        $status = $this->sanitizeStatus($_POST['status'] ?? 'active');
        $permissions = $_POST['permissions'] ?? [];
        if (!$mail || $id === '' || $user === null || $username === '') {
            Show::msg(Lang::get('users-credentials-issue'), 'error');
            $this->core->redirect($this->router->generate('users-admin-home'));
            return null;
        }
        // Check if mail is already taken
        foreach (User::all() as $u) {
            $uId = (string) ($u->id ?? $u->getId());
            if ($u->email === $mail && $uId !== $id) {
                Show::msg(Lang::get('users-already-exists'), 'error');
                $this->core->redirect($this->router->generate('users-admin-home'));
                return null;
            }
            if ($username === ($u->username ?? '') && $uId !== $id) {
                Show::msg(Lang::get('users-registration-username-exists'), 'error');
                $this->core->redirect($this->router->generate('users-admin-home'));
                return null;
            }
        }

        if ($pwd !== '' && $pwd !== false) {
            // Change password
            $user->pwd = UsersManager::encrypt($pwd);
        }
        $user->email = $mail;
        $user->username = $username;
        $user->custom_badge = trim($_POST['custom_badge'] ?? '');
        if (!$user->isSuperAdmin()) {
            $user->group_id = $this->resolveGroupId($groupSlug);
            $user->status = $status;
            $user->permissions = $this->sanitizePermissions($permissions);
        }
        $user->save();
        Show::msg(Lang::get('users-edited'), 'success');
        Core::getInstance()->getLogger()->log('INFO', 'User edited: '. $mail);
        $this->core->redirect($this->router->generate('users-admin-home'));
        return null;
    }

    public function delete($id, $token) {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            $this->core->redirect($this->router->generate('users-admin-home'));
            return null;
        }
        $id = (string) $id;
        $user = User::findPK($id);
        if ($user === null) {
            Show::msg(Lang::get('users-credentials-issue'), 'error');
            $this->core->redirect($this->router->generate('users-admin-home'));
            return null;
        }
        if (!$user->canBeDeleted()) {
            Show::msg(Lang::get('users-delete-forbidden'), 'error');
            $this->core->redirect($this->router->generate('users-admin-home'));
            return null;
        }
        $mail = $user->email;
        if ($user->delete()) {
            Show::msg(Lang::get('users-deleted'), 'success');
            Core::getInstance()->getLogger()->log('INFO', 'User deleted: '. $mail);
            $this->core->redirect($this->router->generate('users-admin-home'));
            return null;
        }
        Show::msg(Lang::get('core-changes-not-saved'), 'error');
        $this->core->redirect($this->router->generate('users-admin-home'));
        return null;
    }

    protected function resolveGroupId(?string $slug): int
    {
        if ($slug) {
            $group = $this->core->auth()->getGroupBySlug($slug);
            if ($group !== null) {
                return (int) ($group->attributes['id'] ?? $this->core->auth()->getDefaultGroupId());
            }
        }
        return $this->core->auth()->getDefaultGroupId();
    }

    protected function sanitizePermissions($permissions): array
    {
        if (!is_array($permissions)) {
            return [];
        }
        $allowed = Permissions::keys();
        $allowed[] = Permissions::ALL;
        return array_values(array_intersect($allowed, $permissions));
    }

    protected function usernameExists(string $username): bool
    {
        foreach (User::all() as $user) {
            if (strcasecmp($user->username ?? '', $username) === 0) {
                return true;
            }
        }
        return false;
    }

    protected function groupSlugFromId(array $groups, $id): ?string
    {
        foreach ($groups as $group) {
            if (($group->attributes['id'] ?? null) == $id) {
                return $group->getSlug();
            }
        }
        return null;
    }

    protected function sanitizeStatus(?string $status): string
    {
        $allowed = ['active', 'pending', 'disabled'];
        return in_array($status, $allowed, true) ? $status : 'active';
    }
}