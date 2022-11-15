<?php

namespace App\Utils;

use App\Exceptions\Meters\MeterIsNotAssignedToCustomer;
use App\Exceptions\Meters\MeterIsNotInUse;
use App\Misc\TransactionDataContainer;
use App\Models\Meter\Meter;
use App\Models\Person\Person;
use App\Services\AppliancePersonService;
use App\Services\ApplianceRateService;
use App\Services\MeterService;


class MinimumPurchaseAmountValidator
{

    private Person $meterOwner;
    private float $restAmount;
    private TransactionDataContainer $transactionData;

    public function __construct(
        private AppliancePersonService $appliancePersonService,
        private ApplianceRateService $applianceRateService,
        private MeterService $meterService
    ) {

    }

    public function validate(TransactionDataContainer $transactionData, $minimumPurchaseAmount): bool
    {
        $this->transactionData = $transactionData;
        $this->meterOwner = $this->getMeterOwner($transactionData->transaction->message);
        $this->restAmount = $this->transactionData->transaction->amount;

        $this->consumeAmountForInstallments();
        $this->consumeAmountForAccessRate();
        $this->consumeAmountForSocialTariffPiggyBankSavingsIfMeterHas();

        return  $this->restAmount >= $minimumPurchaseAmount;

    }

    private function consumeAmountForInstallments()
    {
        $loans = $this->getCustomerDueRates($this->meterOwner);

        foreach ($loans as $loan) {
            $this->restAmount -= $loan->remaining;
        }
    }

    private function consumeAmountForAccessRate()
    {
        $accessRatePayment = $this->transactionData->meter->accessRatePayment()->first();
        $accessRateDebt = $accessRatePayment ? $accessRatePayment->debt : 0;
        $this->restAmount -= $accessRateDebt;
    }

    private function consumeAmountForSocialTariffPiggyBankSavingsIfMeterHas()
    {
        $meterParameter = $this->transactionData->meterParameter;
        $bankAccount = $meterParameter->socialTariffPiggyBank()->first();

        if ($bankAccount) {
            $savingsCost = $bankAccount->savings * (($bankAccount->socialTariff->price / 1000));
            $this->restAmount -= $savingsCost;
        }

    }

    private function getCustomerDueRates($owner)
    {
        $loans = $this->appliancePersonService->getLoanIdsForCustomerId($owner->id);
        return $this->applianceRateService->getByLoanIdsForDueDate($loans);
    }

    private function getMeterOwner(string $serialNumber)
    {
        $meter = $this->meterService->getBySerialNumber($serialNumber);

        if (!$meter) {
            throw new MeterIsNotAssignedToCustomer('meter is not assigned to customer');
        }

        //meter is not been used by anyone
        if (!$meter->in_use) {
            throw new MeterIsNotInUse($serialNumber . ' meter is not in use');
        }

        return $meter->meterParameter->owner;
    }

}