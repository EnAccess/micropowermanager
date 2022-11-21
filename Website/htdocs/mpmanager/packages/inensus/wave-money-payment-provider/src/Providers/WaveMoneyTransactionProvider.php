<?php

namespace Inensus\WaveMoneyPaymentProvider\Providers;

use App\Lib\ITransactionProvider;
use App\Models\Address\Address;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Model;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use Inensus\WaveMoneyPaymentProvider\Modules\Transaction\WaveMoneyTransactionService;

class WaveMoneyTransactionProvider implements ITransactionProvider
{
    private $validData = [];

    public function __construct(
      //  private Transaction $transaction,
      //  private WaveMoneyTransaction $waveMoneyTransaction,
        private WaveMoneyTransactionService $waveMoneyTransactionService,
      //  private Address $address
    )
    {
    }

    public function validateRequest($request)
    {
        $this->validData = array_merge($this->validData, $request->all());

        try {
            $this->waveMoneyTransactionService->validatePaymentOwner($this->validData['meterSerial']);
            $this->waveMoneyTransactionService->imitateTransactionForValidation($this->validData);
        }catch (\Exception $exception){
            throw  new \Exception($exception->getMessage());
        }
    }

    public function saveTransaction()
    {
        // TODO: Implement saveTransaction() method.
    }

    public function sendResult(bool $requestType, Transaction $transaction)
    {
        // TODO: Implement sendResult() method.
    }


    public function confirm(): void
    {
        // TODO: Implement confirm() method.
    }

    public function getMessage(): string
    {
        // TODO: Implement getMessage() method.
    }

    public function getAmount(): int
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

    public function init($transaction): void
    {
        // TODO: Implement init() method.
    }

    public function addConflict(?string $message): void
    {
        // TODO: Implement addConflict() method.
    }

    public function getTransaction(): Transaction
    {
        // TODO: Implement getTransaction() method.
    }
}