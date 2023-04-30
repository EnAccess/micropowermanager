<?php

namespace App\Jobs;

use App\Exceptions\TransactionAmountNotEnoughException;
use App\Exceptions\TransactionNotInitializedException;
use App\Misc\TransactionDataContainer;
use App\Models\Transaction\Transaction;
use App\PaymentHandler\AccessRate;
use App\Services\SmsAndroidSettingService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class EnergyTransactionProcessor extends AbstractJob
{
    private $transaction;

    /**
     * Create a new job instance.
     *
     * @param $transaction
     */
    public function __construct(private $transactionId)
    {
        $this->afterCommit = true;
        parent::__construct(get_class($this));
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws TransactionNotInitializedException
     */
    public function executeJob()
    {
        $this->transaction = Transaction::query()->find($this->transactionId);
        //set transaction type to energy
        $this->transaction->type = 'energy';
        $this->transaction->save();
        $transactionData = $this->initializeTransactionDataContainer();

        try {
            $this->checkForMinimumPurchaseAmount($transactionData);
            $transactionData = $this->payApplianceInstallments($transactionData);
            $transactionData = $this->payAccessRateIfExists($transactionData);
            if ($transactionData->transaction->amount > 0) {
                $this->callTokenProcessor($transactionData);
            } else {
                $this->completeTransactionWithNotification($transactionData);
            }
        } catch (\Exception $e) {
            Log::info('Transaction failed.: ' . $e->getMessage());
            event('transaction.failed', [$this->transaction, $e->getMessage()]);
        }
    }

    /**
     * @return array
     */
    private function initializeTransactionDataContainer(): TransactionDataContainer|array
    {
        try {
            //create an object for the token job
            $transactionData = TransactionDataContainer::initialize($this->transaction);
        } catch (\Exception $e) {
            event('transaction.failed', [$this->transaction, $e->getMessage()]);
            throw new TransactionNotInitializedException($e->getMessage());
        }
        return $transactionData;
    }

    /**
     * @param array|TransactionDataContainer $transactionData
     * @return void
     */
    private function checkForMinimumPurchaseAmount(array|TransactionDataContainer $transactionData): void
    {
        $minimumPurchaseAmount = $this->getTariffMinimumPurchaseAmount($transactionData);

        if ($minimumPurchaseAmount > 0) {
            $validator = resolve('MinimumPurchaseAmountValidator');
            try {
                if (!$validator->validate($transactionData, $minimumPurchaseAmount)) {
                    throw new TransactionAmountNotEnoughException("Minimum purchase amount not reached for {$transactionData->meter->serial_number}");
                }
            } catch (\Exception $e) {
                   throw new TransactionAmountNotEnoughException($e->getMessage());
            }
        }
    }

    /**
     * @param array|TransactionDataContainer $transactionData
     * @return void
     */
    private function payApplianceInstallments(array|TransactionDataContainer $transactionData): TransactionDataContainer
    {
        $applianceInstallmentPayer = resolve('ApplianceInstallmentPayer');
        $applianceInstallmentPayer->initialize($transactionData);
        $transactionData->transaction->amount = $applianceInstallmentPayer->pay();
        $transactionData->totalAmount = $transactionData->transaction->amount;

        return $transactionData;
    }

    /**
     * @param array|TransactionDataContainer $transactionData
     * @return TransactionDataContainer|array
     */
    private function payAccessRateIfExists(array|TransactionDataContainer $transactionData): TransactionDataContainer|array
    {
        if ($transactionData->transaction->amount > 0) {
            // pay if necessary access rate
            $accessRatePayer = resolve('AccessRatePayer');
            $accessRatePayer->initialize($transactionData);
            $transactionData = $accessRatePayer->pay();
        }
        return $transactionData;
    }

    /**
     * @param array|TransactionDataContainer $transactionData
     * @return void
     */
    private function completeTransactionWithNotification(array|TransactionDataContainer $transactionData): void
    {
        event('transaction.successful', [$transactionData->transaction]);
    }

    /**
     * @param array|TransactionDataContainer $transactionData
     * @return void
     */
    private function callTokenProcessor(array|TransactionDataContainer $transactionData): void
    {
        $kWhToBeCharged = 0.0;
        $transactionData->chargedEnergy = round($kWhToBeCharged, 1);

        TokenProcessor::dispatch($transactionData)
            ->allOnConnection('redis')
            ->onQueue(\config('services.queues.token'));
    }

    private function getTariffMinimumPurchaseAmount($transactionData)
    {
        return $transactionData->tariff->minimum_purchase_amount;
    }

    /**
     * @param int|TransactionDataContainer $transactionData
     * @return float|int
     * @deprecated  unused method. To be removed in the future
     */
    private function getChargedEnergyForSocialTariffPiggyBanks(int|TransactionDataContainer $transactionData): int|float
    {
        $meterParameter = $transactionData->meterParameter;
        $transactionData->amount = $transactionData->transaction->amount;
        $kWhToBeCharged = 0.0;
        // get piggy-bank energy
        try {
            $bankAccount = $meterParameter->socialTariffPiggyBank()->firstOrFail();
            // calculate the cost of savings. To achive that, the price (for kWh.) should converted to Wh. (/1000)

            $savingsCost = $bankAccount->savings * (($bankAccount->socialTariff->price / 1000));
            if ($transactionData->amount >= $savingsCost) {
                $kWhToBeCharged += $bankAccount->savings / 1000;
                $transactionData->amount -= $savingsCost;
            } else {
                $transactionData->amount = 0;
                $kWhToBeCharged += $bankAccount->savings / 1000;
                $bankAccount->savings -= $transactionData->amount
                    / (($bankAccount->socialTariff->price / 1000));
            }
            $bankAccount->update();
        } catch (ModelNotFoundException $exception) {
            // meter has no piggy bank account
        }
        return $kWhToBeCharged;
    }
}
