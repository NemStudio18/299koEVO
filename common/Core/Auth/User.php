<?php

namespace Core\Auth;

use Core\Storage\JsonActiveRecord;
use Core\Auth\UsersManager;
use Core\Router\Router;
use Core\Core;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 */
defined('ROOT') or exit('Access denied!');

class User extends JsonActiveRecord
{
    protected static string $filePath = DATA_CORE_AUTH . 'users.json';

    protected static string $primaryKey = 'id';

    public function save(): bool
    {
        $this->attributes['token'] = $this->attributes['token'] ?? UsersManager::generateToken();
        $this->attributes['username'] = $this->normalizeUsername($this->attributes['username'] ?? null);
        $this->attributes['group_id'] = $this->normalizeGroupId($this->attributes['group_id'] ?? null);
        $this->attributes['status'] = $this->normalizeStatus($this->attributes['status'] ?? null);
        $this->attributes['permissions'] = $this->normalizePermissions($this->attributes['permissions'] ?? []);

        return parent::save();
    }

    /**
     * Vérifie qu'un token transmis dans la requête correspond à l'utilisateur.
     * Pour les actions admin, si l'utilisateur est connecté via session, il est automatiquement autorisé.
     */
    public function isAuthorized(): bool
    {
        // Si on est en mode admin et que l'utilisateur est dans la session, il est autorisé
        if (defined('IS_ADMIN') && IS_ADMIN === true) {
            if (isset($_SESSION['email']) && isset($this->attributes['email']) && $_SESSION['email'] === $this->attributes['email']) {
                return true;
            }
        }
        
        $matches = Router::getInstance()->match();
        if (isset($matches['params']['token'])) {
            return $matches['params']['token'] === $this->attributes['token'];
        }

        $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
        if ($contentType === "application/json") {
            $content = trim(file_get_contents("php://input"));
            $data = json_decode($content, true);
            if (isset($data['token'])) {
                return $data['token'] === $this->attributes['token'];
            }
        }

        if (!isset($_REQUEST['token'])) {
            return false;
        }

        return $_REQUEST['token'] == $this->attributes['token'];
    }

    public function getGroup(): ?Group
    {
        $groupId = $this->attributes['group_id'] ?? null;
        if ($groupId === null) {
            return null;
        }
        return Group::findPK((int) $groupId);
    }

    public function getPermissions(): array
    {
        if ($this->isSuperAdmin()) {
            return [Permissions::ALL];
        }

        $groupPermissions = $this->getGroup()?->getPermissions() ?? [];
        $userPermissions = $this->attributes['permissions'] ?? [];
        if (!is_array($userPermissions)) {
            $userPermissions = [];
        }

        return array_values(array_unique(array_merge($groupPermissions, $userPermissions)));
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        $permissions = $this->getPermissions();
        if (in_array(Permissions::ALL, $permissions, true)) {
            return true;
        }
        return in_array($permission, $permissions, true);
    }

    public function isSuperAdmin(): bool
    {
        return (int) ($this->attributes['id'] ?? 0) === 1;
    }

    public function canBeDeleted(): bool
    {
        return !$this->isSuperAdmin();
    }

    public function username(): string
    {
        return $this->attributes['username'] ?? '';
    }

    public function status(): string
    {
        return $this->attributes['status'] ?? 'active';
    }

    private function normalizeUsername(?string $username): string
    {
        $candidate = trim((string) $username);

        if ($candidate === '' && isset($this->attributes['email'])) {
            $candidate = substr($this->attributes['email'], 0, strpos($this->attributes['email'], '@') ?: strlen($this->attributes['email']));
        }

        $candidate = preg_replace('/[^a-z0-9_\-\.]/i', '', $candidate ?? '');

        if ($candidate === '' && isset($this->attributes['id'])) {
            $candidate = 'user' . $this->attributes['id'];
        }

        return $candidate !== '' ? $candidate : 'user' . time();
    }

    private function normalizeGroupId($groupId): int
    {
        if ($groupId !== null) {
            return (int) $groupId;
        }

        return Core::getInstance()->auth()->getDefaultGroupId();
    }

    private function normalizeStatus(?string $status): string
    {
        $allowed = ['active', 'pending', 'disabled'];
        $status = strtolower($status ?? 'active');
        return in_array($status, $allowed, true) ? $status : 'active';
    }

    private function normalizePermissions($permissions): array
    {
        if ($this->isSuperAdmin()) {
            return [Permissions::ALL];
        }

        if (!is_array($permissions)) {
            $permissions = [];
        }

        $permissions = array_map('strval', $permissions);
        $permissions = array_filter($permissions, function ($permission): bool {
            return $permission === Permissions::ALL || in_array($permission, Permissions::keys(), true);
        });

        return array_values(array_unique($permissions));
    }
}


