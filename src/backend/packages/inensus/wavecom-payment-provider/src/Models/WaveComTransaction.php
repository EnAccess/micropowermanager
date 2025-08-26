<?php

declare(strict_types=1);

namespace Inensus\WavecomPaymentProvider\Models;

use App\Models\Base\BaseModel;
use App\Models\Transaction\ManufacturerTransactionInterface;
use App\Models\Transaction\PaymentProviderTransactionInterface;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int    $id
 * @property string $transaction_id
 * @property string $sender
 * @property string $message
 * @property int    $amount
 * @property int    $status
 *
 * @implements PaymentProviderTransactionInterface<WaveComTransaction>
 */
class WaveComTransaction extends BaseModel implements PaymentProviderTransactionInterface {
    protected $table = 'wavecom_transactions';
    public const RELATION_NAME = 'wavecom_transaction';
    public const STATUS_SUCCESS = 1;
    public const STATUS_CANCELLED = -1;

    public function getTransactionId(): string {
        return $this->transaction_id;
    }

    public function getSender(): string {
        return $this->sender;
    }

    public function getMessage(): string {
        return $this->message;
    }

    public function getAmount(): int {
        return $this->amount;
    }

    public function setTransactionId(string $transactionId): void {
        $this->transaction_id = $transactionId;
    }

    public function setSender(string $sender): void {
        $this->sender = $sender;
    }

    public function setMessage(string $message): void {
        $this->message = $message;
    }

    public function setAmount(int $amount): void {
        $this->amount = $amount;
    }

    /**
     * @return MorphOne<Transaction, WaveComTransaction>
     */
    public function transaction(): MorphOne {
        /** @var MorphOne<Transaction, WaveComTransaction> */
        $relation = $this->morphOne(Transaction::class, 'original_transaction');

        return $relation;
    }

    /**
     * @return MorphTo<Model&ManufacturerTransactionInterface, WaveComTransaction>
     */
    public function manufacturerTransaction(): MorphTo {
        /** @var MorphTo<Model&ManufacturerTransactionInterface, WaveComTransaction> */
        $relation = $this->morphTo();

        return $relation;
    }

    public function conflicts(): MorphMany {
        return $this->morphMany(TransactionConflicts::class, 'transaction');
    }

    public function getManufacturerTransferType(): ?string {
        // TODO add type API/IMPORT
        return 'WaveCom';
    }

    public function getDescription(): ?string {
        return $this->getMessage();
    }

    public function setStatus(int $status) {
        $this->status = $status;
    }

    public static function getTransactionName(): string {
        return self::RELATION_NAME;
    }

    public function getId(): int {
        return $this->id;
    }
}
