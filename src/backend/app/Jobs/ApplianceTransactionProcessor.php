<?php

namespace App\Jobs;

use App\DTO\TransactionDataContainer;
use App\Events\PaymentSuccessEvent;
use App\Events\TransactionFailedEvent;
use App\Events\TransactionSuccessfulEvent;
use App\Exceptions\ApplianceTokenNotProcessedException;
use App\Exceptions\TransactionAmountNotEnoughException;
use App\Exceptions\TransactionNotInitializedException;
use App\Models\Appliance;
use App\Models\Transaction\Transaction;
use App\Services\AppliancePaymentService;
use App\Services\ApplianceRateService;
use App\Utils\ApplianceInstallmentPayer;
use Illuminate\Support\Facades\Log;

class ApplianceTransactionProcessor extends AbstractJob {
    private Transaction $transaction;

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

        $originalTransaction = $this->transaction->originalTransaction()->first();
        if ($originalTransaction?->conflicts()->exists()) {
            return; // skip transaction processing if it has conflicts
        }

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
        } else {
            $appliancePaymentService = resolve(AppliancePaymentService::class);
            $creatorId = $this->transaction->originalTransaction()->first()->user_id ?? 0;
            $appliancePaymentService->createPaymentLog($container->appliancePerson, $container->amount, $creatorId);
            event(new TransactionSuccessfulEvent($this->transaction));
        }
    }

    private function initializeTransaction(): void {
        $this->transaction = Transaction::query()->find($this->transactionId);

        if ($this->transaction->type !== Transaction::TYPE_DOWN_PAYMENT) {
            $appliancePerson = $this->transaction->paygoAppliance()->first()
                ?? $this->transaction->nonPaygoAppliance()->first();

            $this->transaction->type = ($appliancePerson && $appliancePerson->isEnergyService())
                ? Transaction::TYPE_EAAS_RATE
                : Transaction::TYPE_DEFERRED_PAYMENT;
        }

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
        $minimumPurchaseAmount = $container->appliancePerson->isEnergyService()
            ? ($container->appliancePerson->minimum_payable_amount ?? 0)
            : $container->installmentCost;

        if ($minimumPurchaseAmount > 0 && $container->amount < $minimumPurchaseAmount) {
            throw new TransactionAmountNotEnoughException("Minimum purchase amount not reached for appliance with serial id:{$container->transaction->message}");
        }
    }

    private function payApplianceInstallments(TransactionDataContainer $container): TransactionDataContainer {
        if ($container->appliancePerson->isEnergyService()) {
            $applianceRateService = resolve(ApplianceRateService::class);
            $paidRate = $applianceRateService->createPaidRate($container->appliancePerson, $container->amount);
            $container->paidRates = [
                ['appliance_rate_id' => $paidRate->id, 'paid' => $container->amount],
            ];

            $container->applianceInstallmentsFullFilled = false;
            // Success payment event means the client will update the ui as updated
            // before the token get's generated.
            event(new PaymentSuccessEvent(
                amount: (int) $container->amount,
                paymentService: $this->transaction->original_transaction_type,
                paymentType: 'energy_service',
                sender: $this->transaction->sender,
                paidFor: $paidRate,
                payer: $container->appliancePerson->person,
                transaction: $this->transaction,
            ));

            return $container;
        }

        $applianceInstallmentPayer = resolve(ApplianceInstallmentPayer::class);
        $applianceInstallmentPayer->initialize($container);
        $applianceInstallmentPayer->payInstallmentsForDevice($container);
        $container->paidRates = $applianceInstallmentPayer->paidRates;
        $container->applianceInstallmentsFullFilled = $container->appliancePerson->rates->every(fn ($installment): bool => $installment->remaining === 0);

        return $container;
    }

    private function processToken(TransactionDataContainer $transactionData): void {
        $transactionData->chargeAmount = 0.0;
        $transactionData->chargeUnit = '';
        $transactionData->chargeType = '';

        dispatch(new TokenProcessor($this->companyId, $transactionData));
    }
}
