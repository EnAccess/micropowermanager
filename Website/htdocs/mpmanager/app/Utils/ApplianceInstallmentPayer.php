<?php

namespace App\Utils;

use App\Exceptions\MeterParameter\MeterParameterNotFound;
use App\Exceptions\Meters\MeterIsNotAssignedToCustomer;
use App\Exceptions\Meters\MeterIsNotInUse;
use App\Misc\TransactionDataContainer;
use App\Models\AssetPerson;
use App\Models\AssetRate;
use App\Models\Meter\Meter;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use App\Services\AppliancePersonService;
use App\Services\ApplianceRateService;
use App\Services\MeterService;

class ApplianceInstallmentPayer implements IPayer
{
    private Person $customer;
    private Transaction $transaction;
    public array $paidRates = [];

    public function __construct(
        private MeterService $meterService,
        private AppliancePersonService $appliancePersonService,
        private ApplianceRateService $applianceRateService,
    ) {
    }

    /**
     * @throws MeterIsNotInUse
     * @throws MeterIsNotAssignedToCustomer
     */
    public function initialize(TransactionDataContainer $transactionData): void
    {
        $this->transaction = $transactionData->transaction;
        $this->customer = $this->getCustomerByMeterSerial($transactionData->transaction->message);
    }

    public function pay()
    {
        $installments = $this->getInstallments();
        $installments->each(function ($installment) {
            if ($installment->remaining > $this->transaction->amount) {// money is not enough to cover the whole rate
                //add payment history for the installment
                event('payment.successful', [
                    'amount' => $this->transaction->amount,
                    'paymentService' => $this->transaction->original_transaction_type,
                    'paymentType' => 'loan rate',
                    'sender' => $this->transaction->sender,
                    'paidFor' => $installment,
                    'payer' => $this->customer,
                    'transaction' => $this->transaction,
                ]);
                $installment->remaining -= $this->transaction->amount;
                $installment->save();

                $this->paidRates[] = [
                    'asset_type_name' => $installment->assetPerson->assetType->name,
                    'paid' => $this->transaction->amount,
                ];
                $this->transaction->amount = 0;

                return false;
            } else {
                //add payment history for the loan
                event('payment.successful', [
                    'amount' => $installment->remaining,
                    'paymentService' => $this->transaction->original_transaction_type,
                    'paymentType' => 'loan rate',
                    'sender' => $this->transaction->sender,
                    'paidFor' => $installment,
                    'payer' => $this->customer,
                    'transaction' => $this->transaction,
                ]);
                $this->paidRates[] = [
                    'asset_type_name' => $installment->assetPerson->assetType->name,
                    'paid' => $installment->remaining,
                ];
                $this->transaction->amount -= $installment->remaining;
                $installment->remaining = 0;
                $installment->save();

                return true;
            }
        });

        return $this->transaction->amount;
    }

    public function consumeAmount()
    {
        $installments = $this->getInstallments();
        $installments->each(function ($installment) {
            if ($installment->remaining > $this->transaction->amount) {// money is not enough to cover the whole rate
                $this->transaction->amount = 0;

                return false;
            } else {
                $this->transaction->amount -= $installment->remaining;

                return true;
            }
        });

        return $this->transaction->amount;
    }

    private function getCustomerByMeterSerial(string $serialNumber): Person
    {
        $meter = $this->meterService->getBySerialNumber($serialNumber);

        if (!$meter) {
            throw new MeterIsNotAssignedToCustomer('Meter is not assigned to customer');
        }

        if (!$meter->in_use) {
            throw new MeterIsNotInUse($serialNumber . ' meter is not in use');
        }

        return $meter->meterParameter->owner;
    }

    private function getInstallments()
    {
        $loans = $this->appliancePersonService->getLoanIdsForCustomerId($this->customer->id);

        return $this->applianceRateService->getByLoanIdsForDueDate($loans);
    }
}
