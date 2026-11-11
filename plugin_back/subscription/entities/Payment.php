<?php
namespace Subscription\Entities;

use Core\Storage\JsonActiveRecord;

/**
 * @property int $id
 * @property int $subscription_id
 * @property int $user_id
 * @property float $amount
 * @property string $currency
 * @property string $stripe_payment_intent_id
 * @property string $stripe_invoice_id
 * @property string $status
 * @property string $payment_type
 * @property string $period_start
 * @property string $period_end
 * @property string $created_at
 */
class Payment extends JsonActiveRecord
{
    protected static string $tableName = 'subscription_payments';
    protected static string $filePath = DATA_PLUGIN . 'subscription/subscription_payments.json';
}
