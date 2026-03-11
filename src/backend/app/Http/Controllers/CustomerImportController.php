<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerImportRequest;
use App\Http\Resources\ApiResource;
use App\Jobs\ImportJob;
use App\Services\ImportServices\CustomerImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CustomerImportController extends Controller {
    private const ASYNC_THRESHOLD = 50;
    private const CACHE_TTL_SECONDS = 3600;

    public function __construct(
        private CustomerImportService $customerImportService,
    ) {}

    public function import(CustomerImportRequest $request): JsonResponse|ApiResource {
        $data = $request->input('data');

        if (isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }

        if (count($data) >= self::ASYNC_THRESHOLD) {
            return $this->dispatchAsync($data, $request);
        }

        $result = $this->customerImportService->import($data);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'errors' => $result['errors'],
            ], 422);
        }

        return ApiResource::make($result);
    }

    /**
     * @param array<int, array<string, mixed>> $data
     */
    private function dispatchAsync(array $data, CustomerImportRequest $request): JsonResponse {
        $companyId = (int) $request->attributes->get('companyId');
        $jobId = Str::uuid()->toString();

        Cache::put("import:{$companyId}:{$jobId}", [
            'job_id' => $jobId,
            'status' => 'pending',
            'type' => 'customers',
            'total' => count($data),
            'result' => null,
            'error' => null,
            'created_at' => now()->toISOString(),
            'completed_at' => null,
        ], self::CACHE_TTL_SECONDS);

        dispatch(new ImportJob($companyId, $jobId, CustomerImportService::class, $data));

        return response()->json([
            'data' => [
                'job_id' => $jobId,
                'status' => 'pending',
                'message' => 'Import queued for background processing',
            ],
        ], 202);
    }
}
