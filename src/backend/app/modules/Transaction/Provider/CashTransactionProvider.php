<?php

namespace MPM\Transaction\Provider;

use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use Illuminate\Database\Eloquent\Model;
use Inensus\WavecomPaymentProvider\Models\WaveComTransaction;

class CashTransactionProvider implements ITransactionProvider {
    /** @var array<string, mixed> */
    private array $validData = [];

    public function __construct(
        private CashTransaction $cashTransaction,
        private Transaction $transaction,
    ) {}

    public function saveTransaction(): void {
        $this->cashTransaction = new CashTransaction();
        $this->transaction = new Transaction();

        // assign data
        $this->assignData($this->validData);

        // save transaction
        $this->saveData($this->cashTransaction);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assignData(array $data): void {
        // provider specific data
        $this->cashTransaction->user_id = (int) $data['user_id'];

        // common transaction data
        $this->transaction->amount = (int) $data['amount'];
        $this->transaction->sender = 'User-'.$data['user_id'];
        $this->transaction->type = 'deferred_payment';
        $this->transaction->original_transaction_type = 'cash_transaction';
    }

    public function saveData(CashTransaction $cashTransaction): void {
        $cashTransaction->save();
    }

    public function sendResult(bool $requestType, Transaction $transaction): void {
        // TODO: Implement sendResult() method.
    }

    public function validateRequest(Transaction|WaveComTransaction $request): void {
        // TODO: Implement validateRequest() method.
    }

    public function confirm(): void {
        // TODO: Implement confirm() method.
    }

    public function getMessage(): string {
        return '';
    }

    public function getAmount(): int {
        return $this->transaction->amount;
    }

    public function getSender(): string {
        return $this->transaction->sender;
    }

    public function saveCommonData(): Model {
        return $this->cashTransaction->transaction()->save($this->transaction);
    }

    public function init(mixed $transaction): void {
        $this->cashTransaction = $transaction;
        $this->transaction = $transaction->transaction()->first();
    }

    public function addConflict(?string $message): void {
        $conflict = new TransactionConflicts();
        $conflict->state = $message;
        $conflict->transaction()->associate($this->cashTransaction);
        $conflict->save();
    }

    public function getTransaction(): Transaction {
        return $this->transaction;
    }
}
