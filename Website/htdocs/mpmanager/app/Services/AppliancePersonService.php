<?php

namespace App\Services;

use App\Exceptions\DownPaymentBiggerThanAppliancePriceException;
use App\Misc\SoldApplianceDataContainer;
use App\Models\AssetPerson;
use App\Models\AssetType;
use App\Models\MainSettings;
use PhpParser\Node\Stmt\Throw_;

class AppliancePersonService implements IBaseService, IAssociative
{
    public function __construct(
        private MainSettings $mainSettings,
        private AssetPerson $assetPerson,
        private CashTransactionService $cashTransactionService,
    ) {
    }

    public function make($data)
    {
        return $this->assetPerson->newQuery()->make($data);
    }

    public function save($appliancePerson)
    {
        $appliancePerson->save();
    }

    private function checkDownPaymentIsBigger($downPayment, $cost)
    {
        if ($downPayment > $cost) {
            throw new DownPaymentBiggerThanAppliancePriceException(
                'Down payment is not bigger than appliance sold cost '
            );
        }
    }

    public function createFromRequest($request, $person, $asset)
    {
        $this->checkDownPaymentIsBigger($request->input('down_payment'), $request->input('cost'));
        $assetPerson = $this->assetPerson::query()->make(
            [
                'person_id' => $person->id,
                'asset_id' => $asset->id,
                'total_cost' => $request->input('cost'),
                'down_payment' => $request->input('downPayment'),
                'rate_count' => $request->input('rate'),
                'creator_id' => $request->input('creatorId')
            ]
        );

        $buyerAddress = $person->addresses()->where('is_primary', 1)->first();
        $sender = $buyerAddress == null ? '-' : $buyerAddress->phone;
        $transaction = null;

        $meter = null;
        if ($asset->assetType->id === AssetType::APPLIANCE_TYPE_SHS) {
            $meterParameter = $person->meters()->whereHas(
                'meter',
                static function ($q) {
                    $q->whereHas(
                        'manufacturer',
                        static function ($q) {
                            $q->where('name', 'SunKing SHS');
                        }
                    );
                }
            )->first();
            $meter = $meterParameter?->meter;
        }
        if ((int)$request->input('downPayment') > 0) {
            $transaction = $this->cashTransactionService->createCashTransaction(
                $request->input('creatorId'),
                $request->input('downPayment'),
                $sender,
                $meter
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
            $asset,
            $assetPerson,
            $transaction
        );

        return $assetPerson;
    }

    public function initSoldApplianceDataContainer($asset, $assetPerson, $transaction)
    {
        $soldApplianceDataContainer = app()->makeWith(
            SoldApplianceDataContainer::class,
            [
                'asset' => $asset,
                'assetType' => $asset->assetType,
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
                        ' instead of Preferred Price (' . $preferredPrice . ' ' . $currency . ')'
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
        $appliance = $this->assetPerson::with('asset', 'rates.logs', 'logs.owner')
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
            $appliance['totalRemainingAmount'] += $rate->remaining;
            if ($rate->remaining !== $rate->rate_cost) {
                $appliance['totalPayments'] += $rate->rate_cost - $rate->remaining;
            }
        });

        return $appliance;
    }

    public function getLoansForCustomerId($customerId)
    {
        return $this->assetPerson->newQuery()->where('person_id', $customerId);
    }

    public function getById($id)
    {
        // TODO: Implement getById() method.
    }

    public function create($data)
    {
        // TODO: Implement create() method.
    }

    public function update($model, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }

    public function getAll($limit = null)
    {
        if ($limit) {
            return $this->assetPerson->newQuery()->with(['person.devices'])->paginate($limit);
        }
        return $this->assetPerson->newQuery()->with(['person.devices'])->get();
    }

    public function getLoanIdsForCustomerId($customerId)
    {
        return $this->assetPerson->newQuery()
            ->where('person_id', $customerId)
            ->where('device_serial', null)
            ->orWhere('device_serial', '')->pluck('id');
    }

    public function getBySerialNumber($serialNumber)
    {
        return $this->assetPerson->newQuery()->where('device_serial', $serialNumber)->first();
    }
}
