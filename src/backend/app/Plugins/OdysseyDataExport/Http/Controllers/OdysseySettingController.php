<?php

namespace App\Plugins\OdysseyDataExport\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Plugins\OdysseyDataExport\Http\Requests\OdysseySettingRequest;
use App\Plugins\OdysseyDataExport\Models\OdysseyFinancingProgram;
use App\Plugins\OdysseyDataExport\Services\OdysseySettingService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Plugins / Odyssey Data Export', 'API endpoints for exporting payment data to Odyssey')]
class OdysseySettingController extends Controller {
    public function __construct(
        private OdysseySettingService $settingService,
    ) {}

    public function show(): JsonResponse {
        return response()->json(['data' => $this->settingService->getSettings()]);
    }

    public function update(OdysseySettingRequest $request): JsonResponse {
        $financingProgram = $request->enum('financing_id', OdysseyFinancingProgram::class);

        return response()->json(['data' => $this->settingService->updateFinancingProgram($financingProgram)]);
    }
}
