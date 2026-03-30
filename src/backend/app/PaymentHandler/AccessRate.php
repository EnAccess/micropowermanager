<?php

namespace App\PaymentHandler;

use App\DTO\TransactionDataContainer;
use App\Events\PaymentSuccessEvent;
use App\Exceptions\AccessRates\NoAccessRateFound;
use App\Models\AccessRate\AccessRate as AccessRateModel;
use App\Models\AccessRate\AccessRatePayment;
use App\Models\Device;
use App\Models\Meter\Meter;
use Illuminate\Support\Carbon;

class AccessRate {
    private ?AccessRateModel $accessRate = null;

    /**
     * @throws NoAccessRateFound
     */
    public static function withDevice(Device $device): AccessRate {
        $deviceModel = $device->device;

        if (!$deviceModel instanceof Meter) {
            throw new NoAccessRateFound('Device is not associated with a meter');
        }

        if ($deviceModel->tariff->accessRate === null) {
            throw new NoAccessRateFound('Tariff  '.$deviceModel->tariff->name.' has no access rate');
        }
        $accessRate = new self();
        $accessRate->accessRate = $deviceModel->accessRate();
        $accessRate->setDevice();

        return $accessRate;
    }

    private function setDevice(): void {}

    /**
     * @throws NoAccessRateFound
     */
    public function initializeAccessRatePayment(): AccessRatePayment {
        if (!$this->accessRate instanceof AccessRateModel) {
            throw new NoAccessRateFound('Access Rate is not set');
        }
        // get current date and add AccessRate.period days
        $nextPaymentDate = Carbon::now()->addDays($this->accessRate->period);
        // create accessRatePayment instance and fill with the variables
        $accessRatePayment = new AccessRatePayment();
        $accessRatePayment->accessRate()->associate($this->accessRate);
        $accessRatePayment->due_date = $nextPaymentDate;
        $accessRatePayment->debt = 0;

        return $accessRatePayment;
    }

    /**
     * @throws NoAccessRateFound
     */
    private function getDebt(Device $device): float {
        $deviceModel = $device->device;

        if (!$deviceModel instanceof Meter) {
            throw new NoAccessRateFound('Device is not associated with a meter');
        }

        $accessRate = $deviceModel->accessRatePayment()->first();
        if ($accessRate === null) {
            throw new NoAccessRateFound('no access rate is defined');
        }

        return $accessRate->debt;
    }

    /**
     * @deprecated
     */
    public static function payAccessRate(TransactionDataContainer $transactionData): TransactionDataContainer {
        $nonStaticGateway = new self();
        /** @var Device $device */
        $device = $transactionData->device;

        // get accessRatePayment
        $accessRatePayment = $nonStaticGateway->getAccessRatePayment($device);
        try {
            $debt_amount = $nonStaticGateway->getDebt($device);
        } catch (NoAccessRateFound) { // no access rate found
            return $transactionData;
        }

        if ($debt_amount > 0) { // there is unpaid amount
            $satisfied = true;
            if ($debt_amount > $transactionData->transaction->amount) {
                $debt_amount = $transactionData->transaction->amount;
                $transactionData->transaction->amount = 0;
                $satisfied = false;
            } else {
                $transactionData->transaction->amount -= $debt_amount;
            }
            $nonStaticGateway->updatePayment($accessRatePayment, $debt_amount, $satisfied);
            $transactionData->accessRateDebt = (int) $debt_amount;

            $deviceModel = $device->device;
            $paidFor = $deviceModel instanceof Meter ? $deviceModel->accessRate() : null;

            event(new PaymentSuccessEvent(
                amount: (int) $debt_amount,
                paymentService: $transactionData->transaction->original_transaction_type,
                paymentType: 'access rate',
                sender: $transactionData->transaction->sender,
                paidFor: $paidFor,
                payer: $device->person,
                transaction: $transactionData->transaction,
            ));
        }

        return $transactionData;
    }

    public function updatePayment(AccessRatePayment $accessRatePayment, float $paidAmount, bool $satisfied = false): void {
        $accessRatePayment->debt = $satisfied ? 0 : $accessRatePayment->debt - $paidAmount;
        $accessRatePayment->save();
    }

    private function getAccessRatePayment(Device $device): ?object {
        $deviceModel = $device->device;

        if (!$deviceModel instanceof Meter) {
            return null;
        }

        return $deviceModel->accessRatePayment()->first();
    }
}
