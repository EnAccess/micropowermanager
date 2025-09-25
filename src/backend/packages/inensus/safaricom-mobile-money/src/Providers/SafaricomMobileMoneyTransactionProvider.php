<?php

namespace Inensus\SafaricomMobileMoney\Providers;

use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Inensus\SafaricomMobileMoney\Models\SafaricomTransaction;
use Inensus\SafaricomMobileMoney\Services\SafaricomTransactionService;
use MPM\Transaction\Provider\ITransactionProvider;

class SafaricomMobileMoneyTransactionProvider implements ITransactionProvider {
    private array $validData = [];
    private Transaction $transaction;

    public function __construct(
        private SafaricomTransaction $safaricomTransaction,
        private TransactionConflicts $transactionConflicts,
        private SafaricomTransactionService $safaricomTransactionService,
        private SmsService $smsService,
    ) {}

    public function validateRequest($request): void {
        // Validation is handled during STK push initiation via controller/service.
        // If needed, basic presence checks can be added here.
        $this->validData = $request->all();
    }

    public function saveTransaction(): void {
        // Transactions are created during STK push initiation in the service layer.
        // This provider delegates persistence to SafaricomTransactionService when applicable.
        // Keeping method for interface compliance.
    }

    public function sendResult(bool $requestType, Transaction $transaction): void {
        /** @var SafaricomTransaction $safaricomSubTx */
        $safaricomSubTx = $transaction->originalTransaction()->first();

        if ($requestType) {
            // On success, send confirmation SMS similar to other providers
            $this->smsService->sendSms(
                $transaction->toArray(),
                SmsTypes::TRANSACTION_CONFIRMATION,
                SmsConfigs::class
            );
        } else {
            Log::error('Safaricom transaction is being cancelled', [
                'transaction_id' => $transaction->id,
                'original_transaction_id' => optional($safaricomSubTx)->id,
            ]);
        }
    }

    public function confirm(): void {
        // Confirmation is driven by webhook callbacks for M-Pesa.
    }

    public function getMessage(): string {
        return $this->getTransaction()->message ?? '';
    }

    public function getAmount(): int {
        return (int) $this->getTransaction()->amount;
    }

    public function getSender(): string {
        return (string) ($this->getTransaction()->message ?? '');
    }

    public function saveCommonData(): Model {
        throw new \BadMethodCallException('Method saveCommonData() not yet implemented.');
    }

    public function init(mixed $transaction): void {
        $this->safaricomTransaction = $transaction;
        $this->transaction = $transaction->transaction()->first();
    }

    public function addConflict(?string $message): void {
        $conflict = $this->transactionConflicts->newQuery()->make([
            'state' => $message,
        ]);
        $conflict->transaction()->associate($this->safaricomTransaction);
        $conflict->save();
    }

    public function getTransaction(): Transaction {
        return $this->transaction;
    }
}
