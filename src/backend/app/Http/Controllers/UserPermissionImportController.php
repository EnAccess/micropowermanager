<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserPermissionImportRequest;
use App\Http\Resources\ApiResource;
use App\Jobs\ImportJob;
use App\Services\ImportServices\UserPermissionImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class UserPermissionImportController extends Controller {
    private const ASYNC_THRESHOLD = 50;
    private const CACHE_TTL_SECONDS = 3600;

    public function __construct(
        private UserPermissionImportService $userPermissionImportService,
    ) {}

    public function import(UserPermissionImportRequest $request): JsonResponse|ApiResource {
        $data = $request->input('data');

        // Handle export format: data might be wrapped in 'data' key
        if (isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }

        if (count($data) >= self::ASYNC_THRESHOLD) {
            return $this->dispatchAsync($data, $request);
        }

        $result = $this->userPermissionImportService->import($data);

        if (!$result['success'] && isset($result['errors'])) {
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
    private function dispatchAsync(array $data, UserPermissionImportRequest $request): JsonResponse {
        $companyId = (int) $request->attributes->get('companyId');
        $jobId = Str::uuid()->toString();

        Cache::put("import:{$companyId}:{$jobId}", [
            'job_id' => $jobId,
            'status' => 'pending',
            'type' => 'user-permissions',
            'total' => count($data),
            'result' => null,
            'error' => null,
            'created_at' => now()->toISOString(),
            'completed_at' => null,
        ], self::CACHE_TTL_SECONDS);

        dispatch(new ImportJob($companyId, $jobId, UserPermissionImportService::class, $data));

        return response()->json([
            'data' => [
                'job_id' => $jobId,
                'status' => 'pending',
                'message' => 'Import queued for background processing',
            ],
        ], 202);
    }
}
