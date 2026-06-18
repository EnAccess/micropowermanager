<?php

declare(strict_types=1);

namespace App\Plugins\SmsTransactionParser\Models;

use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property      int                                   $id
 * @property      string                                $provider_name
 * @property      string                                $transaction_reference
 * @property      float                                 $amount
 * @property      string                                $sender_phone
 * @property      string|null                           $device_serial
 * @property      string                                $raw_message
 * @property      int                                   $status
 * @property      string|null                           $manufacturer_transaction_type
 * @property      int|null                              $manufacturer_transaction_id
 * @property      Carbon|null                           $created_at
 * @property      Carbon|null                           $updated_at
 * @property-read Collection<int, TransactionConflicts> $conflicts
 * @property-read Model|null                            $manufacturerTransaction
 * @property-read Transaction|null                      $transaction
 */
class SmsTransaction extends BasePaymentProviderTransaction {
    protected $table = 'sms_transactions';
    public const RELATION_NAME = 'sms_transaction';
    public const STATUS_FAILED = -1;
    public const STATUS_PENDING = 0;
    public const STATUS_SUCCESS = 1;

    public function getManufacturerTransferType(): ?string {
        return 'SmsTransaction';
    }

    public static function getTransactionName(): string {
        return self::RELATION_NAME;
    }
}
