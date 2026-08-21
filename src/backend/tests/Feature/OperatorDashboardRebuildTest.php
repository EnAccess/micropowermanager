<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyDatabase;
use App\Services\DatabaseProxyManagerService;
use App\Services\OperatorDashboardService;
use Database\Factories\Person\PersonFactory;
use Database\Factories\TransactionFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;
use Tests\TestCompany;

class OperatorDashboardRebuildTest extends TestCase {
    use RefreshMultipleDatabases;

    private OperatorDashboardService $operatorDashboardService;

    protected function setUp(): void {
        parent::setUp();

        Cache::flush();
        $this->operatorDashboardService = resolve(OperatorDashboardService::class);
    }

    public function testItCachesASnapshotPerTenantAndStampsTheIndex(): void {
        $this->registerAdditionalCompanies(2);
        $tenantCount = $this->tenantCount();

        $this->artisan('operator-dashboard:rebuild', ['--sync' => true])->assertExitCode(0);

        $snapshot = $this->operatorDashboardService->platformSnapshot()->toArray();

        $this->assertSame($tenantCount, $snapshot['summary']['tenants_total']);
        $this->assertCount($tenantCount, $snapshot['tenants']);
        $this->assertNotNull($snapshot['generated_at']);
        $this->assertFalse($snapshot['stale']);
    }

    /**
     * Every tenant here reads the same test schema, so the interesting assertion is
     * that per-tenant figures are collected and summed rather than counted once.
     */
    public function testItSumsTenantFiguresIntoThePlatformRollUp(): void {
        PersonFactory::new()->isCustomer()->create();
        $this->registerAdditionalCompanies(1);
        $tenantCount = $this->tenantCount();

        $this->operatorDashboardService->rebuild();
        $snapshot = $this->operatorDashboardService->platformSnapshot()->toArray();

        $customersPerTenant = $snapshot['tenants'][0]['customers'];
        $this->assertGreaterThan(0, $customersPerTenant);
        $this->assertSame($customersPerTenant * $tenantCount, $snapshot['summary']['customers_total']);
    }

    public function testItCountsATenantWithTransactionsThisMonthAsActive(): void {
        TransactionFactory::new()->create([
            'original_transaction_type' => 'cash_transaction',
            'original_transaction_id' => 1,
        ]);
        $tenantCount = $this->tenantCount();

        $this->operatorDashboardService->rebuild();
        $snapshot = $this->operatorDashboardService->platformSnapshot()->toArray();

        $this->assertSame($tenantCount, $snapshot['summary']['tenants_active']);
        $this->assertSame($tenantCount, $snapshot['summary']['transactions_this_month']);
        $this->assertSame('active', $snapshot['tenants'][0]['health']);
        $this->assertSame(
            ['cash_transaction'],
            array_keys($snapshot['monthly']['by_provider'])
        );
    }

    public function testItResolvesTenantMetadataWithoutASchemaChange(): void {
        $this->operatorDashboardService->rebuild();

        $tenant = $this->operatorDashboardService->tenantSnapshot($this->companyId);

        // The test company's phone is a Tanzanian number.
        $this->assertSame('TZ', $tenant->countryCode);
        $this->assertSame('Tanzania', $tenant->country);
        $this->assertNotNull($tenant->usageType);
        $this->assertNotNull($tenant->currency);
    }

    public function testItLeavesTheCentralConnectionUsableAfterWalkingTenants(): void {
        $companyCountBeforeRebuild = Company::query()->count();

        $this->operatorDashboardService->rebuild();

        // A tenant loop rebinds the tenant connection per company; central models
        // must be unaffected by that.
        $this->assertSame($companyCountBeforeRebuild, Company::query()->count());
        $this->assertGreaterThan(0, $companyCountBeforeRebuild);
    }

    public function testItSkipsATenantWhoseDatabaseCannotBeRead(): void {
        $failures = [];

        resolve(DatabaseProxyManagerService::class)->eachCompany(
            function (int $companyId): void {
                throw new \RuntimeException('unreachable database for '.$companyId);
            },
            function (int $companyId, \Throwable $throwable) use (&$failures): void {
                $failures[] = $companyId;
            }
        );

        $this->assertCount($this->tenantCount(), $failures);
        $this->assertContains($this->companyId, $failures);
    }

    public function testItAdvancesTheFreshnessStampOnASingleTenantRebuild(): void {
        $this->operatorDashboardService->rebuild();
        $firstGeneratedAt = $this->operatorDashboardService->generatedAt();

        Carbon::setTestNow(Carbon::now()->addMinutes(5));
        $this->operatorDashboardService->rebuild($this->companyId);
        $secondGeneratedAt = $this->operatorDashboardService->generatedAt();
        Carbon::setTestNow();

        $this->assertNotNull($firstGeneratedAt);
        $this->assertNotNull($secondGeneratedAt);
        $this->assertTrue($secondGeneratedAt->greaterThan($firstGeneratedAt));
    }

    /**
     * The shared test harness creates its tenant company through a path that issues
     * DDL, which implicitly commits the wrapping transaction, so central company
     * rows survive between tests. Counts here are therefore always relative.
     */
    private function tenantCount(): int {
        return CompanyDatabase::query()->count();
    }

    private function registerAdditionalCompanies(int $count): void {
        for ($index = 0; $index < $count; ++$index) {
            $company = Company::query()->create([
                'name' => 'Extra Company '.$index,
                'address' => 'Sample Address',
                'phone' => '+2348000000'.$index,
                'country_id' => -1,
                'email' => 'extra'.$index.'@example.com',
            ]);

            CompanyDatabase::query()->create([
                'company_id' => $company->id,
                'database_name' => TestCompany::TEST_COMPANY_DATABASE_NAME,
            ]);
        }
    }
}
