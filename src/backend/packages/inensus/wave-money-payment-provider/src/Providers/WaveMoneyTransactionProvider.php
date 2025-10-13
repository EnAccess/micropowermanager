<?php

namespace Inensus\WaveMoneyPaymentProvider\Providers;

use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Inensus\SwiftaPaymentProvider\Models\SwiftaTransaction;
use Inensus\WavecomPaymentProvider\Models\WaveComTransaction;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use Inensus\WaveMoneyPaymentProvider\Modules\Transaction\WaveMoneyTransactionService;
use MPM\Transaction\Provider\ITransactionProvider;

class WaveMoneyTransactionProvider implements ITransactionProvider {
    private $validData = [];

    public function __construct(
        private WaveMoneyTransaction $waveMoneytransaction,
        private Transaction $transaction,
        private WaveMoneyTransactionService $waveMoneyTransactionService,
        private TransactionConflicts $transactionConflicts,
    ) {}

    public function validateRequest($request): void {
        $meterSerial = $request->input('meterSerial');
        $amount = $request->input('amount');

        try {
            $this->waveMoneyTransactionService->validatePaymentOwner($meterSerial, $amount);
            $waveMoneyTransactionData = $this->waveMoneyTransactionService->initializeTransactionData();

            // We need to make sure that the payment is fully processable from our end .
            $this->waveMoneyTransactionService->imitateTransactionForValidation($waveMoneyTransactionData);
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage(), $exception->getCode(), $exception);
        }

        $this->setValidData($waveMoneyTransactionData);
    }

    public function saveTransaction(): void {
        $this->waveMoneyTransactionService->saveTransaction();
    }

    public function sendResult(bool $requestType, Transaction $transaction): void {
        if ($requestType) {
            /** @var WaveMoneyTransaction */
            $waveMoneyTransaction = $transaction->originalTransaction()->first();
            $updateData = [
                'status' => WaveMoneyTransaction::STATUS_SUCCESS,
            ];
            $this->waveMoneyTransactionService->update($waveMoneyTransaction, $updateData);
            $smsService = app()->make(SmsService::class);
            $smsService->sendSms(
                $transaction->toArray(),
                SmsTypes::TRANSACTION_CONFIRMATION,
                SmsConfigs::class
            );
        } else {
            Log::critical(
                'WaveMoney transaction is been cancelled from MicroPowerManager.',
                [
                    'transaction_id' => $transaction->id,
                    'original_transaction_id' => $transaction->originalTransaction()->first()->id,
                ]
            );
        }
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

    public function init($transaction): void {
        $this->waveMoneytransaction = $transaction;
        $this->transaction = $transaction->transaction()->first();
    }

    public function addConflict(?string $message): void {
        $conflict = $this->transactionConflicts->newQuery()->make([
            'state' => $message,
        ]);
        $conflict->transaction()->associate($this->waveMoneytransaction);
        $conflict->save();
    }

    public function getTransaction(): Transaction {
        return $this->transaction;
    }

    public function setValidData($waveMoneyTransactionData): void {
        $this->validData = $waveMoneyTransactionData;
    }

    public function getValidData() {
        return $this->validData;
    }

    public function getSubTransaction(): SwiftaTransaction|WaveMoneyTransaction|WaveComTransaction {
        return $this->waveMoneyTransactionService->getWaveMoneyTransaction();
    }
}
