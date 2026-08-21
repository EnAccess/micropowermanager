<?php

namespace Tests\Unit;

use App\DTO\OperatorTenantSnapshot;
use App\Enums\DeviceType;
use App\Models\Company;
use App\Models\Device;
use App\Models\MpmPlugin;
use App\Models\UsageType;
use App\Services\OperatorTenantMetricsService;
use Database\Factories\Person\PersonFactory;
use Database\Factories\TransactionFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class OperatorTenantMetricsServiceTest extends TestCase {
    use RefreshMultipleDatabases;

    private OperatorTenantMetricsService $operatorTenantMetricsService;
    private Company $company;

    protected function setUp(): void {
        parent::setUp();

        $this->operatorTenantMetricsService = resolve(OperatorTenantMetricsService::class);
        $this->company = Company::query()->findOrFail($this->companyId);
    }

    public function testItCountsOnlyCustomersThatAreNotSoftDeleted(): void {
        $customer = PersonFactory::new()->isCustomer()->create();
        PersonFactory::new()->isCustomer()->create()->delete();
        PersonFactory::new()->create(['is_customer' => 0]);

        $snapshot = $this->collect();

        $this->assertSame(1, $snapshot->customers);
        $this->assertSame(1, $snapshot->newCustomersThisMonth);
        $this->assertNotNull($customer->id);
    }

    public function testItBucketsDevicesByType(): void {
        $person = PersonFactory::new()->isCustomer()->create();
        $this->createDevice(DeviceType::Meter, $person->id);
        $this->createDevice(DeviceType::Meter, null);
        $this->createDevice(DeviceType::SolarHomeSystem, $person->id);
        $this->createDevice(DeviceType::EBike, null);

        $snapshot = $this->collect();

        $this->assertSame(2, $snapshot->devices['meters']);
        $this->assertSame(1, $snapshot->devices['shs']);
        $this->assertSame(1, $snapshot->devices['ebikes']);
        $this->assertSame(4, $snapshot->devicesTotal());
        // Assignment to a customer is the portable definition of a deployed meter.
        $this->assertSame(1, $snapshot->metersAssignedToCustomer);
    }

    public function testItReportsNoMeterReadingsWhenTheTenantHasNoConsumptionRecords(): void {
        $snapshot = $this->collect();

        $this->assertNull($snapshot->metersReportingLastSevenDays);
    }

    public function testItZeroFillsTheTrailingTwelveMonths(): void {
        $snapshot = $this->collect();

        $this->assertCount(12, $snapshot->monthlyTransactions);
        $this->assertSame([0], array_values(array_unique(array_values($snapshot->monthlyTransactions))));
        $this->assertSame(Carbon::now()->format('Y-m'), array_key_last($snapshot->monthlyTransactions));
    }

    public function testItGroupsTransactionsByPeriodAndProvider(): void {
        TransactionFactory::new()->create([
            'original_transaction_type' => 'cash_transaction',
            'original_transaction_id' => 1,
            'amount' => 1000,
        ]);
        TransactionFactory::new()->create([
            'original_transaction_type' => 'vodacom_transaction',
            'original_transaction_id' => 2,
            'amount' => 500,
        ]);

        $snapshot = $this->collect();
        $currentPeriod = Carbon::now()->format('Y-m');

        $this->assertSame(2, $snapshot->transactionsThisMonth);
        $this->assertSame(1500.0, $snapshot->volumeThisMonth);
        $this->assertSame(1, $snapshot->monthlyTransactionsByProvider['cash_transaction'][$currentPeriod]);
        $this->assertSame(1, $snapshot->monthlyTransactionsByProvider['vodacom_transaction'][$currentPeriod]);
        $this->assertNotNull($snapshot->lastActiveAt);
    }

    /**
     * Provider morph aliases only register when their plugin boots, so an alias the
     * running instance knows nothing about must survive untouched.
     */
    public function testItPassesThroughAnUnknownProviderAlias(): void {
        TransactionFactory::new()->create([
            'original_transaction_type' => 'not_a_registered_provider',
            'original_transaction_id' => 3,
        ]);

        $snapshot = $this->collect();

        $this->assertArrayHasKey('not_a_registered_provider', $snapshot->monthlyTransactionsByProvider);
    }

    public function testItExcludesTransactionsOlderThanTheSeriesWindow(): void {
        $transaction = TransactionFactory::new()->create([
            'original_transaction_type' => 'cash_transaction',
            'original_transaction_id' => 4,
        ]);
        DB::connection('tenant')->table('transactions')
            ->where('id', $transaction->id)
            ->update(['created_at' => Carbon::now()->subMonths(18)]);

        $snapshot = $this->collect();

        $this->assertSame(0, $snapshot->transactionsThisMonth);
        $this->assertSame([], $snapshot->monthlyTransactionsByProvider);
        // The window bounds the series, not the freshness stamp.
        $this->assertNotNull($snapshot->lastActiveAt);
    }

    public function testItResolvesCountryFromTheDiallingCode(): void {
        $snapshot = $this->collect();

        $this->assertSame('TZ', $snapshot->countryCode);
        $this->assertSame('Tanzania', $snapshot->country);
    }

    public function testItLeavesCountryUnresolvedForAnUnparseablePhoneNumber(): void {
        $this->company->phone = 'not a phone number';

        $snapshot = $this->collect();

        $this->assertNull($snapshot->countryCode);
        $this->assertNull($snapshot->country);
    }

    public function testItReadsTheTenantsOwnCurrencyAndUsageType(): void {
        DB::connection('tenant')->table('main_settings')->update([
            'currency' => 'TZS',
            'usage_type' => 'mini-grid&shs',
        ]);

        $snapshot = $this->collect();

        $this->assertSame('TZS', $snapshot->currency);
        $this->assertSame('Mini-Grid & Solar Home System', $snapshot->usageType);
    }

    public function testItCapsTheActivityFeed(): void {
        $snapshot = $this->collect();

        $this->assertLessThanOrEqual(
            (int) config('micropowermanager.operator_dashboard.activity_entries'),
            count($snapshot->activity)
        );
    }

    private function collect(): OperatorTenantSnapshot {
        $usageTypeNames = UsageType::query()->pluck('name', 'value')->all();
        $pluginNames = MpmPlugin::query()->pluck('name', 'id')->all();

        return $this->operatorTenantMetricsService->collect($this->company, $pluginNames, $usageTypeNames);
    }

    private function createDevice(DeviceType $deviceType, ?int $personId): void {
        Device::query()->create([
            'person_id' => $personId,
            'device_type' => $deviceType->value,
            'device_id' => random_int(1, 100000),
            'device_serial' => 'SERIAL-'.random_int(1, 100000),
        ]);
    }
}
