<?php

namespace App\Jobs;

use App\DTO\TransactionDataContainer;
use App\Events\TransactionFailedEvent;
use App\Exceptions\ApplianceTokenNotProcessedException;
use App\Exceptions\TransactionAmountNotEnoughException;
use App\Exceptions\TransactionNotInitializedException;
use App\Models\Appliance;
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
            $appliance = $container->appliancePerson->appliance;
            $this->processTransactionPayment($container, $appliance);
        } catch (\Exception $e) {
            Log::info('Transaction failed.: '.$e->getMessage());
            event(new TransactionFailedEvent($this->transaction, $e->getMessage()));
            throw new ApplianceTokenNotProcessedException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function processTransactionPayment(TransactionDataContainer $container, Appliance $appliance): void {
        $isPaygo = $appliance->applianceType->paygo_enabled;

        $this->checkForMinimumPurchaseAmount($container);
        $container = $this->payApplianceInstallments($container);
        if ($isPaygo) {
            $this->processToken($container);
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
        if ($container->amount < $minimumPurchaseAmount) {
            throw new TransactionAmountNotEnoughException("Minimum purchase amount not reached for {$container->device->device_serial}");
        }
    }

    private function payApplianceInstallments(TransactionDataContainer $container): TransactionDataContainer {
        $applianceInstallmentPayer = resolve(ApplianceInstallmentPayer::class);
        $applianceInstallmentPayer->initialize($container);
        $applianceInstallmentPayer->payInstallmentsForDevice($container);
        $container->paidRates = $applianceInstallmentPayer->paidRates;
        $container->applianceInstallmentsFullFilled = $container->appliancePerson->rates->every(fn ($installment): bool => $installment->remaining === 0);

        return $container;
    }

    private function processToken(TransactionDataContainer $transactionData): void {
        $kWhToBeCharged = 0.0;
        $transactionData->chargedEnergy = round($kWhToBeCharged, 1);

        dispatch(new TokenProcessor($this->companyId, $transactionData));
    }
}
