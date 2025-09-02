<?php

declare(strict_types=1);

namespace Inensus\PaystackPaymentProvider\Providers;

use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Inensus\PaystackPaymentProvider\Models\PaystackTransaction;
use Inensus\PaystackPaymentProvider\Services\PaystackTransactionService;
use MPM\Transaction\Provider\ITransactionProvider;

class PaystackTransactionProvider implements ITransactionProvider {
    private $validData = [];

    public function __construct(
        private PaystackTransaction $paystackTransaction,
        private Transaction $transaction,
        private PaystackTransactionService $paystackTransactionService,
        private TransactionConflicts $transactionConflicts,
    ) {}

    public function validateRequest($request): void {
        $meterSerial = $request->input('meterSerial');
        $amount = $request->input('amount');

        try {
            $this->paystackTransactionService->validatePaymentOwner($meterSerial, $amount);
            $paystackTransactionData = $this->paystackTransactionService->initializeTransactionData();

            // We need to make sure that the payment is fully processable from our end .
            $this->paystackTransactionService->imitateTransactionForValidation($paystackTransactionData);
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }

        $this->setValidData($paystackTransactionData);
    }

    public function saveTransaction(): void {
        $this->paystackTransactionService->saveTransaction();
    }

    public function sendResult(bool $requestType, Transaction $transaction): void {
        $paystackTransaction = $transaction->originalTransaction()->first();
        if ($requestType) {
            $updateData = [
                'status' => PaystackTransaction::STATUS_SUCCESS,
            ];
            $this->paystackTransactionService->update($paystackTransaction, $updateData);
            $smsService = app()->make(SmsService::class);
            $smsService->sendSms($transaction, SmsTypes::TRANSACTION_CONFIRMATION, SmsConfigs::class);
        } else {
            Log::error('paystack transaction is been cancelled');
        }
    }

    public function addConflict(?string $message): void {
        $conflict = $this->transactionConflicts->newQuery()->make([
            'state' => $message,
        ]);
        $conflict->transaction()->associate($this->paystackTransaction);
        $conflict->save();
    }

    public function getTransaction(): Transaction {
        return $this->transaction;
    }

    public function setValidData($paystackTransactionData) {
        $this->validData = $paystackTransactionData;
    }

    public function getSubTransaction() {
        return $this->paystackTransactionService->getPaystackTransaction();
    }

    public function init($transaction): void {
        $this->paystackTransaction = $transaction;
        $this->transaction = $transaction->transaction()->first();
    }

    public function confirm(): void {
        // TODO: Implement confirm() method.
    }

    public function getMessage(): string {
        // TODO: Implement getMessage() method.
        throw new \BadMethodCallException('Method getMessage() not yet implemented.');
    }

    public function getAmount(): int {
        return $this->getTransaction()->amount;
    }

    public function getSender(): string {
        return $this->getTransaction()->message;
    }

    public function saveCommonData(): Model {
        // TODO: Implement saveCommonData() method.
        throw new \BadMethodCallException('Method saveCommonData() not yet implemented.');
    }
}
