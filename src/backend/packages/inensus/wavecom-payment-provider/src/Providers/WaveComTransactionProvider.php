<?php

declare(strict_types=1);

namespace Inensus\WavecomPaymentProvider\Providers;

use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Inensus\WavecomPaymentProvider\Models\WaveComTransaction;
use Inensus\WavecomPaymentProvider\Services\TransactionService;
use MPM\Transaction\Provider\ITransactionProvider;

class WaveComTransactionProvider implements ITransactionProvider {
    private Transaction $transaction;

    public function __construct(
        private TransactionService $transactionService,
        private SmsService $smsService,
        private TransactionConflicts $transactionConflicts,
        private WaveComTransaction $waveComTransaction,
    ) {}

    public function saveTransaction(): void {
        $this->transactionService->saveTransaction();
    }

    public function sendResult(bool $requestType, Transaction $transaction): void {
        /** @var WaveComTransaction $waveComTransaction */
        $waveComTransaction = $transaction->originalTransaction()->first();
        $this->transactionService->setStatus($waveComTransaction, $requestType);

        // only send confirmation sms
        if ($requestType) {
            $this->smsService->sendSms($transaction, SmsTypes::TRANSACTION_CONFIRMATION, SmsConfigs::class);
        } else {
            Log::error('wavecom transaction is been cancelled');
        }
    }

    public function validateRequest($request): void {
        // no need as the transaction initialized by uploading a separate file
    }

    public function confirm(): void {
        // TODO: Implement confirm() method.
    }

    public function getMessage(): string {
        return $this->getTransaction()->getMessage();
    }

    public function getAmount(): int {
        return $this->getTransaction()->getAmount();
    }

    public function getSender(): string {
        return $this->getTransaction()->getSender();
    }

    public function saveCommonData(): Model {
        // TODO: Implement saveCommonData() method.
    }

    public function init($transaction): void {
        $this->waveComTransaction = $transaction;
        $this->transaction = $transaction->transaction()->first();
    }

    public function addConflict(?string $message): void {
        $conflict = $this->transactionConflicts->newQuery()->make([
            'state' => $message,
        ]);
        $conflict->transaction()->associate($this->waveComTransaction);
        $conflict->save();
    }

    public function getTransaction(): Transaction {
        return $this->transaction;
    }
}
