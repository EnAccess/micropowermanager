<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\MainSettingsService;
use App\Services\ProtectedPageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProtectedPageController {
    public function __construct(private ProtectedPageService $protectedPageService, private MainSettingsService $mainSettingsService) {}

    public function index(): ApiResource {
        return ApiResource::make($this->protectedPageService->getAll());
    }

    public function compareProtectedPagePassword(Request $request): JsonResponse {
        $mainSettingsId = $request->input('id');
        $password = $request->input('password');
        $mainSettings = $this->mainSettingsService->getById($mainSettingsId);
        $result = $this->protectedPageService->compareProtectedPagePassword($mainSettings, $password);

        return response()->json([
            'result' => $result,
        ], 200);
    }
}
