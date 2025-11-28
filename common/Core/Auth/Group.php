<?php

namespace Core\Auth;

use Core\Storage\JsonActiveRecord;

defined('ROOT') or exit('Access denied!');

class Group extends JsonActiveRecord
{
    protected static string $filePath = DATA_CORE_AUTH . 'groups.json';

    public function getSlug(): string
    {
        return $this->attributes['slug'] ?? '';
    }

    public function getName(): string
    {
        return $this->attributes['name'] ?? '';
    }

    public function getPermissions(): array
    {
        $permissions = $this->attributes['permissions'] ?? [];
        return is_array($permissions) ? $permissions : [];
    }

    public function isSystem(): bool
    {
        return (bool) ($this->attributes['system'] ?? false);
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = $this->getPermissions();
        if (in_array(Permissions::ALL, $permissions, true)) {
            return true;
        }
        return in_array($permission, $permissions, true);
    }
}

