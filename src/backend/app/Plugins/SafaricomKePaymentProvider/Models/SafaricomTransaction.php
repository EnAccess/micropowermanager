<?php

namespace App\Plugins\SafaricomKePaymentProvider\Models;

use App\Models\Transaction\BasePaymentProviderTransaction;
use Illuminate\Support\Carbon;

/**
 * @property int                          $id
 * @property float                        $amount
 * @property string                       $currency
 * @property string                       $order_id
 * @property string                       $reference_id
 * @property int                          $status
 * @property string|null                  $external_transaction_id
 * @property int                          $customer_id
 * @property string|null                  $serial_id
 * @property string|null                  $device_type
 * @property string                       $phone_number
 * @property string|null                  $checkout_request_id
 * @property string|null                  $merchant_request_id
 * @property string|null                  $mpesa_receipt_number
 * @property Carbon|null                  $transaction_date
 * @property string|null                  $account_reference
 * @property string|null                  $transaction_desc
 * @property array<array-key, mixed>|null $response_data
 * @property string|null                  $manufacturer_transaction_type
 * @property int|null                     $manufacturer_transaction_id
 * @property array<array-key, mixed>|null $metadata
 * @property int                          $attempts
 * @property Carbon|null                  $created_at
 * @property Carbon|null                  $updated_at
 */
class SafaricomTransaction extends BasePaymentProviderTransaction {
    public const RELATION_NAME = 'safaricom_transaction';

    public const STATUS_REQUESTED = 0;
    public const STATUS_FAILED = -1;
    public const STATUS_SUCCESS = 1;
    public const STATUS_COMPLETED = 2;
    public const STATUS_ABANDONED = 3;
    public const MAX_ATTEMPTS = 5;

    protected $table = 'safaricom_transactions';

    protected $casts = [
        'metadata' => 'array',
        'response_data' => 'array',
        'transaction_date' => 'datetime',
    ];

    public static function getTransactionName(): string {
        return self::RELATION_NAME;
    }
}
