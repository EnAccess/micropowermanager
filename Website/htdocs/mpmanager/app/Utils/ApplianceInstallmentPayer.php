<?php

namespace App\Utils;

use App\Exceptions\MeterParameter\MeterParameterNotFound;
use App\Exceptions\Meters\MeterIsNotAssignedToCustomer;
use App\Exceptions\Meters\MeterIsNotInUse;
use App\Misc\TransactionDataContainer;
use App\Models\AssetPerson;
use App\Models\AssetRate;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterTariff;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use App\Services\AppliancePaymentService;
use App\Services\AppliancePersonService;
use App\Services\ApplianceRateService;
use App\Services\AssetService;
use App\Services\MeterService;
use Illuminate\Support\Facades\Log;

class ApplianceInstallmentPayer implements IPayer
{
    private Person $customer;
    private Transaction $transaction;
    private MeterTariff $tariff;
    public array $paidRates = [];
    public AssetPerson|null $shsLoan = null;
    public $shsLoanRates;
    public $consumableAmount;

    public function __construct(
        private MeterService $meterService,
        private AppliancePersonService $appliancePersonService,
        private ApplianceRateService $applianceRateService,
        private AssetService $assetService
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
        $this->tariff = $transactionData->tariff;
        $this->consumableAmount = $this->transaction->amount;
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
                $installment->update();
                $installment->save();

                $this->paidRates[] = [
                    'asset_type_name' => $installment->assetPerson->asset->name,
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
                    'asset_type_name' => $installment->assetPerson->asset->name,
                    'paid' => $installment->remaining,
                ];
                $this->transaction->amount -= $installment->remaining;
                $installment->remaining = 0;
                $installment->update();
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
            if ($installment->remaining > $this->consumableAmount) {// money is not enough to cover the
                // whole rate
                $this->consumableAmount = 0;

                return false;
            } else {
                $this->consumableAmount -= $installment->remaining;

                return true;
            }
        });

        return $this->consumableAmount;
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
        $loans = $this->appliancePersonService->getLoansForCustomerId($this->customer->id);

        $tariffName = $this->tariff->name;
        $loans->each(function ($assetPerson) use ($tariffName) {
            $asset = $this->assetService->getById($assetPerson->asset_id);
            if ($asset) {
                $assetNameLower  =strtolower($asset->name);
                $shsTariffName = "{$assetNameLower}-{$this->transaction->message}";
                if ($tariffName === $shsTariffName) {
                    $this->shsLoanRates = $this->applianceRateService->getAllByLoanId($assetPerson->id);
                    $this->shsLoan = $assetPerson;
                    return false;
                }
            }
            return true;
        });

        if (isset($this->shsLoanRates)) {
            return $this->shsLoanRates;
        }

        return $this->applianceRateService->getByLoanIdsForDueDate($loans->pluck('id'));
    }
}
