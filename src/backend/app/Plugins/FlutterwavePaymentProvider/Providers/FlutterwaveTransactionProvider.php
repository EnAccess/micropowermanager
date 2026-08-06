<?php

declare(strict_types=1);

namespace app\Plugins\FlutterwavePaymentProvider\Providers;

use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\Transaction;
use App\Providers\Interfaces\ITransactionProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class FlutterwaveTransactionProvider implements ITransactionProvider
{
    public function __construct(
        private FlutterwaveTransaction        $flutterwaveTransaction,
        private Transaction                   $transaction,
        private FlutterwaveTransactionService $flutterwaveTransactionService,
        private TransactionConflicts          $transactionConflicts,
    )
    {}

    public function saveTransaction(): void
    {
        // TODO: Implement saveTransaction() method.
    }

    public function sendResult(bool $requestType, Transaction $transaction): void
    {
        // TODO: Implement sendResult() method.
    }

    public function validateRequest(Request $request): void
    {
        // TODO: Implement validateRequest() method.
    }

    public function confirm(): void
    {
        // TODO: Implement confirm() method.
    }

    public function getMessage(): string
    {
        // TODO: Implement getMessage() method.
    }

    public function getAmount(): float
    {
        // TODO: Implement getAmount() method.
    }

    public function getSender(): string
    {
        // TODO: Implement getSender() method.
    }

    public function saveCommonData(): Model
    {
        // TODO: Implement saveCommonData() method.
    }

    public function init(BasePaymentProviderTransaction $transaction): void
    {
        // TODO: Implement init() method.
    }

    public function addConflict(?string $message): void
    {
        // TODO: Implement addConflict() method.
    }
}