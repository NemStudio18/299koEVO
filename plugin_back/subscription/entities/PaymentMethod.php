<?php
namespace Subscription\Entities;

use Core\Storage\JsonActiveRecord;

/**
 * @property int $id
 * @property int $user_id
 * @property string $stripe_payment_method_id
 * @property string $card_type
 * @property string $card_last4
 * @property int $card_exp_month
 * @property int $card_exp_year
 * @property int $is_default
 * @property string $created_at
 */
class PaymentMethod extends JsonActiveRecord
{
    protected static string $tableName = 'subscription_payment_methods';
    protected static string $filePath = DATA_PLUGIN . 'subscription/subscription_payment_methods.json';
}
