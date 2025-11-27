<?php

namespace Inensus\SwiftaPaymentProvider\Providers;

use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use App\Services\Interfaces\ITransactionProvider;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inensus\SwiftaPaymentProvider\Models\SwiftaTransaction;
use Inensus\SwiftaPaymentProvider\Services\SwiftaTransactionService;
use Inensus\WavecomPaymentProvider\Models\WaveComTransaction;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;

class SwiftaTransactionProvider implements ITransactionProvider {
    /** @var array<string, mixed> */
    private array $validData = [];

    public function __construct(
        private SwiftaTransaction $swiftaTransaction,
        private Transaction $transaction,
        private SwiftaTransactionService $swiftaTransactionService,
        private TransactionConflicts $transactionConflicts,
    ) {}

    public function validateRequest(Request $request): void {
        $meterSerial = $request->input('meter_number');
        $amount = $request->input('amount');

        try {
            $this->swiftaTransactionService->validatePaymentOwner($meterSerial, $amount);
            $swiftaTransactionData = $this->swiftaTransactionService->initializeTransactionData($request->all());

            // We need to make sure that the payment is fully processable from our end .
            $this->swiftaTransactionService->imitateTransactionForValidation($swiftaTransactionData);
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage(), $exception->getCode(), $exception);
        }

        $this->setValidData($swiftaTransactionData);
    }

    public function saveTransaction(): void {
        $this->swiftaTransactionService->saveTransaction();
    }

    public function sendResult(bool $requestType, Transaction $transaction): void {
        /** @var SwiftaTransaction */
        $swiftaTransaction = $transaction->originalTransaction()->first();
        if ($requestType) {
            $updateData = [
                'status' => SwiftaTransaction::STATUS_SUCCESS,
            ];
            $this->swiftaTransactionService->update($swiftaTransaction, $updateData);
            $smsService = app()->make(SmsService::class);
            $smsService->sendSms(
                $transaction->toArray(),
                SmsTypes::TRANSACTION_CONFIRMATION,
                SmsConfigs::class
            );
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

    /**
     * @param array<string, mixed> $swiftaTransactionData
     */
    public function setValidData(array $swiftaTransactionData): void {
        $this->validData = $swiftaTransactionData;
    }

    /**
     * @return array<string, mixed>
     */
    public function getValidData(): array {
        return $this->validData;
    }

    public function getSubTransaction(): SwiftaTransaction|WaveMoneyTransaction|WaveComTransaction {
        return $this->swiftaTransactionService->getSwiftaTransaction();
    }

    /**
     * @param SwiftaTransaction $transaction
     */
    public function init($transaction): void {
        $this->swiftaTransaction = $transaction;
        $this->transaction = $transaction->transaction()->first();
    }

    public function confirm(): void {
        // TODO: Implement getMessage() method.
    }

    public function getMessage(): string {
        // TODO: Implement getMessage() method.
        throw new \BadMethodCallException('Method getMessage() not yet implemented.');
    }

    public function getAmount(): float {
        return $this->getTransaction()->amount;
    }

    public function getSender(): string {
        return $this->getTransaction()->message;
    }

    public function saveCommonData(): Model {
        // TODO: Implement getSender() method.
        throw new \BadMethodCallException('Method saveCommonData() not yet implemented.');
    }
}
