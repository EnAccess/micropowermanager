<?php

declare(strict_types=1);

namespace App\Plugins\SmsTransactionParser\Models;

use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\Transaction;
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
 * @property      string|null                           $manufacturer_transaction_type
 * @property      int|null                              $manufacturer_transaction_id
 * @property      Carbon|null                           $created_at
 * @property      Carbon|null                           $updated_at
 * @property-read Model|null                            $manufacturerTransaction
 * @property-read Transaction|null                      $transaction
 */
class SmsTransaction extends BasePaymentProviderTransaction {
    protected $table = 'sms_transactions';
    public const RELATION_NAME = 'sms_transaction';
    public const STATUS_FAILED = -1;
    public const STATUS_PENDING = 0;
    public const STATUS_SUCCESS = 1;

    public function getTransactionReference(): string {
        return $this->transaction_reference;
    }

    public function getSenderPhone(): string {
        return $this->sender_phone;
    }

    public function getDeviceSerial(): ?string {
        return $this->device_serial;
    }

    public function getAmount(): float {
        return $this->amount;
    }

    public function getRawMessage(): string {
        return $this->raw_message;
    }

    public function getProviderName(): string {
        return $this->provider_name;
    }

    public function setStatus(int $status): void {
        $this->status = $status;
    }

    public function getManufacturerTransferType(): ?string {
        return 'SmsTransaction';
    }

    public function getDescription(): ?string {
        return $this->raw_message;
    }

    public static function getTransactionName(): string {
        return self::RELATION_NAME;
    }

    public function getId(): int {
        return $this->id;
    }
}
