<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Exceptions\ImportFailedException;
use App\Jobs\ImportJob;
use App\Services\ImportServices\DeviceImportItem;
use App\Services\ImportServices\DeviceImportService;
use App\Services\ImportServices\DeviceInfoItem;
use App\Services\ImportServices\ImportResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class ImportJobTest extends TestCase {
    public function testStoresCompletedStatusAndResultOnSuccess(): void {
        $jobId = Str::uuid()->toString();
        $job = new ImportJob($this->companyId, $jobId, DeviceImportService::class, [
            $this->deviceItem('IMPORT-JOB-TEST-OK', manufacturer: 'Test Manufacturer'),
        ]);

        $job->executeJob();

        $cached = Cache::get("import:{$this->companyId}:{$jobId}");

        $this->assertSame('completed', $cached['status']);
        $this->assertTrue($cached['result']['success']);
        $this->assertSame(1, $cached['result']['imported_count']);
    }

    public function testStoresCompletedStatusWithFailureDetailsWhenAllItemsFail(): void {
        $jobId = Str::uuid()->toString();
        // A meter without a manufacturer currently fails on the NOT NULL meters.manufacturer_id
        // column — if a default-manufacturer fallback gets added, pick a new failure trigger here.
        $job = new ImportJob($this->companyId, $jobId, DeviceImportService::class, [
            $this->deviceItem('IMPORT-JOB-TEST-FAIL', manufacturer: null),
        ]);

        $job->executeJob();

        $cached = Cache::get("import:{$this->companyId}:{$jobId}");

        $this->assertSame('completed', $cached['status']);
        $this->assertFalse($cached['result']['success']);
        $this->assertSame(1, $cached['result']['failed_count']);
    }

    public function testStoresFailedStatusWhenServiceThrowsImportFailedException(): void {
        $this->app->bind(DeviceImportService::class, fn () => new class extends DeviceImportService {
            public function import(array $data): ImportResult {
                throw new ImportFailedException(['transaction' => 'Failed to import devices: boom']);
            }
        });

        $jobId = Str::uuid()->toString();
        $job = new ImportJob($this->companyId, $jobId, DeviceImportService::class, [
            $this->deviceItem('IRRELEVANT', manufacturer: 'Test Manufacturer'),
        ]);

        $job->executeJob();

        $cached = Cache::get("import:{$this->companyId}:{$jobId}");

        $this->assertSame('failed', $cached['status']);
        $this->assertFalse($cached['result']['success']);
        $this->assertArrayHasKey('transaction', $cached['result']['errors']);
    }

    private function deviceItem(string $serialNumber, ?string $manufacturer): DeviceImportItem {
        return new DeviceImportItem(
            customer: null,
            deviceInfo: new DeviceInfoItem(
                serialNumber: $serialNumber,
                type: 'meter',
                manufacturer: $manufacturer,
                meterType: null,
                connectionType: null,
                connectionGroup: null,
                tariff: null,
                appliance: null,
            ),
            geoJson: null,
        );
    }
}
