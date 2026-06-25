<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Providers;

use App\Models\Transaction\ThirdPartyTransaction;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use App\Plugins\PesapalPaymentProvider\Models\PesapalTransaction;
use App\Plugins\PesapalPaymentProvider\Services\PesapalTransactionService;
use App\Providers\Interfaces\ITransactionProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class PesapalTransactionProvider implements ITransactionProvider {
    public function __construct(
        private PesapalTransaction $pesapalTransaction,
        private Transaction $transaction,
        private PesapalTransactionService $pesapalTransactionService,
        private TransactionConflicts $transactionConflicts,
    ) {}

    /**
     * @param mixed $request
     */
    public function validateRequest($request): void {
        $meterSerial = $request->input('meterSerial');
        $amount = $request->input('amount');
        $this->pesapalTransactionService->validatePaymentOwner($meterSerial, $amount);
        $pesapalTransactionData = $this->pesapalTransactionService->initializeTransactionData();
        $this->pesapalTransactionService->imitateTransactionForValidation($pesapalTransactionData);
    }

    public function saveTransaction(): void {
        $this->pesapalTransactionService->saveTransaction();
    }

    public function sendResult(bool $requestType, Transaction $transaction): void {
        /** @var PesapalTransaction|ThirdPartyTransaction|null $pesapalTransaction */
        $pesapalTransaction = $transaction->originalTransaction()->first();
        if ($requestType && $pesapalTransaction !== null && $pesapalTransaction instanceof PesapalTransaction) {
            $updateData = [
                'status' => PesapalTransaction::STATUS_SUCCESS,
            ];
            $this->pesapalTransactionService->update($this->pesapalTransaction, $updateData);
        } else {
            Log::error('pesapal transaction is being cancelled');
        }
    }

    public function addConflict(?string $message): void {
        $conflict = $this->transactionConflicts->newQuery()->make([
            'state' => $message,
        ]);
        $conflict->transaction()->associate($this->pesapalTransaction);
        $conflict->save();
    }

    /**
     * @param mixed $transaction
     */
    public function init($transaction): void {
        $this->pesapalTransaction = $transaction;
        $this->transaction = $transaction->transaction()->first();
    }

    public function confirm(): void {}

    public function getMessage(): string {
        throw new \BadMethodCallException('Method getMessage() not yet implemented.');
    }

    public function getAmount(): float {
        return (float) $this->transaction->amount;
    }

    public function getSender(): string {
        return $this->transaction->message;
    }

    public function saveCommonData(): Model {
        throw new \BadMethodCallException('Method saveCommonData() not yet implemented.');
    }
}
