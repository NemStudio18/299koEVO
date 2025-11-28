<?php

namespace Core\Auth;

use Core\Lang;

defined('ROOT') or exit('Access denied!');

/**
 * Central registry for ACL permissions exposed to groups and users.
 */
class Permissions
{
    public const ALL = '*';

    /**
     * Returns structured definitions grouped by category.
     *
     * @return array<string,array{label:string,items:array<string,string>}>
     */
    public static function definitions(): array
    {
        return [
            'core' => [
                'label' => 'permissions.category.core',
                'items' => [
                    'admin.access' => 'permissions.admin.access',
                    'config.manage' => 'permissions.config.manage',
                ],
            ],
            'content' => [
                'label' => 'permissions.category.content',
                'items' => [
                    'pages.manage' => 'permissions.pages.manage',
                ],
            ],
            'media' => [
                'label' => 'permissions.category.media',
                'items' => [
                    'media.manage' => 'permissions.media.manage',
                ],
            ],
            'extensions' => [
                'label' => 'permissions.category.extensions',
                'items' => [
                    'extensions.manage' => 'permissions.extensions.manage',
                    'marketplace.install' => 'permissions.marketplace.install',
                ],
            ],
            'users' => [
                'label' => 'permissions.category.users',
                'items' => [
                    'users.manage' => 'permissions.users.manage',
                    'groups.manage' => 'permissions.groups.manage',
                ],
            ],
            'themes' => [
                'label' => 'permissions.category.themes',
                'items' => [
                    'themes.manage' => 'permissions.themes.manage',
                ],
            ],
            'profile' => [
                'label' => 'permissions.category.profile',
                'items' => [
                    'profile.view' => 'permissions.profile.view',
                    'profile.edit' => 'permissions.profile.edit',
                ],
            ],
        ];
    }

    /**
     * Flattened list of known permission keys (excluding wildcard).
     *
     * @return string[]
     */
    public static function keys(): array
    {
        $keys = [];
        foreach (self::definitions() as $definition) {
            foreach ($definition['items'] as $key => $label) {
                $keys[] = $key;
            }
        }
        return $keys;
    }

    /**
     * Definitions with translated labels ready for UI consumption.
     *
     * @return array
     */
    public static function translated(): array
    {
        $resolved = [];
        foreach (self::definitions() as $groupKey => $definition) {
            $items = [];
            foreach ($definition['items'] as $permission => $labelKey) {
                $items[] = [
                    'key' => $permission,
                    'label' => Lang::get($labelKey),
                ];
            }
            $resolved[$groupKey] = [
                'label' => Lang::get($definition['label']),
                'items' => $items,
            ];
        }
        return $resolved;
    }
}

