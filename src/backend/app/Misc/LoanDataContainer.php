<?php

namespace App\Misc;

use App\Events\PaymentSuccessEvent;
use App\Exceptions\Meters\MeterIsNotAssignedToCustomer;
use App\Exceptions\Meters\MeterIsNotInUse;
use App\Models\AssetPerson;
use App\Models\AssetRate;
use App\Models\Meter\Meter;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class LoanDataContainer {
    private Person $meterOwner;
    private Transaction $transaction;

    /**
     * @var array<int, array{asset_type_name: string, paid: float}>
     */
    public array $paid_rates = [];

    public function initialize(Transaction $transaction): void {
        $this->transaction = $transaction;
        $this->meterOwner = $this->getMeterOwner($transaction->message);
    }

    public function loanCost(): float {
        $loans = $this->getCustomerDueRates($this->meterOwner);

        foreach ($loans as $loan) {
            if ($loan->remaining > $this->transaction->amount) {// money is not enough to cover the whole rate
                event(new PaymentSuccessEvent(
                    amount: $this->transaction->amount,
                    paymentService: $this->transaction->original_transaction_type,
                    paymentType: 'installment',
                    sender: $this->transaction->sender,
                    paidFor: $loan,
                    payer: $this->meterOwner,
                    transaction: $this->transaction,
                ));
                $loan->update(
                    ['remaining' => $this->transaction->amount]
                );

                $this->paid_rates[] = [
                    'asset_type_name' => $loan->assetPerson->asset->assetType->name,
                    'paid' => $this->transaction->amount,
                ];

                $this->transaction->amount = 0;
                break;
            } else {
                event(new PaymentSuccessEvent(
                    amount: $loan->remaining,
                    paymentService: $this->transaction->original_transaction_type,
                    paymentType: 'installment',
                    sender: $this->transaction->sender,
                    paidFor: $loan,
                    payer: $this->meterOwner,
                    transaction: $this->transaction,
                ));
                $this->paid_rates[] = [
                    'asset_type_name' => $loan->assetPerson->asset->assetType->name,
                    'paid' => $loan->remaining,
                ];
                $this->transaction->amount -= $loan->remaining;
                $loan->remaining = 0;
                $loan->update();
            }
        }

        return $this->transaction->amount;
    }

    /**
     * @return Collection<int, AssetRate>
     */
    private function getCustomerDueRates(Person $owner): Collection {
        $loans = AssetPerson::query()->where('person_id', $owner->id)->pluck('id');

        return AssetRate::with('assetPerson.device')
            ->whereIn('asset_person_id', $loans)
            ->where('remaining', '>', 0)
            ->whereDate('due_date', '<', date('Y-m-d'))
            ->get();
    }

    /**
     * @throws MeterIsNotInUse
     * @throws MeterIsNotAssignedToCustomer
     */
    private function getMeterOwner(string $serialNumber): Person {
        try {
            $meter = Meter::with('device.person')
                ->where('serial_number', $serialNumber)
                ->firstOrFail();
        } catch (ModelNotFoundException) {
            throw new MeterIsNotAssignedToCustomer('');
        }

        // meter is not been used by anyone
        if (!$meter->in_use) {
            throw new MeterIsNotInUse($serialNumber.' meter is not in use');
        }

        return $meter->device->person;
    }
}
