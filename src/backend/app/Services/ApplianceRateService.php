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

    public function recomputeRatesFromTotalCost(
        AppliancePerson $appliancePerson,
        int $newTotalCost,
        int $creatorId,
        ?int $rateCount = null,
        ?string $rateType = null,
    ): AppliancePerson {
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

        if ($rateCount === null) {
            $this->redistributeAcrossUnpaidRates($unpaidRates, $newOutstanding);
        } else {
            $this->regenerateUnpaidRates($appliancePerson, $rates, $unpaidRates, $newOutstanding, $rateCount, $rateType ?? 'monthly');
        }

        $oldTotalCost = (int) $appliancePerson->total_cost;
        $appliancePerson->total_cost = $newTotalCost;
        $appliancePerson->save();

        $currency = $this->getCurrencyFromMainSettings();
        $creatorName = $this->userService->getById($creatorId)->name ?? 'Unknown';
        $action = "User {$creatorName} updated Total cost from {$oldTotalCost} {$currency} to {$newTotalCost} {$currency}";
        if ($rateCount !== null) {
            $action .= " and rescheduled the outstanding balance into {$rateCount} {$rateType} installments";
        }
        event(new NewLogEvent([
            'user_id' => $creatorId,
            'affected' => $appliancePerson,
            'action' => $action,
        ]));

        return $appliancePerson->refresh();
    }

    /**
     * Spread the new outstanding amount across the existing unpaid rates,
     * preserving their count and due dates.
     *
     * @param Collection<int, ApplianceRate> $unpaidRates
     */
    private function redistributeAcrossUnpaidRates(Collection $unpaidRates, int $newOutstanding): void {
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
    }

    /**
     * Replace the unpaid rates with a freshly generated schedule of the given
     * count and cadence, leaving paid/partially paid rates untouched. New rates
     * continue after the latest settled (paid/partially paid) rate, or from the
     * plan's first payment date when nothing has been settled yet.
     *
     * @param Collection<int, ApplianceRate> $allRates
     * @param Collection<int, ApplianceRate> $unpaidRates
     */
    private function regenerateUnpaidRates(
        AppliancePerson $appliancePerson,
        Collection $allRates,
        Collection $unpaidRates,
        int $newOutstanding,
        int $rateCount,
        string $rateType,
    ): void {
        if ($newOutstanding > 0 && $rateCount < 1) {
            throw ValidationException::withMessages(['rate_count' => 'At least one installment is required for the outstanding amount']);
        }

        $keptRates = $allRates->filter(
            fn (ApplianceRate $rate): bool => $rate->rate_cost !== $rate->remaining
        );
        $latestSettledDueDate = $keptRates->max(fn (ApplianceRate $rate) => $rate->due_date);
        $anchor = $latestSettledDueDate
            ? Carbon::parse($latestSettledDueDate)->format('Y-m-d')
            : Carbon::parse($appliancePerson->first_payment_date ?? date('Y-m-d'))->format('Y-m-d');

        $keptCount = $keptRates->count();
        $unpaidRates->each(fn (ApplianceRate $rate) => $rate->delete());

        if ($newOutstanding > 0) {
            $installment = $rateType === 'weekly' ? 'week' : 'month';
            $this->generateRateSchedule($appliancePerson->id, $newOutstanding, $rateCount, $installment, $anchor);
            $appliancePerson->rate_count = $keptCount + $rateCount;
        } else {
            $appliancePerson->rate_count = $keptCount;
        }
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
        $baseDate = $appliancePerson->first_payment_date ?? date('Y-m-d');
        $installment = $installmentType === 'monthly' ? 'month' : 'week';
        if ($appliancePerson->down_payment > 0) {
            $appliancePerson->total_cost -= $appliancePerson->down_payment;
        }

        $this->generateRateSchedule(
            (int) $appliancePerson->id,
            (float) $appliancePerson->total_cost,
            (int) $appliancePerson->rate_count,
            $installment,
            $baseDate,
        );
    }

    /**
     * Create $count rate rows summing to $amount, split evenly with the last
     * rate absorbing the rounding remainder, due $baseDate + i * $installment.
     */
    private function generateRateSchedule(int $appliancePersonId, float $amount, int $count, string $installment, string $baseDate): void {
        if ($count < 1) {
            return;
        }

        $base = floor($amount / $count);
        foreach (range(1, $count) as $rate) {
            $rateCost = ($rate === $count)
                ? $amount - (($rate - 1) * $base)
                : $base;
            $rateDate = date('Y-m-d', strtotime('+'.$rate." $installment", strtotime($baseDate)));

            $this->applianceRate->newQuery()->create(
                [
                    'appliance_person_id' => $appliancePersonId,
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
            ->with(['appliancePerson.appliance', 'appliancePerson.person', 'appliancePerson.rates'])
            ->where('due_date', '<', $toDate->format('Y-m-d'))
            ->where('remaining', '>', 0)
            ->orderBy('id');
    }
}
