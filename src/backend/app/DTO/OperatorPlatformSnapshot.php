<?php

declare(strict_types=1);

namespace App\DTO;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * The platform-wide roll-up the operator dashboard reads.
 *
 * It is folded from the per-tenant snapshots on every read rather than cached in
 * its own right, so there is never a stored copy that can disagree with the
 * tenant snapshots it summarises.
 */
class OperatorPlatformSnapshot {
    /**
     * @param array<string, mixed>       $summary
     * @param array<string, mixed>       $monthly
     * @param list<array<string, mixed>> $tenants
     */
    private function __construct(
        private readonly array $summary,
        private readonly array $monthly,
        private readonly array $tenants,
        private readonly ?CarbonInterface $generatedAt,
        private readonly bool $refreshing,
    ) {}

    /** @param list<OperatorTenantSnapshot> $snapshots */
    public static function fold(array $snapshots, ?CarbonInterface $generatedAt, bool $refreshing): self {
        $periods = self::periods();

        $transactionsPerPeriod = array_fill_keys($periods, 0);
        $transactionsPerPeriodByProvider = [];

        $customers = 0;
        $meters = 0;
        $shs = 0;
        $ebikes = 0;
        $transactionsThisMonth = 0;
        $transactionsLastMonth = 0;
        $newTenantsThisMonth = 0;
        $activeTenants = 0;

        $currentMonth = Carbon::now()->startOfMonth();

        foreach ($snapshots as $snapshot) {
            $customers += $snapshot->customers;
            $meters += $snapshot->devices['meters'];
            $shs += $snapshot->devices['shs'];
            $ebikes += $snapshot->devices['ebikes'];
            $transactionsThisMonth += $snapshot->transactionsThisMonth;
            $transactionsLastMonth += $snapshot->transactionsLastMonth;

            if ($snapshot->transactionsThisMonth > 0) {
                ++$activeTenants;
            }

            if ($snapshot->registeredAt !== null && $snapshot->registeredAt->greaterThanOrEqualTo($currentMonth)) {
                ++$newTenantsThisMonth;
            }

            foreach ($snapshot->monthlyTransactions as $period => $count) {
                if (array_key_exists($period, $transactionsPerPeriod)) {
                    $transactionsPerPeriod[$period] += $count;
                }
            }

            foreach ($snapshot->monthlyTransactionsByProvider as $provider => $countsPerPeriod) {
                foreach ($countsPerPeriod as $period => $count) {
                    if (!array_key_exists($period, $transactionsPerPeriod)) {
                        continue;
                    }
                    $transactionsPerPeriodByProvider[$provider] ??= array_fill_keys($periods, 0);
                    $transactionsPerPeriodByProvider[$provider][$period] += $count;
                }
            }
        }

        $tenantsTotal = count($snapshots);

        // Providers are ordered by total volume so the stacked chart keeps a
        // stable, meaningful series order across rebuilds.
        uasort(
            $transactionsPerPeriodByProvider,
            fn (array $left, array $right): int => array_sum($right) <=> array_sum($left)
        );

        return new self(
            summary: [
                'tenants_total' => $tenantsTotal,
                'tenants_new_this_month' => $newTenantsThisMonth,
                'tenants_active' => $activeTenants,
                'tenants_active_percentage' => $tenantsTotal === 0
                    ? 0.0
                    : round($activeTenants / $tenantsTotal * 100, 1),
                'transactions_this_month' => $transactionsThisMonth,
                'transactions_last_month' => $transactionsLastMonth,
                'transactions_trend_percentage' => $transactionsLastMonth === 0
                    ? null
                    : round(($transactionsThisMonth - $transactionsLastMonth) / $transactionsLastMonth * 100, 1),
                'customers_total' => $customers,
                'devices_total' => [
                    'total' => $meters + $shs + $ebikes,
                    'meters' => $meters,
                    'shs' => $shs,
                    'ebikes' => $ebikes,
                ],
            ],
            monthly: [
                'periods' => $periods,
                'transactions' => array_values($transactionsPerPeriod),
                'by_provider' => array_map(
                    array_values(...),
                    $transactionsPerPeriodByProvider
                ),
            ],
            tenants: array_map(
                fn (OperatorTenantSnapshot $snapshot): array => $snapshot->toRowArray(),
                $snapshots
            ),
            generatedAt: $generatedAt,
            refreshing: $refreshing,
        );
    }

    /** @return list<string> */
    public static function periods(): array {
        $months = (int) config('micropowermanager.operator_dashboard.series_months');
        $start = Carbon::now()->startOfMonth()->subMonths($months - 1);

        $periods = [];
        for ($month = 0; $month < $months; ++$month) {
            $periods[] = $start->copy()->addMonths($month)->format('Y-m');
        }

        return $periods;
    }

    /** @return array<string, mixed> */
    public function toArray(): array {
        return [
            'summary' => $this->summary,
            'monthly' => $this->monthly,
            'tenants' => $this->tenants,
            'generated_at' => $this->generatedAt?->toIso8601String(),
            'refreshing' => $this->refreshing,
            'stale' => $this->isStale(),
        ];
    }

    private function isStale(): bool {
        if (!$this->generatedAt instanceof CarbonInterface) {
            return true;
        }

        $staleAfterHours = (int) config('micropowermanager.operator_dashboard.stale_after_hours');

        return $this->generatedAt->lessThan(Carbon::now()->subHours($staleAfterHours));
    }
}
