<?php

namespace App\Services;

use App\Events\NewLogEvent;
use App\Models\AppliancePerson;
use App\Models\ApplianceRate;
use App\Models\MainSettings;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

// FIXME:
// class ApplianceRateService implements IBaseService
class ApplianceRateService {
    public function __construct(
        private ApplianceRate $applianceRate,
        private MainSettings $mainSettings,
        private UserService $userService,
    ) {}

    public function getCurrencyFromMainSettings(): string {
        $mainSettings = $this->mainSettings->newQuery()->first();

        return $mainSettings->currency ?? '€';
    }

    public function recomputeRatesFromTotalCost(AppliancePerson $appliancePerson, int $newTotalCost, int $creatorId): AppliancePerson {
        if ($newTotalCost < 0) {
            throw ValidationException::withMessages(['new_total_cost' => 'Total cost cannot be negative']);
        }

        $rates = $appliancePerson->rates()->oldest('due_date')->get();
        $paidAmount = (int) $rates->sum(fn (ApplianceRate $rate): int => $rate->rate_cost - $rate->remaining);

        if ($newTotalCost < $paidAmount) {
            throw ValidationException::withMessages(['new_total_cost' => 'New total cannot be lower than the amount already paid']);
        }

        $newOutstanding = $newTotalCost - $paidAmount;
        $unpaidRates = $rates->filter(
            fn (ApplianceRate $rate): bool => $rate->rate_cost === $rate->remaining
        )->values();

        if ($unpaidRates->isEmpty() && $newOutstanding > 0) {
            throw ValidationException::withMessages(['new_total_cost' => 'All rates are paid or partially paid; edit individual rates instead']);
        }

        $count = $unpaidRates->count();
        if ($count > 0) {
            $base = intdiv($newOutstanding, $count);
            $remainder = $newOutstanding - $base * $count;
            $unpaidRates->each(function (ApplianceRate $rate, int $index) use ($base, $remainder, $count): void {
                $rateCost = $base + ($index === $count - 1 ? $remainder : 0);
                $rate->update(['rate_cost' => $rateCost, 'remaining' => $rateCost]);
            });
        }

        $oldTotalCost = (int) $appliancePerson->total_cost;
        $appliancePerson->total_cost = $newTotalCost;
        $appliancePerson->save();

        $currency = $this->getCurrencyFromMainSettings();
        $creatorName = $this->userService->getById($creatorId)->name ?? 'Unknown';
        event(new NewLogEvent([
            'user_id' => $creatorId,
            'affected' => $appliancePerson,
            'action' => "User {$creatorName} updated Total cost from {$oldTotalCost} {$currency} to {$newTotalCost} {$currency}",
        ]));

        return $appliancePerson->refresh();
    }

    public function updateApplianceRateCost(ApplianceRate $applianceRate, int $creatorId, int $cost, int $newCost): ApplianceRate {
        $currency = $this->getCurrencyFromMainSettings();
        event(new NewLogEvent([
            'user_id' => $creatorId,
            'affected' => $applianceRate->appliancePerson,
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

    public function deleteUpdatedApplianceRateIfCostZero(ApplianceRate $applianceRate, int $creatorId, float $cost, float $newCost): void {
        $currency = $this->getCurrencyFromMainSettings();
        $appliancePerson = $applianceRate->appliancePerson;
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
     * @param int[] $loanIds
     *
     * @return Collection<int, ApplianceRate>
     */
    public function getByLoanIdsForDueDate(array $loanIds): Collection {
        return $this->applianceRate->newQuery()->with('appliancePerson.appliance')
            ->whereIn('appliance_person_id', $loanIds)
            ->where('remaining', '>', 0)
            ->whereDate('due_date', '<', date('Y-m-d'))
            ->get();
    }

    /**
     * @return Collection<int, ApplianceRate>
     */
    public function getAllByLoanId(int $loanId): Collection {
        return $this->applianceRate->newQuery()->with('appliancePerson.appliance')
            ->where('appliance_person_id', $loanId)
            ->get();
    }

    public function getById(int $id): ApplianceRate {
        throw new \Exception('Method getById() not yet implemented.');
    }

    public function create(object $appliancePerson, string $installmentType = 'monthly'): void {
        $baseTime = $appliancePerson->first_payment_date ?? date('Y-m-d');
        $installment = $installmentType === 'monthly' ? 'month' : 'week';
        if ($appliancePerson->down_payment > 0) {
            $appliancePerson->total_cost -= $appliancePerson->down_payment;
        }
        foreach (range(1, $appliancePerson->rate_count) as $rate) {
            if ($appliancePerson->rate_count === 0) {
                $rateCost = 0;
            } elseif ((int) $rate === (int) $appliancePerson->rate_count) {
                // last rate
                $rateCost = $appliancePerson->total_cost
                    - (($rate - 1) * floor($appliancePerson->total_cost / $appliancePerson->rate_count));
            } else {
                $rateCost = floor($appliancePerson->total_cost / $appliancePerson->rate_count);
            }
            $rateDate = date('Y-m-d', strtotime('+'.$rate." $installment", strtotime($baseTime)));

            $this->applianceRate->newQuery()->create(
                [
                    'appliance_person_id' => $appliancePerson->id,
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
    public function update(object $model, array $data): ApplianceRate {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete(object $model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    /**
     * @return Collection<int, ApplianceRate>
     */
    public function getAll(?int $limit = null): Collection {
        throw new \Exception('Method getAll() not yet implemented.');
    }

    public function createPaidRate(AppliancePerson $appliancePerson, float $amount): ApplianceRate {
        return $this->applianceRate->newQuery()->create([
            'appliance_person_id' => $appliancePerson->id,
            'rate_cost' => round($amount),
            'remaining' => 0,
            'due_date' => Carbon::now()->toDateTimeString(),
            'remind' => 0,
        ]);
    }

    public function getDownPaymentAsApplianceRate(object $appliancePerson): ?ApplianceRate {
        return $this->applianceRate->newQuery()
            ->where('appliance_person_id', $appliancePerson->id)
            ->where('rate_cost', round($appliancePerson->down_payment))
            ->where('remaining', 0)
            ->first();
    }

    /**
     * @return Builder<ApplianceRate>
     */
    public function queryOutstandingDebtsByApplianceRates(CarbonImmutable $toDate): Builder {
        return $this->applianceRate->newQuery()
            ->with(['appliancePerson.appliance', 'appliancePerson.person'])
            ->where('due_date', '<', $toDate->format('Y-m-d'))
            ->where('remaining', '>', 0)
            ->orderBy('id');
    }
}
