<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingsImportRequest;
use App\Http\Resources\ImportResource;
use App\Services\ImportServices\SettingsImportService;
use Dedoc\Scramble\Attributes\Group;

#[Group('Import', 'Import data from a MicroPowerManager JSON export.', weight: 10)]
class SettingsImportController extends Controller {
    public function __construct(
        private SettingsImportService $settingsImportService,
    ) {}

    /**
     * Import main settings.
     *
     * Replaces the tenant's main settings with the single settings object in `data`.
     */
    public function import(SettingsImportRequest $request): ImportResource {
        $data = $request->validated('data');

        return ImportResource::make($this->settingsImportService->import($data));
    }
}
