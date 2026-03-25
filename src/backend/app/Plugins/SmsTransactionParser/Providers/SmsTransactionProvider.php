<?php

declare(strict_types=1);

namespace App\Plugins\SmsTransactionParser\Providers;

use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use App\Plugins\SmsTransactionParser\Models\SmsTransaction;
use App\Providers\Interfaces\ITransactionProvider;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmsTransactionProvider implements ITransactionProvider {
    private Transaction $transaction;

    public function __construct(
        private SmsService $smsService,
        private TransactionConflicts $transactionConflicts,
        private SmsTransaction $smsTransaction,
    ) {}

    public function saveTransaction(): void {}

    public function sendResult(bool $requestType, Transaction $transaction): void {
        /** @var SmsTransaction $smsTransaction */
        $smsTransaction = $transaction->originalTransaction()->first();

        $smsTransaction->setStatus($requestType ? SmsTransaction::STATUS_SUCCESS : SmsTransaction::STATUS_FAILED);
        $smsTransaction->save();

        if ($requestType) {
            $this->smsService->sendSms($transaction->toArray(), SmsTypes::TRANSACTION_CONFIRMATION, SmsConfigs::class);
        } else {
            Log::error('SMS parsed transaction has been cancelled');
        }
    }

    public function validateRequest(Request $request): void {}

    public function confirm(): void {}

    public function getMessage(): string {
        return $this->getTransaction()->getMessage();
    }

    public function getAmount(): float {
        return $this->getTransaction()->getAmount();
    }

    public function getSender(): string {
        return $this->getTransaction()->getSender();
    }

    public function saveCommonData(): Model {
        throw new \BadMethodCallException('Method saveCommonData() not implemented for SMS transactions.');
    }

    public function init(BasePaymentProviderTransaction $transaction): void {
        if (!$transaction instanceof SmsTransaction) {
            throw new \InvalidArgumentException('Expected instance of '.SmsTransaction::class.', got '.$transaction::class);
        }
        $this->smsTransaction = $transaction;
        $this->transaction = $transaction->transaction()->first();
    }

    public function addConflict(?string $message): void {
        $conflict = $this->transactionConflicts->newQuery()->make([
            'state' => $message,
        ]);
        $conflict->transaction()->associate($this->smsTransaction);
        $conflict->save();
    }

    public function getTransaction(): Transaction {
        return $this->transaction;
    }
}
