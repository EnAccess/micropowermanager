<?php

namespace App\Http\Controllers;

use App\Services\ExportServices\SettingsExportService;
use App\Services\MainSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SettingsExportController extends Controller {
    public function __construct(
        private MainSettingsService $mainSettingsService,
        private SettingsExportService $settingsExportService,
    ) {}

    public function download(Request $request): BinaryFileResponse|JsonResponse {
        $format = $request->get('format', 'json');

        if ($format === 'excel') {
            return $this->downloadExcel($request);
        }

        if ($format === 'csv') {
            return $this->downloadCsv($request);
        }

        return $this->downloadJson($request);
    }

    public function downloadExcel(Request $request): BinaryFileResponse {
        $settings = $this->mainSettingsService->getAll()->first();
        $this->settingsExportService->setSettingsData($settings);
        $this->settingsExportService->createSpreadSheetFromTemplate($this->settingsExportService->getTemplatePath());
        $this->settingsExportService->setExportingData();
        $this->settingsExportService->writeSettingsData();
        $pathToSpreadSheet = $this->settingsExportService->saveSpreadSheet();

        $path = Storage::path($pathToSpreadSheet);

        return response()->download($path, 'settings_export_'.now()->format('Ymd_His').'.xlsx');
    }

    public function downloadCsv(Request $request): BinaryFileResponse {
        $settings = $this->mainSettingsService->getAll()->first();
        $this->settingsExportService->setSettingsData($settings);
        $this->settingsExportService->setExportingData();
        $headers = ['Site Title', 'Company Name', 'Currency', 'Country', 'Language', 'VAT Energy', 'VAT Appliance', 'Usage Type', 'SMS Gateway ID', 'Created At', 'Updated At'];
        $csvPath = $this->settingsExportService->saveCsv($headers);

        $path = Storage::path($csvPath);

        return response()->download($path, 'settings_export_'.now()->format('Ymd_His').'.csv');
    }

    public function downloadJson(Request $request): JsonResponse {
        $settings = $this->mainSettingsService->getAll()->first();
        $this->settingsExportService->setSettingsData($settings);
        $jsonData = $this->settingsExportService->exportDataToArray();

        return response()->json([
            'data' => $jsonData,
            'meta' => [
                'exported_at' => now()->toISOString(),
            ],
        ]);
    }
}
