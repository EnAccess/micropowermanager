<?php

namespace Inensus\WaveMoneyPaymentProvider\Models;

use App\Models\Base\BaseModel;
use App\Models\Transaction\ManufacturerTransactionInterface;
use App\Models\Transaction\PaymentProviderTransactionInterface;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int         $id
 * @property int         $amount
 * @property string      $currency
 * @property string      $order_id
 * @property string      $reference_id
 * @property string      $status
 * @property string      $external_transaction_id
 * @property int         $customer_id
 * @property string|null $meter_serial
 *
 * @implements PaymentProviderTransactionInterface<WaveMoneyTransaction>
 */
class WaveMoneyTransaction extends BaseModel implements PaymentProviderTransactionInterface {
    public const RELATION_NAME = 'wave_money_transaction';

    public const STATUS_REQUESTED = 0;
    public const STATUS_FAILED = -1;
    public const STATUS_SUCCESS = 1;
    public const STATUS_COMPLETED_BY_WAVE_MONEY = 2;
    public const MAX_ATTEMPTS = 5;

    protected $table = 'wave_money_transactions';

    public function getAmount(): int {
        return $this->amount;
    }

    public function getCurrency(): string {
        return $this->currency;
    }

    public function getOrderId(): string {
        return $this->order_id;
    }

    public function getReferenceId(): string {
        return $this->reference_id;
    }

    public function getId(): int {
        return $this->id;
    }

    public function setStatus(int $status) {
        $this->status = $status;
    }

    public function setExternalTransactionId(string $transactionId) {
        $this->external_transaction_id = $transactionId;
    }

    public function setOrderId(string $orderId) {
        $this->order_id = $orderId;
    }

    public function setReferenceId(string $referenceId) {
        $this->reference_id = $referenceId;
    }

    public function setCustomerId(int $customerId) {
        $this->customer_id = $customerId;
    }

    public function setMeterSerial(string $meterSerialNumber) {
        $this->meter_serial = $meterSerialNumber;
    }

    public function setAmount(float $amount) {
        $this->amount = $amount;
    }

    /**
     * @return MorphOne<Transaction, WaveMoneyTransaction>
     */
    public function transaction(): MorphOne {
        /** @var MorphOne<Transaction, WaveMoneyTransaction> */
        $relation = $this->morphOne(Transaction::class, 'original_transaction');

        return $relation;
    }

    /**
     * @return MorphTo<Model&ManufacturerTransactionInterface, WaveMoneyTransaction>
     */
    public function manufacturerTransaction(): MorphTo {
        /** @var MorphTo<Model&ManufacturerTransactionInterface, WaveMoneyTransaction> */
        $relation = $this->morphTo();

        return $relation;
    }

    public function conflicts() {
        return $this->morphMany(TransactionConflicts::class, 'transaction');
    }

    public static function getTransactionName(): string {
        return self::RELATION_NAME;
    }
}
