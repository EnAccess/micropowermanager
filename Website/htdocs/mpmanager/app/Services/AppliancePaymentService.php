<?php

namespace App\Services;

use App\Exceptions\PaymentAmountBiggerThanTotalRemainingAmount;
use App\Exceptions\PaymentAmountSmallerThanZero;
use App\Models\AssetRate;
use App\Models\AssetType;
use App\Models\MainSettings;
use App\Models\Person\Person;

class AppliancePaymentService
{
    private $cashTransactionService;
    private $applianceType;
    private $person;
    private $payment;
    private $mainSettings;
    private $appliancePersonService;

    public function __construct(
        CashTransactionService $cashTransactionService,
        AssetType $applianceType,
        Person $person,
        MainSettings $mainSettings,
        AppliancePersonService $appliancePersonService
    ) {
        $this->cashTransactionService = $cashTransactionService;
        $this->applianceType = $applianceType;
        $this->person = $person;
        $this->mainSettings = $mainSettings;
        $this->appliancePersonService = $appliancePersonService;
    }

    public function getPaymentForAppliance($request, $appliancePerson)
    {
        $creatorId = auth('api')->user()->id;
        $this->payment = (int)$request->input('amount');
        $soldApplianceDetail = $this->appliancePersonService->getApplianceDetails($appliancePerson->id);
        if ($this->payment > $soldApplianceDetail->totalRemainingAmount) {
            throw new PaymentAmountBiggerThanTotalRemainingAmount(
                'Payment Amount can not bigger than Total Remaining Amount'
            );
        }
        if ($this->payment <= 0) {
            throw new PaymentAmountSmallerThanZero(
                'Payment amount can not smaller than zero'
            );
        }
        $rates = Collect($soldApplianceDetail->rates);
        $buyer = $appliancePerson->person;
        $applianceType = $this->applianceType->newQuery()->find($appliancePerson->asset_type_id);
        $buyerAddress = $buyer->addresses()->where('is_primary', 1)->first();
        $sender = $buyerAddress == null ? '-' : $buyerAddress->phone;
        $transaction = $this->cashTransactionService->createCashTransaction($creatorId, $this->payment, $sender);
        $rates->map(function ($rate) use ($buyer, $applianceType, $transaction) {
            if ($rate['remaining'] > 0 && $this->payment > 0) {
                if ($rate['remaining'] <= $this->payment) {
                    $this->payment -= $rate['remaining'];
                    $applianceRate = $this->updateRateRemaining($rate['id'], $rate['remaining']);
                    $this->createPaymentHistory($rate['remaining'], $buyer, $applianceRate, $transaction);
                } else {
                    $applianceRate = $this->updateRateRemaining($rate['id'], $this->payment);
                    $this->createPaymentHistory($this->payment, $buyer, $applianceRate, $transaction);
                    $this->payment = 0;
                }
            }
        });

        $this->createPaymentLog($appliancePerson, (int)$request->input('amount'), $creatorId);
    }

    public function updateRateRemaining($id, $amount)
    {
        $applianceRate = AssetRate::find($id);
        $applianceRate->remaining -= $amount;
        $applianceRate->update();
        $applianceRate->save();
        return $applianceRate;
    }

    public function createPaymentLog($appliancePerson, $amount, $creatorId)
    {
        $mainSettings = $this->mainSettings->newQuery()->first();
        $currency = $mainSettings === null ? '€' : $mainSettings->currency;
        event(
            'new.log',
            [
                'logData' => [
                    'user_id' => $creatorId,
                    'affected' => $appliancePerson,
                    'action' => $amount . ' ' . $currency . ' of payment is made '
                ]
            ]
        );
    }

    public function createPaymentHistory($amount, $buyer, $applianceRate, $transaction)
    {
        event(
            'payment.successful',
            [
                'amount' => $amount,
                'paymentService' => 'web',
                'paymentType' => 'loan rate',
                'sender' => $transaction->sender,
                'paidFor' => $applianceRate,
                'payer' => $buyer,
                'transaction' => $transaction,
            ]
        );
    }
}
