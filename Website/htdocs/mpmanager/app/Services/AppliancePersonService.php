<?php

namespace App\Services;

use App\Exceptions\DownPaymentBiggerThanAppliancePriceException;
use App\Models\AssetPerson;
use App\Models\MainSettings;
use PhpParser\Node\Stmt\Throw_;

class AppliancePersonService
{
    private $mainSettings;
    private $assetPerson;
    private $cashTransactionService;

    public function __construct(
        MainSettings $mainSettings,
        AssetPerson $assetPerson,
        CashTransactionService $cashTransactionService,
    ) {
        $this->mainSettings = $mainSettings;
        $this->assetPerson = $assetPerson;
        $this->cashTransactionService = $cashTransactionService;
    }

    private function checkDownPaymentIsBigger($downPayment, $cost)
    {
        if ($downPayment > $cost) {
            throw new DownPaymentBiggerThanAppliancePriceException(
                'Down payment is not bigger than appliance sold cost '
            );
        }
    }

    public function createFromRequest($request, $person, $assetType)
    {
        $this->checkDownPaymentIsBigger($request->input('downPayment'), $request->input('cost'));
        $assetPerson = $this->assetPerson::query()->make(
            [
                'person_id' => $person->id,
                'asset_type_id' => $assetType->id,
                'total_cost' => $request->input('cost'),
                'down_payment' => $request->input('downPayment'),
                'rate_count' => $request->input('rate'),
                'creator_id' => $request->input('creatorId')

            ]
        );

        $buyerAddress = $person->addresses()->where('is_primary', 1)->first();
        $sender = $buyerAddress == null ? '-' : $buyerAddress->phone;
        $transaction = null;
        if ((int)$request->input('downPayment') > 0) {
            $transaction = $this->cashTransactionService->createCashTransaction(
                $request->input('creatorId'),
                $request->input('downPayment'),
                $sender
            );
        }

        $assetPerson->save();
        $cost = $request->input('cost');
        $preferredPrice = $request->input('preferredPrice');

        // if appliance sold cost different than appliance preferred price
        if ($cost !== $preferredPrice) {
            $this->createLogForSoldAppliance($assetPerson, $cost, $preferredPrice);
        }

        $this->initSoldApplianceDataContainer(
            $assetType,
            $assetPerson,
            $transaction
        );

        return $assetPerson;
    }

    public function initSoldApplianceDataContainer($assetType, $assetPerson, $transaction)
    {
        $soldApplianceDataContainer = app()->makeWith(
            'App\Misc\SoldApplianceDataContainer',
            [
                'assetType' => $assetType,
                'assetPerson' => $assetPerson,
                'transaction' => $transaction
            ]
        );
        event('appliance.sold', $soldApplianceDataContainer);
    }

    public function createLogForSoldAppliance($assetPerson, $cost, $preferredPrice)
    {
        $currency = $this->getCurrencyFromMainSettings();

        event(
            'new.log',
            [
                'logData' => [
                    'user_id' => auth('api')->user()->id,
                    'affected' => $assetPerson,
                    'action' => 'Appliance is sold to ' . $cost . ' ' . $currency .
                        ' instead of Preferred Price (' . $preferredPrice . ' ' . $currency .  ')'
                ]
            ]
        );
    }

    public function getCurrencyFromMainSettings()
    {
        $mainSettings = $this->mainSettings->newQuery()->first();
        return $mainSettings === null ? '€' : $mainSettings->currency;
    }

    public function getApplianceDetails($applianceId)
    {
        $appliance = $this->assetPerson::with('assetType', 'rates.logs', 'logs.owner')
            ->where('id', '=', $applianceId)
            ->first();

        return $this->sumTotalPaymentsAndTotalRemainingAmount($appliance);
    }

    private function sumTotalPaymentsAndTotalRemainingAmount($appliance)
    {
        $rates = Collect($appliance->rates);
        $appliance['totalRemainingAmount'] = 0;
        $appliance['totalPayments'] = 0;

        $rates->map(function ($rate) use ($appliance) {
                $appliance['totalRemainingAmount'] +=  $rate->remaining;
            if ($rate->remaining !== $rate->rate_cost) {
                $appliance['totalPayments'] += $rate->rate_cost - $rate->remaining;
            }
        });

        return $appliance;
    }
}
