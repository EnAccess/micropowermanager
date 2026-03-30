<?php

namespace App\Plugins\WaveMoneyPaymentProvider\Providers;

use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use App\Plugins\SwiftaPaymentProvider\Models\SwiftaTransaction;
use App\Plugins\WavecomPaymentProvider\Models\WaveComTransaction;
use App\Plugins\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use App\Plugins\WaveMoneyPaymentProvider\Modules\Transaction\WaveMoneyTransactionService;
use App\Providers\Interfaces\ITransactionProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WaveMoneyTransactionProvider implements ITransactionProvider {
    /** @var array<string, mixed> */
    private array $validData = [];

    public function __construct(
        private WaveMoneyTransaction $waveMoneytransaction,
        private Transaction $transaction,
        private WaveMoneyTransactionService $waveMoneyTransactionService,
        private TransactionConflicts $transactionConflicts,
    ) {}

    public function validateRequest(Request $request): void {
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
        // SMS sent centrally via SendTransactionConfirmationSmsListener
        } else {
            $originalTransaction = $transaction->originalTransaction()->first();
            Log::critical(
                'WaveMoney transaction is been cancelled from MicroPowerManager.',
                [
                    'transaction_id' => $transaction->getAttribute('id'),
                    'original_transaction_id' => $originalTransaction ? $originalTransaction->getAttribute('id') : null,
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

    public function getAmount(): float {
        return (int) $this->getTransaction()->amount;
    }

    public function getSender(): string {
        return $this->getTransaction()->message;
    }

    public function saveCommonData(): Model {
        // TODO: Implement saveCommonData() method.
        throw new \BadMethodCallException('Method saveCommonData() not yet implemented.');
    }

    public function init(BasePaymentProviderTransaction $transaction): void {
        if (!$transaction instanceof WaveMoneyTransaction) {
            throw new \InvalidArgumentException('Expected instance of '.WaveMoneyTransaction::class.', got '.$transaction::class);
        }
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

    /**
     * @param array<string, mixed> $waveMoneyTransactionData
     */
    public function setValidData(array $waveMoneyTransactionData): void {
        $this->validData = $waveMoneyTransactionData;
    }

    /**
     * @return array<string, mixed>
     */
    public function getValidData(): array {
        return $this->validData;
    }

    public function getSubTransaction(): SwiftaTransaction|WaveMoneyTransaction|WaveComTransaction {
        return $this->waveMoneyTransactionService->getWaveMoneyTransaction();
    }
}
