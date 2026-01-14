<?php

namespace App\Http\Controllers;

use App\Services\ApplianceService;
use App\Services\ExportServices\ApplianceExportService;
use App\Services\MainSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ApplianceExportController extends Controller {
    public function __construct(
        private ApplianceService $applianceService,
        private ApplianceExportService $applianceExportService,
        private MainSettingsService $mainSettingsService,
    ) {}

    public function download(Request $request): BinaryFileResponse|JsonResponse {
        $format = $request->get('format', 'excel');

        if ($format === 'csv') {
            return $this->downloadCsv($request);
        }

        if ($format === 'json') {
            return $this->downloadJson($request);
        }

        return $this->downloadExcel($request);
    }

    public function downloadExcel(Request $request): BinaryFileResponse {
        $mainSettings = $this->mainSettingsService->getAll()->first();
        $this->applianceExportService->setCurrency($mainSettings->currency);

        $appliances = $this->applianceService->getAllForExport();
        $this->applianceExportService->createSpreadSheetFromTemplate($this->applianceExportService->getTemplatePath());
        $this->applianceExportService->setApplianceData($appliances);
        $this->applianceExportService->setExportingData();
        $this->applianceExportService->writeApplianceData();
        $pathToSpreadSheet = $this->applianceExportService->saveSpreadSheet();

        $path = Storage::path($pathToSpreadSheet);

        return response()->download($path, 'appliance_export_'.now()->format('Ymd_His').'.xlsx');
    }

    public function downloadCsv(Request $request): BinaryFileResponse {
        $mainSettings = $this->mainSettingsService->getAll()->first();
        $this->applianceExportService->setCurrency($mainSettings->currency);

        $appliances = $this->applianceService->getAllForExport();

        $this->applianceExportService->setApplianceData($appliances);
        $this->applianceExportService->setExportingData();
        $headers = ['Appliance Name', 'Appliance Type', 'Price', 'Total Sold', 'Total Rates', 'Created At', 'Updated At'];
        $csvPath = $this->applianceExportService->saveCsv($headers);

        $path = Storage::path($csvPath);

        return response()->download($path, 'appliance_export_'.now()->format('Ymd_His').'.csv');
    }

    public function downloadJson(Request $request): JsonResponse {
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
