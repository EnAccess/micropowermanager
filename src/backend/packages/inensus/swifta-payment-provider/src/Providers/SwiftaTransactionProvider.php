<?php

namespace Inensus\SwiftaPaymentProvider\Providers;

use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Inensus\SwiftaPaymentProvider\Models\SwiftaTransaction;
use Inensus\SwiftaPaymentProvider\Services\SwiftaTransactionService;
use MPM\Transaction\Provider\ITransactionProvider;

class SwiftaTransactionProvider implements ITransactionProvider {
    private $validData = [];

    public function __construct(
        private SwiftaTransaction $swiftaTransaction,
        private Transaction $transaction,
        private SwiftaTransactionService $swiftaTransactionService,
        private TransactionConflicts $transactionConflicts,
    ) {}

    public function validateRequest($request): void {
        $meterSerial = $request->input('meter_number');
        $amount = $request->input('amount');

        try {
            $this->swiftaTransactionService->validatePaymentOwner($meterSerial, $amount);
            $swiftaTransactionData = $this->swiftaTransactionService->initializeTransactionData($request->all());

            // We need to make sure that the payment is fully processable from our end .
            $this->swiftaTransactionService->imitateTransactionForValidation($swiftaTransactionData);
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }

        $this->setValidData($swiftaTransactionData);
    }

    public function saveTransaction(): void {
        $this->swiftaTransactionService->saveTransaction();
    }

    public function sendResult(bool $requestType, Transaction $transaction): void {
        $swiftaTransaction = $transaction->originalTransaction()->first();
        if ($requestType) {
            $updateData = [
                'status' => SwiftaTransaction::STATUS_SUCCESS,
            ];
            $this->swiftaTransactionService->update($swiftaTransaction, $updateData);
            $smsService = app()->make(SmsService::class);
            $smsService->sendSms($transaction, SmsTypes::TRANSACTION_CONFIRMATION, SmsConfigs::class);
        } else {
            Log::error('swifta transaction is been cancelled');
        }
    }

    public function addConflict(?string $message): void {
        $conflict = $this->transactionConflicts->newQuery()->make([
            'state' => $message,
        ]);
        $conflict->transaction()->associate($this->swiftaTransaction);
        $conflict->save();
    }

    public function getTransaction(): Transaction {
        return $this->transaction;
    }

    public function setValidData($swiftaTransactionData) {
        $this->validData = $swiftaTransactionData;
    }

    public function getSubTransaction() {
        return $this->swiftaTransactionService->getSwiftaTransaction();
    }

    public function init($transaction): void {
        $this->swiftaTransaction = $transaction;
        $this->transaction = $transaction->transaction()->first();
    }

    public function confirm(): void {
        // TODO: Implement getMessage() method.
    }

    public function getMessage(): string {
        // TODO: Implement getMessage() method.
    }

    public function getAmount(): int {
        return $this->getTransaction()->amount;
    }

    public function getSender(): string {
        return $this->getTransaction()->message;
    }

    public function saveCommonData(): Model {
        // TODO: Implement getSender() method.
    }
}
