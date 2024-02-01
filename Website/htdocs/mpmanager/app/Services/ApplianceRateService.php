<?php

namespace App\Services;

use App\Models\AssetRate;
use App\Models\MainSettings;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ApplianceRateService implements IBaseService
{
    public function __construct(
        private AssetRate $applianceRate,
        private MainSettings $mainSettings
    ) {
    }

    public function getCurrencyFromMainSettings(): string
    {
        /** @var MainSettings $mainSettings */
        $mainSettings = $this->mainSettings->newQuery()->first();
        return $mainSettings === null ? '€' : $mainSettings->currency;
    }

    public function updateApplianceRateCost(AssetRate $applianceRate, $creatorId, $cost, $newCost): AssetRate
    {
        $currency = $this->getCurrencyFromMainSettings();
        event(
            'new.log',
            [
                'logData' => [
                    'user_id' => $creatorId,
                    'affected' => $applianceRate->assetPerson,
                    'action' => 'Appliance rate ' . date(
                        'd-m-Y',
                        strtotime($applianceRate->due_date)
                    ) . ' cost updated. From '
                        . $cost . ' ' . $currency . ' to ' . $newCost . ' ' . $currency
                ]
            ]
        );
        $applianceRate->rate_cost = $newCost;
        $applianceRate->remaining = $newCost;
        $applianceRate->update();
        $applianceRate->save();
        return $applianceRate->refresh();
    }

    public function deleteUpdatedApplianceRateIfCostZero(AssetRate $applianceRate, $creatorId, $cost, $newCost): void
    {
        $currency = $this->getCurrencyFromMainSettings();
        $appliancePerson = $applianceRate->assetPerson;
        $applianceRate->delete();
        event(
            'new.log',
            [
                'logData' => [
                    'user_id' => $creatorId,
                    'affected' => $appliancePerson,
                    'action' => 'Appliance rate ' . date(
                        'd-m-Y',
                        strtotime($applianceRate->due_date)
                    ) . ' deleted. From '
                        . $cost . ' ' . $currency . ' to ' . $newCost . ' ' . $currency
                ]
            ]
        );
    }

    public function getByLoanIdsForDueDate($loanIds): Collection
    {
        return $this->applianceRate->newQuery()->with('assetPerson.asset')
            ->whereIn('asset_person_id', $loanIds)
            ->where('remaining', '>', 0)
            ->whereDate('due_date', '<', date('Y-m-d'))
            ->get();
    }

    public function getAllByLoanId($loanId): Collection
    {
        return $this->applianceRate->newQuery()->with('assetPerson.asset')
            ->where('asset_person_id', $loanId)
            ->get();
    }

    public function getById($id)
    {
        // TODO: Implement getById() method.
    }

    public function create($assetPerson, $installmentType = 'monthly'): void
    {
        $baseTime = $assetPerson->first_payment_date ?? date('Y-m-d');
        $installment = $installmentType === 'monthly' ? 'month' : 'week';
        if ($assetPerson->down_payment > 0) {
            $this->applianceRate->newQuery()->create(
                [
                    'asset_person_id' => $assetPerson->id,
                    'rate_cost' => $assetPerson->down_payment,
                    'remaining' => 0,
                    'due_date' => Carbon::parse(date('Y-m-d'))->toIso8601ZuluString(),
                    'remind' => 0
                ]
            );
            $assetPerson->total_cost -= $assetPerson->down_payment;
        }
        foreach (range(1, $assetPerson->rate_count) as $rate) {
            if ($assetPerson->rate_count === 0) {
                $rateCost = 0;
            } elseif ((int)$rate === (int)$assetPerson->rate_count) {
                //last rate
                $rateCost = $assetPerson->total_cost
                    - (($rate - 1) * floor($assetPerson->total_cost / $assetPerson->rate_count));
            } else {
                $rateCost = floor($assetPerson->total_cost / $assetPerson->rate_count);
            }
            $rateDate = date('Y-m-d', strtotime('+' . $rate . " $installment", strtotime($baseTime)));

            $this->applianceRate->newQuery()->create(
                [
                    'asset_person_id' => $assetPerson->id,
                    'rate_cost' => $rateCost,
                    'remaining' => $rateCost,
                    'due_date' => $rateDate,
                    'remind' => 0
                ]
            );
        }
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
        // TODO: Implement getAll() method.
    }


    public function getDownPaymentAsAssetRate($assetPerson): ?AssetRate
    {
        /** @var ?AssetRate $result */
        $result = $this->applianceRate->newQuery()
            ->where('asset_person_id', $assetPerson->id)
            ->where('rate_cost', $assetPerson->down_payment)
            ->where('remaining', 0)
            ->first();

        return $result;
    }

    public function queryOutstandingDebtsByApplianceRates(CarbonImmutable $toDate): Builder
    {
        return $this->applianceRate->newQuery()
            ->with(['assetPerson.asset', 'assetPerson.person'])
            ->where('due_date', '<', $toDate->format('Y-m-d'))
            ->where('remaining', '>', 0)
            ->groupBy('asset_person_id')
            ->orderBy('id');
    }
}
