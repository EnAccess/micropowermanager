<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingsImportRequest;
use App\Http\Resources\ApiResource;
use App\Services\ImportServices\SettingsImportService;
use Illuminate\Http\JsonResponse;

class SettingsImportController extends Controller {
    public function __construct(
        private SettingsImportService $settingsImportService,
    ) {}

    public function import(SettingsImportRequest $request): JsonResponse|ApiResource {
        $data = $request->input('data');

        $result = $this->settingsImportService->import($data);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'errors' => $result['errors'],
            ], 422);
        }

        return ApiResource::make($result);
    }
}
