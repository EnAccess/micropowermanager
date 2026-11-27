<?php

namespace App\Plugins\AirtelPaymentProvider\Providers;

use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use App\Plugins\AirtelPaymentProvider\Http\Middleware\VerifyAirtelToken;
use App\Plugins\AirtelPaymentProvider\Models\AirtelTransaction;
use App\Plugins\AirtelPaymentProvider\Services\AirtelTransactionService;
use App\Providers\Interfaces\ITransactionProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AirtelTransactionProvider implements ITransactionProvider {
    public function __construct(
        private AirtelTransaction $airtelTransaction,
        private Transaction $transaction,
        private AirtelTransactionService $airtelTransactionService,
        private TransactionConflicts $transactionConflicts,
    ) {}

    public function validateRequest(Request $request): void {
        $this->airtelTransactionService->validateCommand($request->attributes->get(VerifyAirtelToken::COMMAND_ATTRIBUTE));
    }

    public function saveTransaction(): void {
        $this->airtelTransactionService->saveTransaction();
    }

    /**
     * Airtel is not called back. It learns the outcome from the status when it sends an enquiry.
     */
    public function sendResult(bool $requestType, Transaction $transaction): void {
        $airtelTransaction = $transaction->originalTransaction()->first();

        if (!$airtelTransaction instanceof AirtelTransaction) {
            return;
        }

        $airtelTransaction->update([
            'status' => $requestType ? AirtelTransaction::STATUS_SUCCESS : AirtelTransaction::STATUS_FAILED,
        ]);
    }

    public function confirm(): void {}

    public function getMessage(): string {
        return $this->transaction->message;
    }

    public function getAmount(): float {
        return $this->transaction->amount;
    }

    public function getSender(): string {
        return $this->transaction->sender;
    }

    public function saveCommonData(): Model {
        return $this->airtelTransaction->transaction()->save($this->transaction);
    }

    /**
     * @param AirtelTransaction $transaction
     */
    public function init(BasePaymentProviderTransaction $transaction): void {
        $this->airtelTransaction = $transaction;
        $this->transaction = $transaction->transaction()->first();
    }

    public function addConflict(?string $message): void {
        $conflict = $this->transactionConflicts->newQuery()->make(['state' => $message]);
        $conflict->transaction()->associate($this->airtelTransaction);
        $conflict->save();
    }
}
