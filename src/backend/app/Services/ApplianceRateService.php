<?php

namespace App\Services;

use App\Events\NewLogEvent;
use App\Models\AssetRate;
use App\Models\MainSettings;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

// FIXME:
// class ApplianceRateService implements IBaseService
class ApplianceRateService {
    public function __construct(
        private AssetRate $applianceRate,
        private MainSettings $mainSettings,
    ) {}

    public function getCurrencyFromMainSettings(): string {
        $mainSettings = $this->mainSettings->newQuery()->first();

        return $mainSettings?->currency ?? '€';
    }

    public function updateApplianceRateCost(AssetRate $applianceRate, int $creatorId, int $cost, int $newCost): AssetRate {
        $currency = $this->getCurrencyFromMainSettings();
        event(new NewLogEvent([
            'user_id' => $creatorId,
            'affected' => $applianceRate->assetPerson,
            'action' => 'Appliance rate '.date(
                'd-m-Y',
                strtotime($applianceRate->due_date)
            ).' cost updated. From '
                .$cost.' '.$currency.' to '.$newCost.' '.$currency,
        ]));
        $applianceRate->rate_cost = $newCost;
        $applianceRate->remaining = $newCost;
        $applianceRate->update();
        $applianceRate->save();

        return $applianceRate->refresh();
    }

    public function deleteUpdatedApplianceRateIfCostZero(AssetRate $applianceRate, int $creatorId, float $cost, float $newCost): void {
        $currency = $this->getCurrencyFromMainSettings();
        $appliancePerson = $applianceRate->assetPerson;
        $applianceRate->delete();
        event(new NewLogEvent([
            'user_id' => $creatorId,
            'affected' => $appliancePerson,
            'action' => 'Appliance rate '.date(
                'd-m-Y',
                strtotime($applianceRate->due_date)
            ).' deleted. From '
                .$cost.' '.$currency.' to '.$newCost.' '.$currency,
        ]));
    }

    /**
     * @return Collection<int, AssetRate>
     */
    public function getByLoanIdsForDueDate(array $loanIds): Collection {
        return $this->applianceRate->newQuery()->with('assetPerson.asset')
            ->whereIn('asset_person_id', $loanIds)
            ->where('remaining', '>', 0)
            ->whereDate('due_date', '<', date('Y-m-d'))
            ->get();
    }

    /**
     * @return Collection<int, AssetRate>
     */
    public function getAllByLoanId(int $loanId): Collection {
        return $this->applianceRate->newQuery()->with('assetPerson.asset')
            ->where('asset_person_id', $loanId)
            ->get();
    }

    public function getById(int $id): AssetRate {
        throw new \Exception('Method getById() not yet implemented.');
    }

    public function create(object $assetPerson, string $installmentType = 'monthly'): void {
        $baseTime = $assetPerson->first_payment_date ?? date('Y-m-d');
        $installment = $installmentType === 'monthly' ? 'month' : 'week';
        if ($assetPerson->down_payment > 0) {
            $this->applianceRate->newQuery()->create(
                [
                    'asset_person_id' => $assetPerson->id,
                    'rate_cost' => round($assetPerson->down_payment),
                    'remaining' => 0,
                    'due_date' => Carbon::parse(date('Y-m-d'))->toDateTimeString(),
                    'remind' => 0,
                ]
            );
            $assetPerson->total_cost -= $assetPerson->down_payment;
        }
        foreach (range(1, $assetPerson->rate_count) as $rate) {
            if ($assetPerson->rate_count === 0) {
                $rateCost = 0;
            } elseif ((int) $rate === (int) $assetPerson->rate_count) {
                // last rate
                $rateCost = $assetPerson->total_cost
                    - (($rate - 1) * floor($assetPerson->total_cost / $assetPerson->rate_count));
            } else {
                $rateCost = floor($assetPerson->total_cost / $assetPerson->rate_count);
            }
            $rateDate = date('Y-m-d', strtotime('+'.$rate." $installment", strtotime($baseTime)));

            $this->applianceRate->newQuery()->create(
                [
                    'asset_person_id' => $assetPerson->id,
                    'rate_cost' => $rateCost,
                    'remaining' => $rateCost,
                    'due_date' => $rateDate,
                    'remind' => 0,
                ]
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(object $model, array $data): AssetRate {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete(object $model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    /**
     * @return Collection<int, AssetRate>
     */
    public function getAll(?int $limit = null): Collection {
        throw new \Exception('Method getAll() not yet implemented.');
    }

    public function getDownPaymentAsAssetRate(object $assetPerson): ?AssetRate {
        /** @var ?AssetRate $result */
        $result = $this->applianceRate->newQuery()
            ->where('asset_person_id', $assetPerson->id)
            ->where('rate_cost', round($assetPerson->down_payment))
            ->where('remaining', 0)
            ->first();

        return $result;
    }

    /**
     * @return Builder<AssetRate>
     */
    public function queryOutstandingDebtsByApplianceRates(CarbonImmutable $toDate): Builder {
        return $this->applianceRate->newQuery()
            ->with(['assetPerson.asset', 'assetPerson.person'])
            ->where('due_date', '<', $toDate->format('Y-m-d'))
            ->where('remaining', '>', 0)
            ->orderBy('id');
    }
}
