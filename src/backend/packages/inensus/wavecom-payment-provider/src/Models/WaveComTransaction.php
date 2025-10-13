<?php

declare(strict_types=1);

namespace Inensus\WavecomPaymentProvider\Models;

use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\TransactionConflicts;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int    $id
 * @property string $transaction_id
 * @property string $sender
 * @property string $message
 * @property int    $amount
 * @property int    $status
 */
class WaveComTransaction extends BasePaymentProviderTransaction {
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

    public function setStatus(int $status): void {
        $this->status = $status;
    }

    public static function getTransactionName(): string {
        return self::RELATION_NAME;
    }

    public function getId(): int {
        return $this->id;
    }
}
