<?php

namespace App\Http\Controllers;

use App\Services\ApplianceService;
use App\Services\ExportServices\AbstractExportService;
use App\Services\ExportServices\ApplianceExportService;
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
class ApplianceExportController extends Controller {
    public function __construct(
        private ApplianceService $applianceService,
        private ApplianceExportService $applianceExportService,
        private MainSettingsService $mainSettingsService,
    ) {}

    /**
     * Export appliances.
     *
     * Downloads appliances as an Excel or CSV file, or returns them as JSON.
     *
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    #[QueryParameter('format', description: 'Export format.', type: "'excel'|'csv'|'json'", default: 'excel')]
    public function download(Request $request): StreamedResponse|JsonResponse {
        $format = $request->input('format', 'excel');

        if ($format === 'csv') {
            return $this->downloadCsv();
        }

        if ($format === 'json') {
            return $this->downloadJson();
        }

        return $this->downloadExcel();
    }

    public function downloadExcel(): StreamedResponse {
        $mainSettings = $this->mainSettingsService->getAll()->first();
        $this->applianceExportService->setCurrency($mainSettings->currency);

        $appliances = $this->applianceService->getAllForExport();
        $this->applianceExportService->createSpreadSheetFromTemplate($this->applianceExportService->getTemplatePath());
        $this->applianceExportService->setApplianceData($appliances);
        $this->applianceExportService->setExportingData();
        $this->applianceExportService->writeApplianceData();
        $pathToSpreadSheet = $this->applianceExportService->saveSpreadSheet();

        return Storage::download($pathToSpreadSheet, 'appliance_export_'.now()->format('Ymd_His').'.xlsx', ['Content-Type' => AbstractExportService::XLSX_CONTENT_TYPE]);
    }

    public function downloadCsv(): StreamedResponse {
        $mainSettings = $this->mainSettingsService->getAll()->first();
        $this->applianceExportService->setCurrency($mainSettings->currency);

        $appliances = $this->applianceService->getAllForExport();

        $this->applianceExportService->setApplianceData($appliances);
        $this->applianceExportService->setExportingData();
        $headers = ['Appliance Name', 'Appliance Type', 'Price', 'Total Sold', 'Total Rates', 'Created At', 'Updated At'];
        $csvPath = $this->applianceExportService->saveCsv($headers);

        return Storage::download($csvPath, 'appliance_export_'.now()->format('Ymd_His').'.csv', ['Content-Type' => AbstractExportService::CSV_CONTENT_TYPE]);
    }

    public function downloadJson(): JsonResponse {
        $mainSettings = $this->mainSettingsService->getAll()->first();
        $this->applianceExportService->setCurrency($mainSettings->currency);

        $appliances = $this->applianceService->getAllForExport();

        $this->applianceExportService->setApplianceData($appliances);
        $jsonData = $this->applianceExportService->exportDataToArray();

        return response()->json([
            'data' => $jsonData,
            'meta' => [
                'total' => count($jsonData),
                'currency' => $mainSettings->currency,
                'exported_at' => now()->toISOString(),
            ],
        ]);
    }
}
