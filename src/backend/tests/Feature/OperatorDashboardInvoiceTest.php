<?php

namespace Tests\Feature;

use App\Enums\DeviceType;
use App\Models\Company;
use App\Models\Device;
use Database\Factories\Person\PersonFactory;
use Database\Factories\TransactionFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class OperatorDashboardInvoiceTest extends TestCase {
    use RefreshMultipleDatabases;

    private const string USERNAME = 'operator';
    private const string PASSWORD = 'operator-secret';
    private const string BILLING_MONTH = '2026-08';

    protected function setUp(): void {
        parent::setUp();

        config()->set('micropowermanager.operator_dashboard.basic_auth.username', self::USERNAME);
        config()->set('micropowermanager.operator_dashboard.basic_auth.password', self::PASSWORD);
        Cache::flush();
        Carbon::setTestNow('2026-09-15 12:00:00');
    }

    protected function tearDown(): void {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testItCountsUsageAsOfTheEndOfTheBillingMonth(): void {
        $this->createCustomer('2026-07-10');
        $this->createCustomer('2026-08-20');
        $this->createCustomer('2026-09-02');
        $this->createCustomer('2026-07-01', deletedAt: '2026-09-05');
        $this->createCustomer('2026-07-01', deletedAt: '2026-08-10');
        $this->createDevice('2026-08-01');
        $this->createDevice('2026-09-03');
        $this->createTransaction('2026-07-31 23:59:59');
        $this->createTransaction('2026-08-01 00:00:00');
        $this->createTransaction('2026-08-31 23:00:00');
        $this->createTransaction('2026-09-01 00:00:00');

        $response = $this->downloadInvoice(self::BILLING_MONTH);

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $companyName = Company::query()->findOrFail($this->companyId)->name;
        $this->assertStringContainsString(
            'invoice-data_'.str($companyName)->slug().'_2026-08.csv',
            (string) $response->headers->get('Content-Disposition')
        );

        $row = $this->invoiceRow($response);
        $this->assertSame((string) $this->companyId, $row['tenant_id']);
        $this->assertSame(self::BILLING_MONTH, $row['billing_month']);
        $this->assertSame('3', $row['customers']);
        $this->assertSame('1', $row['devices']);
        $this->assertSame('2', $row['transactions']);
        $this->assertSame('Free', $row['tier']);
        $this->assertSame('no', $row['above_free_tier']);
    }

    public function testItPlacesUsageInTheFirstTierThatCoversEveryLimit(): void {
        config()->set('micropowermanager.operator_dashboard.billing_tiers', [
            ['name' => 'Free', 'customers' => 1, 'devices' => 1, 'transactions' => 1],
            ['name' => 'Growth', 'customers' => 5, 'devices' => 5, 'transactions' => 5],
        ]);
        $this->createCustomer('2026-08-02');
        $this->createTransaction('2026-08-03 10:00:00');

        $this->assertSame('Free', $this->invoiceRow($this->downloadInvoice(self::BILLING_MONTH))['tier']);

        $this->createTransaction('2026-08-04 10:00:00');
        $row = $this->invoiceRow($this->downloadInvoice(self::BILLING_MONTH));

        $this->assertSame('Growth', $row['tier']);
        $this->assertSame('yes', $row['above_free_tier']);
    }

    public function testItBillsUsageAboveEveryTierAsCustom(): void {
        config()->set('micropowermanager.operator_dashboard.billing_tiers', [
            ['name' => 'Free', 'customers' => 0, 'devices' => 0, 'transactions' => 0],
        ]);
        $this->createCustomer('2026-08-02');

        $row = $this->invoiceRow($this->downloadInvoice(self::BILLING_MONTH));

        $this->assertSame('Custom', $row['tier']);
        $this->assertSame('yes', $row['above_free_tier']);
    }

    public function testItRejectsAMalformedOrFutureMonth(): void {
        foreach (['2026-13', 'august', '2026-10'] as $month) {
            $this->downloadInvoice($month)->assertStatus(422)->assertJsonValidationErrors('month');
        }

        $this->getJson('/api/operator/dashboard/tenants/'.$this->companyId.'/invoice', $this->authorization())
            ->assertStatus(422);
    }

    public function testItReturnsNotFoundForAnUnknownTenant(): void {
        $this->getJson(
            '/api/operator/dashboard/tenants/987654/invoice?month='.self::BILLING_MONTH,
            $this->authorization()
        )->assertStatus(404);
    }

    public function testItRequiresOperatorCredentials(): void {
        $this->getJson('/api/operator/dashboard/tenants/'.$this->companyId.'/invoice?month='.self::BILLING_MONTH)
            ->assertStatus(401);
    }

    private function downloadInvoice(string $month): TestResponse {
        return $this->getJson(
            '/api/operator/dashboard/tenants/'.$this->companyId.'/invoice?month='.$month,
            $this->authorization()
        );
    }

    /** @return array<string, string> */
    private function invoiceRow(TestResponse $response): array {
        $lines = array_values(array_filter(explode("\n", $response->streamedContent())));
        $this->assertCount(2, $lines);

        return array_combine(str_getcsv($lines[0], escape: '\\'), str_getcsv($lines[1], escape: '\\'));
    }

    private function createCustomer(string $createdAt, ?string $deletedAt = null): void {
        PersonFactory::new()->isCustomer()->create([
            'created_at' => $createdAt,
            'deleted_at' => $deletedAt,
        ]);
    }

    private function createDevice(string $createdAt): void {
        Device::query()->create([
            'person_id' => null,
            'device_type' => DeviceType::Meter->value,
            'device_id' => random_int(1, 100000),
            'device_serial' => 'SERIAL-'.random_int(1, 100000),
            'created_at' => $createdAt,
        ]);
    }

    private function createTransaction(string $createdAt): void {
        TransactionFactory::new()->create([
            'original_transaction_type' => 'cash_transaction',
            'original_transaction_id' => random_int(1, 100000),
            'created_at' => $createdAt,
        ]);
    }

    /** @return array<string, string> */
    private function authorization(): array {
        return ['Authorization' => 'Basic '.base64_encode(self::USERNAME.':'.self::PASSWORD)];
    }
}
