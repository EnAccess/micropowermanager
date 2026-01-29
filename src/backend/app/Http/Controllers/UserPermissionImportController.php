<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserPermissionImportRequest;
use App\Http\Resources\ApiResource;
use App\Services\ImportServices\UserPermissionImportService;
use Illuminate\Http\JsonResponse;

class UserPermissionImportController extends Controller {
    public function __construct(
        private UserPermissionImportService $userPermissionImportService,
    ) {}

    public function import(UserPermissionImportRequest $request): JsonResponse|ApiResource {
        $data = $request->input('data');

        // Handle export format: data might be wrapped in 'data' key
        if (isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        }

        $result = $this->userPermissionImportService->import($data);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'errors' => $result['errors'],
            ], 422);
        }

        return ApiResource::make($result);
    }
}
