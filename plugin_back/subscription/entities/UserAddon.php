<?php
namespace Subscription\Entities;

use Core\Storage\JsonActiveRecord;

/**
 * @property int $id
 * @property int $subscription_id
 * @property int $addon_id
 * @property int $user_id
 * @property string $status
 * @property string $started_at
 * @property string $cancelled_at
 * @property string $created_at
 */
class UserAddon extends JsonActiveRecord
{
    protected static string $tableName = 'subscription_user_addons';
    protected static string $filePath = DATA_PLUGIN . 'subscription/subscription_user_addons.json';
}
