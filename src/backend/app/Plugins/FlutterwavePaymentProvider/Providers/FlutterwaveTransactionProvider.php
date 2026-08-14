<?php

declare(strict_types=1);

namespace App\Plugins\FlutterwavePaymentProvider\Providers;

use App\Models\Transaction\ThirdPartyTransaction;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use App\Plugins\FlutterwavePaymentProvider\Models\FlutterwaveTransaction;
use App\Plugins\FlutterwavePaymentProvider\Services\FlutterwaveTransactionService;
use App\Providers\Interfaces\ITransactionProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class FlutterwaveTransactionProvider implements ITransactionProvider {
    public function __construct(
        private FlutterwaveTransaction $flutterwaveTransaction,
        private Transaction $transaction,
        private FlutterwaveTransactionService $flutterwaveTransactionService,
        private TransactionConflicts $transactionConflicts,
    ) {}

    /**
     * @param mixed $request
     */
    public function validateRequest($request): void {
        $meterSerial = $request->input('meterSerial');
        $amount = $request->input('amount');
        $this->flutterwaveTransactionService->validatePaymentOwner($meterSerial, $amount);
        $flutterwaveTransactionData = $this->flutterwaveTransactionService->initializeTransactionData();
        // We need to make sure that the payment is fully processable from our end.
        $this->flutterwaveTransactionService->create($flutterwaveTransactionData);
    }

    public function saveTransaction(): void {
        // The Flutterwave transaction is created by initiatePayment()/create() —
        // nothing further needs saving here once validateRequest() has run.
    }

    public function sendResult(bool $requestType, Transaction $transaction): void {
        /** @var FlutterwaveTransaction|ThirdPartyTransaction|null $flutterwaveTransaction */
        $flutterwaveTransaction = $transaction->originalTransaction()->first();
        if ($requestType && $flutterwaveTransaction !== null && $flutterwaveTransaction instanceof FlutterwaveTransaction) {
            $updateData = [
                'status' => FlutterwaveTransaction::STATUS_SUCCESS,
            ];
            $this->flutterwaveTransactionService->update($this->flutterwaveTransaction, $updateData);
        // SMS sent centrally via SendTransactionConfirmationSmsListener
        } else {
            Log::error('flutterwave transaction is been cancelled');
        }
    }

    public function addConflict(?string $message): void {
        $conflict = $this->transactionConflicts->newQuery()->make([
            'state' => $message,
        ]);
        $conflict->transaction()->associate($this->flutterwaveTransaction);
        $conflict->save();
    }

    /**
     * @param mixed $transaction
     */
    public function init($transaction): void {
        $this->flutterwaveTransaction = $transaction;
        $this->transaction = $transaction->transaction()->first();
    }

    public function confirm(): void {
        // TODO: Implement confirm() method.
    }

    public function getMessage(): string {
        // TODO: Implement getMessage() method.
        throw new \BadMethodCallException('Method getMessage() not yet implemented.');
    }

    public function getAmount(): float {
        return (float) $this->transaction->amount;
    }

    public function getSender(): string {
        return $this->transaction->message;
    }

    public function saveCommonData(): Model {
        // TODO: Implement saveCommonData() method.
        throw new \BadMethodCallException('Method saveCommonData() not yet implemented.');
    }
}
