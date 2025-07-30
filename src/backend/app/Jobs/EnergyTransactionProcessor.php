<?php

namespace App\Jobs;

use App\Events\TransactionFailedEvent;
use App\Events\TransactionSuccessfulEvent;
use App\Exceptions\TransactionAmountNotEnoughException;
use App\Exceptions\TransactionNotInitializedException;
use App\Misc\TransactionDataContainer;
use App\Models\Transaction\Transaction;
use Illuminate\Support\Facades\Log;

class EnergyTransactionProcessor extends AbstractJob {
    private Transaction $transaction;
    protected const TYPE = 'energy';

    public function __construct(private int|string $transactionId) {
        $this->afterCommit = true;
        parent::__construct(get_class($this));
    }

    /**
     * Execute the job.
     *
     * @return void
     *
     * @throws TransactionNotInitializedException
     */
    public function executeJob(): void {
        $this->initializeTransaction();
        $container = $this->initializeTransactionDataContainer();

        try {
            $this->checkForMinimumPurchaseAmount($container);
            $transactionData = $this->payApplianceInstallments($container);
            $transactionData = $this->payAccessRateIfExists($transactionData);

            if ($transactionData->transaction->amount > 0) {
                $this->processToken($transactionData);
            } else {
                $this->completeTransactionWithNotification($transactionData);
            }
        } catch (\Exception $e) {
            Log::info('Transaction failed.: '.$e->getMessage());
            event(new TransactionFailedEvent($this->transaction, $e->getMessage()));
        }
    }

    private function initializeTransaction(): void {
        $this->transaction = Transaction::query()->find($this->transactionId);
        $this->transaction->type = 'energy';
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

    private function checkForMinimumPurchaseAmount(TransactionDataContainer $transactionData): void {
        $minimumPurchaseAmount = $this->getTariffMinimumPurchaseAmount($transactionData);

        if ($minimumPurchaseAmount > 0) {
            $validator = resolve('MinimumPurchaseAmountValidator');
            try {
                if (!$validator->validate($transactionData, $minimumPurchaseAmount)) {
                    throw new TransactionAmountNotEnoughException("Minimum purchase amount not reached for {$transactionData->device->device_serial}");
                }
            } catch (\Exception $e) {
                throw new TransactionAmountNotEnoughException($e->getMessage());
            }
        }
    }

    private function payApplianceInstallments(TransactionDataContainer $container): TransactionDataContainer {
        $applianceInstallmentPayer = resolve('ApplianceInstallmentPayer');
        $applianceInstallmentPayer->initialize($container);
        $container->transaction->amount = $applianceInstallmentPayer->payInstallments();
        $container->totalAmount = $container->transaction->amount;
        $container->paidRates = $applianceInstallmentPayer->paidRates;

        return $container;
    }

    private function payAccessRateIfExists(TransactionDataContainer $transactionData): TransactionDataContainer {
        if ($transactionData->transaction->amount > 0) {
            $accessRatePayer = resolve('AccessRatePayer');
            $accessRatePayer->initialize($transactionData);
            $transactionData = $accessRatePayer->pay();
        }

        return $transactionData;
    }

    private function completeTransactionWithNotification(TransactionDataContainer $transactionData): void {
        event(new TransactionSuccessfulEvent($transactionData->transaction));
    }

    private function processToken(TransactionDataContainer $transactionData): void {
        $kWhToBeCharged = 0.0;
        $transactionData->chargedEnergy = round($kWhToBeCharged, 1);

        TokenProcessor::dispatch($transactionData)
            ->allOnConnection('redis')
            ->onQueue(config('services.queues.token'));
    }

    private function getTariffMinimumPurchaseAmount(TransactionDataContainer $transactionData): float {
        return $transactionData->tariff->minimum_purchase_amount;
    }
}
