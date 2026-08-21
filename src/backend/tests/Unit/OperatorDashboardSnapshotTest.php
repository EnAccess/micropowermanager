<?php

namespace Tests\Unit;

use App\DTO\OperatorPlatformSnapshot;
use App\DTO\OperatorTenantSnapshot;
use App\Enums\OperatorTenantHealth;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OperatorDashboardSnapshotTest extends TestCase {
    public function testItBucketsHealthOnTheConfiguredThresholds(): void {
        $this->assertSame(
            OperatorTenantHealth::Active,
            OperatorTenantHealth::fromLastActiveAt(Carbon::now()->subDays(7))
        );
        $this->assertSame(
            OperatorTenantHealth::Watch,
            OperatorTenantHealth::fromLastActiveAt(Carbon::now()->subDays(8))
        );
        $this->assertSame(
            OperatorTenantHealth::Watch,
            OperatorTenantHealth::fromLastActiveAt(Carbon::now()->subDays(21))
        );
        $this->assertSame(
            OperatorTenantHealth::Dormant,
            OperatorTenantHealth::fromLastActiveAt(Carbon::now()->subDays(22))
        );
    }

    public function testItTreatsATenantThatNeverTransactedAsDormant(): void {
        $this->assertSame(OperatorTenantHealth::Dormant, OperatorTenantHealth::fromLastActiveAt(null));
    }

    public function testItHonoursThresholdsFromConfiguration(): void {
        config()->set('micropowermanager.operator_dashboard.health.active_days', 30);

        $this->assertSame(
            OperatorTenantHealth::Active,
            OperatorTenantHealth::fromLastActiveAt(Carbon::now()->subDays(20))
        );
    }

    public function testItFoldsAnEmptyPlatform(): void {
        $snapshot = OperatorPlatformSnapshot::fold([], null, false)->toArray();

        $this->assertSame(0, $snapshot['summary']['tenants_total']);
        $this->assertSame(0.0, $snapshot['summary']['tenants_active_percentage']);
        $this->assertNull($snapshot['summary']['transactions_trend_percentage']);
        $this->assertTrue($snapshot['stale']);
        $this->assertCount(12, $snapshot['monthly']['periods']);
    }

    public function testItSumsCountersAcrossTenants(): void {
        $snapshot = OperatorPlatformSnapshot::fold([
            $this->snapshot(companyId: 1, customers: 100, meters: 80, transactionsThisMonth: 10),
            $this->snapshot(companyId: 2, customers: 50, meters: 40, transactionsThisMonth: 0),
        ], Carbon::now(), false)->toArray();

        $this->assertSame(2, $snapshot['summary']['tenants_total']);
        $this->assertSame(150, $snapshot['summary']['customers_total']);
        $this->assertSame(120, $snapshot['summary']['devices_total']['meters']);
        $this->assertSame(1, $snapshot['summary']['tenants_active']);
        $this->assertSame(50.0, $snapshot['summary']['tenants_active_percentage']);
    }

    public function testItReportsNoTrendWhenThePreviousMonthHadNoTransactions(): void {
        $snapshot = OperatorPlatformSnapshot::fold([
            $this->snapshot(companyId: 1, transactionsThisMonth: 25, transactionsLastMonth: 0),
        ], Carbon::now(), false)->toArray();

        $this->assertNull($snapshot['summary']['transactions_trend_percentage']);
    }

    public function testItComputesTheTransactionTrend(): void {
        $snapshot = OperatorPlatformSnapshot::fold([
            $this->snapshot(companyId: 1, transactionsThisMonth: 110, transactionsLastMonth: 100),
        ], Carbon::now(), false)->toArray();

        $this->assertSame(10.0, $snapshot['summary']['transactions_trend_percentage']);
    }

    /**
     * Tenants bill in different currencies, so a platform-wide money total would be
     * meaningless no matter how it were labelled.
     */
    public function testItNeverEmitsAPlatformWideMoneyTotal(): void {
        $snapshot = OperatorPlatformSnapshot::fold([
            $this->snapshot(companyId: 1, volumeThisMonth: 742516.0, currency: 'TZS'),
            $this->snapshot(companyId: 2, volumeThisMonth: 91000.0, currency: 'NGN'),
        ], Carbon::now(), false)->toArray();

        $encoded = json_encode($snapshot);
        $this->assertIsString($encoded);
        $this->assertStringNotContainsString('742516', $encoded);
        $this->assertStringNotContainsString('833516', $encoded);
        $this->assertArrayNotHasKey('volume_this_month', $snapshot['summary']);
    }

    public function testItOrdersProviderSeriesByVolume(): void {
        $periods = OperatorPlatformSnapshot::periods();
        $currentPeriod = Carbon::now()->format('Y-m');

        $snapshot = OperatorPlatformSnapshot::fold([
            $this->snapshot(companyId: 1, byProvider: [
                'cash_transaction' => [$currentPeriod => 5],
                'vodacom_transaction' => [$currentPeriod => 50],
            ]),
        ], Carbon::now(), false)->toArray();

        $this->assertSame(
            ['vodacom_transaction', 'cash_transaction'],
            array_keys($snapshot['monthly']['by_provider'])
        );
        $this->assertCount(count($periods), $snapshot['monthly']['by_provider']['vodacom_transaction']);
    }

    public function testItMarksAStaleDocument(): void {
        $snapshot = OperatorPlatformSnapshot::fold([], Carbon::now()->subHours(48), false)->toArray();

        $this->assertTrue($snapshot['stale']);
    }

    public function testItDoesNotMarkAFreshDocumentStale(): void {
        $snapshot = OperatorPlatformSnapshot::fold([], Carbon::now()->subHours(2), false)->toArray();

        $this->assertFalse($snapshot['stale']);
    }

    /** @param array<string, array<string, int>> $byProvider */
    private function snapshot(
        int $companyId,
        int $customers = 0,
        int $meters = 0,
        int $transactionsThisMonth = 0,
        int $transactionsLastMonth = 0,
        float $volumeThisMonth = 0.0,
        ?string $currency = null,
        array $byProvider = [],
    ): OperatorTenantSnapshot {
        return new OperatorTenantSnapshot(
            companyId: $companyId,
            name: 'Tenant '.$companyId,
            email: null,
            phone: null,
            country: null,
            countryCode: null,
            usageType: null,
            registeredAt: Carbon::now()->subYear(),
            lastActiveAt: $transactionsThisMonth > 0 ? Carbon::now() : null,
            customers: $customers,
            newCustomersThisMonth: 0,
            devices: ['meters' => $meters, 'shs' => 0, 'ebikes' => 0],
            metersAssignedToCustomer: 0,
            metersReportingLastSevenDays: null,
            monthlyTransactions: [Carbon::now()->format('Y-m') => $transactionsThisMonth],
            monthlyTransactionsByProvider: $byProvider,
            transactionsThisMonth: $transactionsThisMonth,
            transactionsLastMonth: $transactionsLastMonth,
            volumeThisMonth: $volumeThisMonth,
            currency: $currency,
            plugins: [],
            activity: [],
        );
    }
}
