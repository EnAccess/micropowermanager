<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\ImportJob;
use App\Services\ImportServices\DeviceImportService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * DeviceImportService::import() now throws ImportFailedException instead of returning
 * a success=false array. ImportJob::executeJob() is the one caller that doesn't go through
 * a FormRequest first, so it's the only place this contract change can actually be observed.
 */
class ImportJobTest extends TestCase {
    public function testStoresCompletedStatusAndResultOnSuccess(): void {
        $jobId = Str::uuid()->toString();
        $job = new ImportJob($this->companyId, $jobId, DeviceImportService::class, [
            ['device_info' => ['serial_number' => 'IMPORT-JOB-TEST-OK', 'manufacturer' => 'Test Manufacturer']],
        ]);

        $job->executeJob();

        $cached = Cache::get("import:{$this->companyId}:{$jobId}");

        $this->assertSame('completed', $cached['status']);
        $this->assertTrue($cached['result']['success']);
        $this->assertSame(1, $cached['result']['imported_count']);
    }

    public function testStoresFailedStatusAndErrorsWhenServiceThrowsImportFailedException(): void {
        $jobId = Str::uuid()->toString();
        $job = new ImportJob($this->companyId, $jobId, DeviceImportService::class, [
            ['device_info' => []],
        ]);

        $job->executeJob();

        $cached = Cache::get("import:{$this->companyId}:{$jobId}");

        $this->assertSame('failed', $cached['status']);
        $this->assertFalse($cached['result']['success']);
        $this->assertArrayHasKey('device_0.device_info.serial_number', $cached['result']['errors']);
    }
}
