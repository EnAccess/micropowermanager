<?php

namespace App\Misc;

use App\Exceptions\MeterParameter\MeterParameterNotFound;
use App\Exceptions\Meters\MeterIsNotAssignedToCustomer;
use App\Exceptions\Meters\MeterIsNotInUse;
use App\Models\AssetPerson;
use App\Models\AssetRate;
use App\Models\Meter\Meter;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class LoanDataContainer
{
    private $meterOwner;
    private $transaction;


    public $paid_rates = array();

    public function initialize(Transaction $transaction): void
    {
        $this->transaction = $transaction;
        $this->meterOwner = $this->getMeterOwner($transaction->message);
    }

    public function loanCost()
    {
        if (!$this->meterOwner) {
            throw new MeterParameterNotFound('loan data container');
        }
        $loans = $this->getCustomerDueRates($this->meterOwner);

        foreach ($loans as $loan) {
            if ($loan->remaining > $this->transaction->amount) {// money is not enough to cover the whole rate
                //add payment history for the loan
                event(
                    'payment.successful',
                    [
                    'amount' => $this->transaction->amount,
                    'paymentService' => $this->transaction->original_transaction_type,
                    'paymentType' => 'loan rate',
                    'sender' => $this->transaction->sender,
                    'paidFor' => $loan,
                    'payer' => $this->meterOwner,
                    'transaction' => $this->transaction,
                    ]
                );
                $loan->remaining -= $this->transaction->amount;
                $loan->update();

                $this->paid_rates[] = [
                    'asset_type_name' => $loan->assetPerson->assetType->name,
                    'paid' => $this->transaction->amount,
                ];

                $this->transaction->amount = 0;
                break;
            } else {
                //add payment history for the loan
                event(
                    'payment.successful',
                    [
                    'amount' => $loan->remaining,
                    'paymentService' => $this->transaction->original_transaction_type,
                    'paymentType' => 'loan rate',
                    'sender' => $this->transaction->sender,
                    'paidFor' => $loan,
                    'payer' => $this->meterOwner,
                    'transaction' => $this->transaction,
                    ]
                );
                $this->paid_rates[] = [
                    'asset_type_name' => $loan->assetPerson->assetType->name,
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
     * @param $owner
     * @return Builder[]|Collection
     *
     * @psalm-return \Illuminate\Database\Eloquent\Collection|array<array-key, \Illuminate\Database\Eloquent\Builder>
     */
    private function getCustomerDueRates($owner)
    {
        $loans = AssetPerson::where('person_id', $owner->id)->pluck('id');
        return AssetRate::with('assetPerson.assetType')
            ->whereIn('asset_person_id', $loans)
            ->where('remaining', '>', 0)
            ->whereDate('due_date', '<', date('Y-m-d'))
            ->get();
    }

    /**
     * @param String $serialNumber
     *
     * @return mixed
     * @throws MeterIsNotInUse
     * @throws MeterIsNotAssignedToCustomer
     */
    private function getMeterOwner(string $serialNumber)
    {
        try {
            $meter = Meter::with('meterParameter.owner')
                ->where('serial_number', $serialNumber)
                ->firstOrFail();
        } catch (ModelNotFoundException $ex) {
            throw new MeterIsNotAssignedToCustomer('');
        }

        //meter is not been used by anyone
        if (!$meter->in_use) {
            throw new MeterIsNotInUse($serialNumber . ' meter is not in use');
        }
        return $meter->meterParameter->owner;
    }
}
