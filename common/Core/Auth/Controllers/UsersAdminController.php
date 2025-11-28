<?php

namespace Core\Auth\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Auth\User;
use Core\Auth\Permissions;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 *
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

class UsersAdminController extends AdminController {

    public function home() {

        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('users', 'userslist');

        $users = User::all();
        $groups = $this->core->auth()->getGroups();
        $groupMap = [];
        foreach ($groups as $group) {
            $groupMap[$group->attributes['id'] ?? null] = $group;
        }

        foreach ($users as $user) {
            $user->deleteLink = $this->router->generate("users-delete", ["id" => $user->id , "token" => $this->user->token]);
            $groupId = $user->attributes['group_id'] ?? null;
            $group = $groupMap[$groupId] ?? null;
            $user->group_name = $group ? ($group->attributes['name'] ?? '') : '-';
            $user->group_slug = $group ? ($group->attributes['slug'] ?? '') : null;
        }
        $tpl->set('users', $users);
        $tpl->set('groups', $groups);
        $tpl->set('permissionsDefinitions', Permissions::translated());
        $tpl->set('token', $this->user->token);

        $response->addTemplate($tpl);
        return $response;
    }
}