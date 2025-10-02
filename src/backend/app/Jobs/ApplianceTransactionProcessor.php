<?php

namespace App\Jobs;

use App\Events\TransactionFailedEvent;
use App\Exceptions\TransactionAmountNotEnoughException;
use App\Exceptions\TransactionNotInitializedException;
use App\Misc\TransactionDataContainer;
use App\Models\Transaction\Transaction;
use App\Utils\ApplianceInstallmentPayer;
use Illuminate\Support\Facades\Log;

class ApplianceTransactionProcessor extends AbstractJob {
    private Transaction $transaction;
    protected const TYPE = 'deferred_payment';

    public function __construct(int $companyId, private int $transactionId) {
        $this->onConnection('redis');
        $this->onQueue('transaction_appliance');

        $this->companyId = $companyId;
        $this->afterCommit = true;
        parent::__construct($companyId);
    }

    /**
     * @throws TransactionNotInitializedException
     */
    public function executeJob(): void {
        $this->initializeTransaction();
        $container = $this->initializeTransactionDataContainer();

        try {
            $this->checkForMinimumPurchaseAmount($container);
            $container = $this->payApplianceInstallments($container);
            $this->processToken($container);
        } catch (\Exception $e) {
            Log::info('Transaction failed.: '.$e->getMessage());
            event(new TransactionFailedEvent($this->transaction, $e->getMessage()));
        }
    }

    private function initializeTransaction(): void {
        $this->transaction = Transaction::query()->find($this->transactionId);
        $this->transaction->type = 'deferred_payment';
        $this->transaction->save();
    }

    private function initializeTransactionDataContainer(): TransactionDataContainer {
        try {
            return TransactionDataContainer::initialize($this->transaction);
        } catch (\Exception $e) {
            event(new TransactionFailedEvent($this->transaction, $e->getMessage()));
            throw new TransactionNotInitializedException($e->getMessage());
        }
    }

    private function checkForMinimumPurchaseAmount(TransactionDataContainer $container): void {
        $minimumPurchaseAmount = $container->installmentCost;
        throw_if($container->amount < $minimumPurchaseAmount, new TransactionAmountNotEnoughException("Minimum purchase amount not reached for {$container->device->device_serial}"));
    }

    private function payApplianceInstallments(TransactionDataContainer $container): TransactionDataContainer {
        $applianceInstallmentPayer = resolve(ApplianceInstallmentPayer::class);
        $applianceInstallmentPayer->initialize($container);
        $applianceInstallmentPayer->payInstallmentsForDevice($container);
        $container->paidRates = $applianceInstallmentPayer->paidRates;
        $container->applianceInstallmentsFullFilled = $container->appliancePerson->rates->every(function ($installment): bool {
            return $installment->remaining === 0;
        });

        return $container;
    }

    private function processToken(TransactionDataContainer $transactionData): void {
        $kWhToBeCharged = 0.0;
        $transactionData->chargedEnergy = round($kWhToBeCharged, 1);

        TokenProcessor::dispatch($this->companyId, $transactionData);
    }
}
