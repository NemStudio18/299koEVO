<?php
namespace Subscription\Entities;

use Core\Storage\JsonActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property float $monthly_price
 * @property int $active
 * @property string $created_at
 * @property string $updated_at
 */
class Addon extends JsonActiveRecord
{
    protected static string $tableName = 'subscription_addons';
    protected static string $filePath = DATA_PLUGIN . 'subscription/subscription_addons.json';
}
