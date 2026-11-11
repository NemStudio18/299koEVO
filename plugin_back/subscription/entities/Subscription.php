<?php
namespace Subscription\Entities;

use Core\Storage\JsonActiveRecord;

/**
 * @property int $id
 * @property int $user_id
 * @property string $plan_type
 * @property string $status
 * @property float $monthly_price
 * @property float $annual_price
 * @property float $annual_discount
 * @property string $stripe_customer_id
 * @property string $stripe_subscription_id
 * @property string $started_at
 * @property string $expires_at
 * @property string $cancelled_at
 * @property string $created_at
 * @property string $updated_at
 */
class Subscription extends JsonActiveRecord
{
    protected static string $tableName = 'subscriptions';
    protected static string $filePath = DATA_PLUGIN . 'subscription/subscriptions.json';
}
