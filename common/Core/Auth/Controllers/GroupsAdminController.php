<?php

namespace Core\Auth\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Auth\Group;
use Core\Auth\Permissions;
use Core\Auth\User;
use Utils\Show;
use Core\Lang;
use Core\Core;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 *
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class GroupsAdminController extends AdminController
{
    public function home()
    {
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('users', 'groupslist');

        $groups = $this->core->auth()->getGroups();
        
        // Count users per group & resolve permission labels
        foreach ($groups as $group) {
            $groupId = $group->attributes['id'] ?? null;
            $group->userCount = $this->countUsersInGroup($groupId);
            $labels = [];
            foreach ($group->getPermissions() as $perm) {
                if ($perm === Permissions::ALL) {
                    $labels[] = ['key' => $perm, 'label' => Lang::get('permissions.all')];
                } else {
                    $labels[] = ['key' => $perm, 'label' => Lang::get('permissions.' . $perm) ?: $perm];
                }
            }
            $group->permissionLabels = $labels;
        }

        $tpl->set('groups', $groups);
        $tpl->set('permissionsDefinitions', Permissions::translated());
        $tpl->set('token', $this->user->token);

        $response->addTemplate($tpl);
        return $response;
    }

    public function add()
    {
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('users', 'groupsadd');

        $tpl->set('link', $this->router->generate('groups-add-send'));
        $tpl->set('permissionsDefinitions', Permissions::translated());

        $response->addTemplate($tpl);
        return $response;
    }

    public function addSend()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            return $this->add();
        }

        if (!\Core\Security\Csrf::validate($_POST['_csrf'] ?? null)) {
            Show::msg(Lang::get('core-changes-not-saved'), 'error');
            return $this->add();
        }

        $name = trim($_POST['name'] ?? '');
        $slug = $this->generateSlug($name);
        $permissions = $_POST['permissions'] ?? [];

        if ($name === '') {
            Show::msg(Lang::get('groups-bad-entries'), 'error');
            return $this->add();
        }

        // Check if slug already exists
        if ($this->core->auth()->getGroupBySlug($slug) !== null) {
            Show::msg(Lang::get('groups-slug-exists'), 'error');
            return $this->add();
        }

        $group = new Group();
        $group->attributes['name'] = $name;
        $group->attributes['slug'] = $slug;
        $group->attributes['permissions'] = $this->sanitizePermissions($permissions);
        $group->attributes['system'] = false;
        $group->save();

        Show::msg(Lang::get('groups-added'), 'success');
        Core::getInstance()->getLogger()->log('INFO', 'Group added: ' . $name);
        $this->core->redirect($this->router->generate('groups-admin-home'));
    }

    public function edit($id)
    {
        $id = trim((string) $id);
        $group = $id !== '' ? Group::findPK($id) : null;
        if ($group === null) {
            $this->core->redirect($this->router->generate('groups-admin-home'));
        }

        if ($group->isSystem()) {
            Show::msg(Lang::get('groups-edit-system-forbidden'), 'error');
            $this->core->redirect($this->router->generate('groups-admin-home'));
        }

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('users', 'groupsedit');

        $tpl->set('link', $this->router->generate('groups-edit-send'));
        $tpl->set('group', $group);
        $tpl->set('permissionsDefinitions', Permissions::translated());

        $response->addTemplate($tpl);
        return $response;
    }

    public function editSend()
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            return $this->home();
        }

        if (!\Core\Security\Csrf::validate($_POST['_csrf'] ?? null)) {
            Show::msg(Lang::get('core-changes-not-saved'), 'error');
            $this->core->redirect($this->router->generate('groups-admin-home'));
        }

        $id = trim((string) ($_POST['id'] ?? ''));
        $group = $id !== '' ? Group::findPK($id) : null;
        $name = trim($_POST['name'] ?? '');
        $permissions = $_POST['permissions'] ?? [];

        if ($id === '' || $group === null || $name === '') {
            Show::msg(Lang::get('groups-bad-entries'), 'error');
            $this->core->redirect($this->router->generate('groups-admin-home'));
        }

        if ($group->isSystem()) {
            Show::msg(Lang::get('groups-edit-system-forbidden'), 'error');
            $this->core->redirect($this->router->generate('groups-admin-home'));
        }

        $group->attributes['name'] = $name;
        $group->attributes['permissions'] = $this->sanitizePermissions($permissions);
        $group->save();

        Show::msg(Lang::get('groups-edited'), 'success');
        Core::getInstance()->getLogger()->log('INFO', 'Group edited: ' . $name);
        $this->core->redirect($this->router->generate('groups-admin-home'));
    }

    public function delete($id, $token)
    {
        $evaluator = \Core\Navigation\AccessPolicyEvaluator::getInstance();
        $ctx = new \Core\Navigation\ConditionContext($this->user);
        if (!$evaluator->isAdmin($ctx)) {
            $this->core->redirect($this->router->generate('groups-admin-home'));
        }

        $id = trim((string) $id);
        $group = $id !== '' ? Group::findPK($id) : null;
        if ($group === null) {
            Show::msg(Lang::get('groups-not-found'), 'error');
            $this->core->redirect($this->router->generate('groups-admin-home'));
        }

        if ($group->isSystem()) {
            Show::msg(Lang::get('groups-delete-system-forbidden'), 'error');
            $this->core->redirect($this->router->generate('groups-admin-home'));
        }

        // Check if group has users
        $userCount = $this->countUsersInGroup($id);
        if ($userCount > 0) {
            Show::msg(Lang::get('groups-delete-has-users'), 'error');
            $this->core->redirect($this->router->generate('groups-admin-home'));
        }

        $name = $group->attributes['name'] ?? '';
        if ($group->delete()) {
            Show::msg(Lang::get('groups-deleted'), 'success');
            Core::getInstance()->getLogger()->log('INFO', 'Group deleted: ' . $name);
            $this->core->redirect($this->router->generate('groups-admin-home'));
        }

        Show::msg(Lang::get('core-changes-not-saved'), 'error');
        $this->core->redirect($this->router->generate('groups-admin-home'));
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

    protected function generateSlug(string $name): string
    {
        $slug = strtolower($name);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug ?: 'group-' . time();
    }

    protected function countUsersInGroup(int|string|null $groupId): int
    {
        if ($groupId === null || $groupId === '') {
            return 0;
        }
        $target = (string) $groupId;
        $count = 0;
        foreach (User::all() as $user) {
            $userGroup = $user->attributes['group_id'] ?? null;
            if ($userGroup !== null && (string) $userGroup === $target) {
                $count++;
            }
        }
        return $count;
    }
}

