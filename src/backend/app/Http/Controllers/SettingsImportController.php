<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingsImportRequest;
use App\Http\Resources\ImportResource;
use App\Services\ImportServices\SettingsImportService;

class SettingsImportController extends Controller {
    public function __construct(
        private SettingsImportService $settingsImportService,
    ) {}

    public function import(SettingsImportRequest $request): ImportResource {
        $data = $request->input('data');

        return ImportResource::make($this->settingsImportService->import($data));
    }
}
