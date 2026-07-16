<?php

namespace App\Http\Controllers;

use App\Services\ExportServices\AbstractExportService;
use App\Services\ExportServices\SettingsExportService;
use App\Services\MainSettingsService;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Group('Export', 'Export data as Excel, CSV, or JSON.', weight: 11)]
class SettingsExportController extends Controller {
    public function __construct(
        private MainSettingsService $mainSettingsService,
        private SettingsExportService $settingsExportService,
    ) {}

    /**
     * Export settings.
     *
     * Returns the main settings as JSON, or downloads them as an Excel or CSV file.
     *
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    #[QueryParameter('format', description: 'Export format.', type: "'json'|'excel'|'csv'", default: 'json')]
    public function download(Request $request): StreamedResponse|JsonResponse {
        $format = $request->input('format', 'json');

        if ($format === 'excel') {
            return $this->downloadExcel();
        }

        if ($format === 'csv') {
            return $this->downloadCsv();
        }

        return $this->downloadJson();
    }

    public function downloadExcel(): StreamedResponse {
        $settings = $this->mainSettingsService->getAll()->first();
        $this->settingsExportService->setSettingsData($settings);
        $this->settingsExportService->createSpreadSheetFromTemplate($this->settingsExportService->getTemplatePath());
        $this->settingsExportService->setExportingData();
        $this->settingsExportService->writeSettingsData();
        $pathToSpreadSheet = $this->settingsExportService->saveSpreadSheet();

        return Storage::download($pathToSpreadSheet, 'settings_export_'.now()->format('Ymd_His').'.xlsx', ['Content-Type' => AbstractExportService::XLSX_CONTENT_TYPE]);
    }

    public function downloadCsv(): StreamedResponse {
        $settings = $this->mainSettingsService->getAll()->first();
        $this->settingsExportService->setSettingsData($settings);
        $this->settingsExportService->setExportingData();
        $headers = ['Site Title', 'Company Name', 'Currency', 'Country', 'Language', 'VAT Energy', 'VAT Appliance', 'Usage Type', 'SMS Gateway ID', 'Created At', 'Updated At'];
        $csvPath = $this->settingsExportService->saveCsv($headers);

        return Storage::download($csvPath, 'settings_export_'.now()->format('Ymd_His').'.csv', ['Content-Type' => AbstractExportService::CSV_CONTENT_TYPE]);
    }

    public function downloadJson(): JsonResponse {
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
