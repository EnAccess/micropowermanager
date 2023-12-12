<?php

namespace App\Utils;

use App\Exceptions\Device\DeviceIsNotAssignedToCustomer;
use App\Misc\TransactionDataContainer;
use App\Models\AssetPerson;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use App\Services\AppliancePaymentService;
use App\Services\AppliancePersonService;
use App\Services\ApplianceRateService;
use Illuminate\Support\Collection;
use MPM\Device\DeviceService;

class ApplianceInstallmentPayer
{
    private Person $customer;
    private Transaction $transaction;
    public array $paidRates = [];
    public AssetPerson|null $shsLoan = null;
    public $consumableAmount;

    public function __construct(
        private AppliancePersonService $appliancePersonService,
        private ApplianceRateService $applianceRateService,
        private AppliancePaymentService $appliancePaymentService,
        private DeviceService $deviceService
    ) {
    }

    public function initialize(TransactionDataContainer $transactionData): void
    {
        $this->transaction = $transactionData->transaction;
        $this->consumableAmount = $this->transaction->amount;
        $this->customer = $this->getCustomerByDeviceSerial($this->transaction->message);
    }

    //This function pays the installments for the device number that provided in transaction
    public function payInstallmentsForDevice(TransactionDataContainer $container)
    {
        $customer = $container->appliancePerson->person;
        $this->appliancePaymentService->setPaymentAmount($container->transaction->amount);
        $installments = $container->appliancePerson->rates;
        $this->pay($installments, $customer);
    }


    // This function processes the payment of all installments (excluding device-recorded ones) that are due, right before generating the meter token.
    // If meter number is provided in transaction
    public function payInstallments()
    {
        $customer = $this->customer;
        $appliancePersonIds = $this->appliancePersonService->getLoanIdsForCustomerId($customer->id);
        $installments = $this->applianceRateService->getByLoanIdsForDueDate($appliancePersonIds);
        $this->pay($installments, $customer);

        return $this->transaction->amount;
    }

    public function consumeAmount()
    {
        $installments = $this->getInstallments($this->customer);
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

    private function getCustomerByDeviceSerial(string $serialNumber): Person
    {
        $device = $this->deviceService->getBySerialNumber($serialNumber);

        if (!$device) {
            throw new DeviceIsNotAssignedToCustomer('Device is not assigned to customer');
        }


        return $device->person;
    }

    private function getInstallments($customer)
    {
        $loans = $this->appliancePersonService->getLoanIdsForCustomerId($customer->id);

        return $this->applianceRateService->getByLoanIdsForDueDate($loans);
    }

    private function pay(Collection $installments, mixed $customer): void
    {
        $installments->map(function ($installment) use ($customer) {
            if ($installment->remaining > $this->transaction->amount) {// money is not enough to cover the whole rate
                //add payment history for the installment
                event('payment.successful', [
                    'amount' => $this->transaction->amount,
                    'paymentService' => $this->transaction->original_transaction_type,
                    'paymentType' => 'installment',
                    'sender' => $this->transaction->sender,
                    'paidFor' => $installment,
                    'payer' => $customer,
                    'transaction' => $this->transaction,
                ]);
                $installment->remaining -= $this->transaction->amount;
                $installment->update();
                $installment->save();

                $this->paidRates[] = [
                    'asset_rate_id' => $installment->id,
                    'paid' => $this->transaction->amount,
                ];
                $this->transaction->amount = 0;

                return false;
            } else {
                //add payment history for the loan
                event('payment.successful', [
                    'amount' => $installment->remaining,
                    'paymentService' => $this->transaction->original_transaction_type,
                    'paymentType' => 'installment',
                    'sender' => $this->transaction->sender,
                    'paidFor' => $installment,
                    'payer' => $customer,
                    'transaction' => $this->transaction,
                ]);
                $this->paidRates[] = [
                    'asset_rate_id' => $installment->id,
                    'paid' => $installment->remaining,
                ];
                $this->transaction->amount -= $installment->remaining;
                $installment->remaining = 0;
                $installment->update();
                $installment->save();

                return true;
            }
        });
    }
}
