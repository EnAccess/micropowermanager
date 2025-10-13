<?php

namespace App\PaymentHandler;

use App\Events\PaymentSuccessEvent;
use App\Exceptions\AccessRates\NoAccessRateFound;
use App\Misc\TransactionDataContainer;
use App\Models\AccessRate\AccessRate as AccessRateModel;
use App\Models\AccessRate\AccessRatePayment;
use App\Models\Meter\Meter;
use Carbon\Carbon;

class AccessRate {
    private ?AccessRateModel $accessRate = null;

    /**
     * @throws NoAccessRateFound
     */
    public static function withMeter(Meter $meter): AccessRate {
        if ($meter->tariff->accessRate === null) {
            throw new NoAccessRateFound('Tariff  '.$meter->tariff->name.' has no access rate');
        }
        $accessRate = new self();
        $accessRate->accessRate = $meter->accessRate();
        $accessRate->setMeter();

        return $accessRate;
    }

    private function setMeter(): void {}

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
    private function getDebt(Meter $meter): int {
        $accessRate = $meter->accessRatePayment()->first();
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
        // get accessRatePayment
        $accessRatePayment = $nonStaticGateway->getAccessRatePayment($transactionData->meter);
        try {
            $debt_amount = $nonStaticGateway->getDebt($transactionData->meter);
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
            $transactionData->accessRateDebt = $debt_amount;
            event(new PaymentSuccessEvent(
                amount: $debt_amount,
                paymentService: $transactionData->transaction->original_transaction_type,
                paymentType: 'access rate',
                sender: $transactionData->transaction->sender,
                paidFor: $transactionData->meter->accessRate(),
                payer: $transactionData->meter->device->person,
                transaction: $transactionData->transaction,
            ));
        }

        return $transactionData;
    }

    public function updatePayment(AccessRatePayment $accessRatePayment, int $paidAmount, bool $satisfied = false): void {
        $accessRatePayment->debt = $satisfied ? 0 : $accessRatePayment->debt - $paidAmount;
        $accessRatePayment->save();
    }

    private function getAccessRatePayment(Meter $meter): ?object {
        return $meter->accessRatePayment()->first();
    }
}
