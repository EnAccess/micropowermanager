<?php

namespace App\Http\Controllers;

use App\Http\Requests\AppliancePersonImportRequest;
use App\Http\Resources\ImportResource;
use App\Jobs\ImportJob;
use App\Services\ImportServices\AppliancePersonImportItem;
use App\Services\ImportServices\AppliancePersonImportService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

#[Group('Import', 'Import data from a MicroPowerManager JSON export.', weight: 10)]
class AppliancePersonImportController extends Controller {
    private const int ASYNC_THRESHOLD = 50;
    private const int CACHE_TTL_SECONDS = 3600;

    public function __construct(
        private AppliancePersonImportService $appliancePersonImportService,
    ) {}

    /**
     * Import AppliancePerson records.
     *
     * Imports AppliancePerson records (an appliance sold to a customer) from a MicroPowerManager JSON export.
     *
     * Each record is linked to an existing customer (matched by name and surname) and an existing
     * appliance (matched by name) — so run the customer and appliance imports first; rows whose
     * customer or appliance is missing are reported under `failed` without aborting the rest.
     * A row whose `device_serial` already belongs to an existing AppliancePerson is rejected the same way,
     * so re-running an import does not create duplicate records for the same device.
     * `installment` records generate their installment rate schedule on import (`rate_type`
     * defaults to `monthly`, since MPM does not persist the cadence to export); `energy_service`
     * records store `minimum_payable_amount` / `price_per_day` and generate no rates.
     *
     * Imports of 50 or more items are queued for background processing —
     * the response is a 202 with a `job_id` to poll via the import status endpoint.
     */
    public function import(AppliancePersonImportRequest $request): JsonResponse|ImportResource {
        $items = $request->items();

        if (count($items) >= self::ASYNC_THRESHOLD) {
            return $this->dispatchAsync($items, $request);
        }

        return ImportResource::make($this->appliancePersonImportService->import($items));
    }

    /**
     * @param list<AppliancePersonImportItem> $data
     */
    private function dispatchAsync(array $data, AppliancePersonImportRequest $request): JsonResponse {
        $companyId = (int) $request->attributes->get('companyId');
        $jobId = Str::uuid()->toString();

        Cache::put("import:{$companyId}:{$jobId}", [
            'job_id' => $jobId,
            'status' => 'pending',
            'type' => 'appliance_people',
            'total' => count($data),
            'result' => null,
            'error' => null,
            'created_at' => now()->toISOString(),
            'completed_at' => null,
        ], self::CACHE_TTL_SECONDS);

        dispatch(new ImportJob($companyId, $jobId, AppliancePersonImportService::class, $data));

        return response()->json([
            'data' => [
                'job_id' => $jobId,
                'status' => 'pending',
                'message' => 'Import queued for background processing',
            ],
        ], 202);
    }
}
