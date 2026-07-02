<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClusterImportRequest;
use App\Http\Resources\ImportResource;
use App\Jobs\ImportJob;
use App\Services\ImportServices\ClusterImportService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

#[Group('Import', 'Import data from a MicroPowerManager JSON export.', weight: 10)]
class ClusterImportController extends Controller {
    private const int ASYNC_THRESHOLD = 50;
    private const int CACHE_TTL_SECONDS = 3600;

    public function __construct(
        private ClusterImportService $clusterImportService,
    ) {}

    /**
     * Import clusters.
     *
     * Imports of 50 or more items are queued for background processing —
     * the response is a 202 with a `job_id` to poll via the import status endpoint.
     */
    public function import(ClusterImportRequest $request): JsonResponse|ImportResource {
        $data = $request->validated('data');

        if (count($data) >= self::ASYNC_THRESHOLD) {
            return $this->dispatchAsync($data, $request);
        }

        return ImportResource::make($this->clusterImportService->import($data));
    }

    /**
     * @param array<int, array<string, mixed>> $data
     */
    private function dispatchAsync(array $data, ClusterImportRequest $request): JsonResponse {
        $companyId = (int) $request->attributes->get('companyId');
        $jobId = Str::uuid()->toString();

        Cache::put("import:{$companyId}:{$jobId}", [
            'job_id' => $jobId,
            'status' => 'pending',
            'type' => 'clusters',
            'total' => count($data),
            'result' => null,
            'error' => null,
            'created_at' => now()->toISOString(),
            'completed_at' => null,
        ], self::CACHE_TTL_SECONDS);

        dispatch(new ImportJob($companyId, $jobId, ClusterImportService::class, $data));

        return response()->json([
            'data' => [
                'job_id' => $jobId,
                'status' => 'pending',
                'message' => 'Import queued for background processing',
            ],
        ], 202);
    }
}
