<?php

namespace Core\Navigation;

use Core\Storage\JsonActiveRecord;

/**
 * Entité représentant un élément de menu indépendant des pages CMS.
 */
defined('ROOT') or exit('Access denied!');

class MenuItem extends JsonActiveRecord
{
    public const VALID_LOCATIONS = ['main', 'avatar', 'footer'];
    public const VALID_TYPES = ['page', 'plugin', 'url', 'folder'];
    public const VALID_VISIBILITIES = ['public', 'guest', 'logged', 'admin', 'roles'];

    protected static string $filePath = DATA_CORE . 'menu_items.json';
    protected static string $tableName = 'menu_items';
    protected static string $primaryKey = 'id';
    protected static string $storageDriver = self::DRIVER_SQL;

    protected static array $attributeMapping = [
        'id'         => 'id',
        'location'   => 'location',
        'parentId'   => 'parent_id',
        'position'   => 'position',
        'type'       => 'type',
        'targetRef'  => 'target_ref',
        'label'      => 'label',
        'icon'       => 'icon',
        'cssClass'   => 'css_class',
        'targetAttr' => 'target_attr',
        'visibility' => 'visibility',
        'isEnabled'  => 'is_enabled',
    ];

    protected static array $casts = [
        'visibility' => 'array',
    ];

    protected static array $indexes = ['location', 'parent_id'];
    protected static bool $isBooted = false;

    /**
     * Schéma SQLite/MySQL pour auto-création de table via JsonActiveRecord.
     */
    protected static function getSchema(): array
    {
        return [
            'location'    => 'string',
            'parent_id'   => 'int',
            'position'    => 'int',
            'type'        => 'string',
            'target_ref'  => 'string',
            'label'       => 'string',
            'icon'        => 'string',
            'css_class'   => 'string',
            'target_attr' => 'string',
            'visibility'  => 'array',
            'is_enabled'  => 'int',
        ];
    }

    public function getId(): int
    {
        return (int)($this->attributes['id'] ?? 0);
    }

    public function getLocation(): string
    {
        $loc = (string)($this->attributes['location'] ?? 'main');
        return in_array($loc, self::VALID_LOCATIONS, true) ? $loc : 'main';
    }

    public function setLocation(string $location): void
    {
        $this->attributes['location'] = in_array($location, self::VALID_LOCATIONS, true) ? $location : 'main';
    }

    public function getParentId(): int
    {
        return (int)($this->attributes['parentId'] ?? $this->attributes['parent_id'] ?? 0);
    }

    public function setParentId(int $parentId): void
    {
        $this->attributes['parentId'] = max(0, $parentId);
    }

    public function getPosition(): int
    {
        return (int)($this->attributes['position'] ?? 0);
    }

    public function setPosition(int $position): void
    {
        $this->attributes['position'] = $position;
    }

    public function getType(): string
    {
        $type = (string)($this->attributes['type'] ?? 'page');
        return in_array($type, self::VALID_TYPES, true) ? $type : 'url';
    }

    public function setType(string $type): void
    {
        $this->attributes['type'] = in_array($type, self::VALID_TYPES, true) ? $type : 'url';
    }

    public function getTargetRef(): string
    {
        return (string)($this->attributes['targetRef'] ?? $this->attributes['target_ref'] ?? '');
    }

    public function setTargetRef(string $targetRef): void
    {
        $this->attributes['targetRef'] = trim($targetRef);
    }

    public function getLabel(): string
    {
        return (string)($this->attributes['label'] ?? '');
    }

    public function setLabel(string $label): void
    {
        $this->attributes['label'] = trim($label);
    }

    public function getIcon(): string
    {
        return (string)($this->attributes['icon'] ?? '');
    }

    public function setIcon(string $icon): void
    {
        $this->attributes['icon'] = trim($icon);
    }

    public function getCssClass(): string
    {
        return (string)($this->attributes['cssClass'] ?? $this->attributes['css_class'] ?? '');
    }

    public function setCssClass(string $cssClass): void
    {
        $this->attributes['cssClass'] = trim($cssClass);
    }

    public function getTargetAttr(): string
    {
        $attr = (string)($this->attributes['targetAttr'] ?? $this->attributes['target_attr'] ?? '_self');
        return ($attr === '_blank') ? '_blank' : '_self';
    }

    public function setTargetAttr(string $targetAttr): void
    {
        $this->attributes['targetAttr'] = ($targetAttr === '_blank') ? '_blank' : '_self';
    }

    public function getVisibility(): array
    {
        $vis = $this->attributes['visibility'] ?? [];
        if (is_string($vis)) {
            $vis = json_decode($vis, true) ?: [];
        }
        $mode = $vis['mode'] ?? 'public';
        if (!in_array($mode, self::VALID_VISIBILITIES, true)) {
            $mode = 'public';
        }
        $roles = is_array($vis['roles'] ?? null) ? array_values($vis['roles']) : [];
        return [
            'mode'  => $mode,
            'roles' => $roles,
        ];
    }

    public function setVisibility(array $visibility): void
    {
        $mode = $visibility['mode'] ?? 'public';
        if (!in_array($mode, self::VALID_VISIBILITIES, true)) {
            $mode = 'public';
        }
        $roles = is_array($visibility['roles'] ?? null) ? array_values(array_filter($visibility['roles'])) : [];
        $this->attributes['visibility'] = [
            'mode'  => $mode,
            'roles' => $roles,
        ];
    }

    public function getIsEnabled(): int
    {
        return (int)($this->attributes['isEnabled'] ?? $this->attributes['is_enabled'] ?? 1);
    }

    public function isEnabled(): bool
    {
        return $this->getIsEnabled() === 1;
    }

    public function setIsEnabled($val): void
    {
        $this->attributes['isEnabled'] = ((int)$val === 1 || $val === true) ? 1 : 0;
    }

    /**
     * Convertit la règle de visibilité MenuItem vers le format attendu par NavigationItem.
     */
    public function toNavigationVisibility(array $extraConditions = []): array
    {
        $vis = $this->getVisibility();
        $mode = $vis['mode'];
        $conditions = $extraConditions;

        $result = [
            'public'     => false,
            'logged'     => false,
            'admin'      => false,
            'roles'      => [],
            'conditions' => $conditions,
        ];

        switch ($mode) {
            case 'guest':
                $result['conditions'][] = ['type' => 'auth', 'value' => false];
                break;
            case 'logged':
                $result['logged'] = true;
                $result['conditions'][] = ['type' => 'auth', 'value' => true];
                break;
            case 'admin':
                $result['admin'] = true;
                $result['conditions'][] = ['type' => 'role', 'value' => 'admin'];
                break;
            case 'roles':
                $result['roles'] = $vis['roles'];
                foreach ($vis['roles'] as $role) {
                    $result['conditions'][] = ['type' => 'role', 'value' => $role];
                }
                break;
            case 'public':
            default:
                $result['public'] = empty($extraConditions);
                break;
        }

        return $result;
    }
}
