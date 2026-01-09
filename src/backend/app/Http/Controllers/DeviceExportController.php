<?php

namespace App\Http\Controllers;

use App\Services\DeviceService;
use App\Services\ExportServices\DeviceExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class DeviceExportController extends Controller {
    public function __construct(
        private DeviceService $deviceService,
        private DeviceExportService $deviceExportService,
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
        $devices = $this->deviceService->getAllForExport();
        $this->deviceExportService->createSpreadSheetFromTemplate($this->deviceExportService->getTemplatePath());
        $this->deviceExportService->setDeviceData($devices);
        $this->deviceExportService->setExportingData();
        $this->deviceExportService->writeDeviceData();
        $pathToSpreadSheet = $this->deviceExportService->saveSpreadSheet();

        $path = Storage::path($pathToSpreadSheet);

        return response()->download($path, 'device_export_'.now()->format('Ymd_His').'.xlsx');
    }

    public function downloadCsv(Request $request): BinaryFileResponse {
        $devices = $this->deviceService->getAllForExport();

        $this->deviceExportService->setDeviceData($devices);
        $this->deviceExportService->setExportingData();
        $headers = ['Device Serial', 'Device Type', 'Customer', 'Address', 'Manufacturer', 'Created At', 'Updated At'];
        $csvPath = $this->deviceExportService->saveCsv($headers);

        $path = Storage::path($csvPath);

        return response()->download($path, 'device_export_'.now()->format('Ymd_His').'.csv');
    }

    public function downloadJson(Request $request): JsonResponse {
        $devices = $this->deviceService->getAllForExport();

        $this->deviceExportService->setDeviceData($devices);
        $jsonData = $this->deviceExportService->exportDataToArray();

        return response()->json([
            'data' => $jsonData,
            'meta' => [
                'total' => count($jsonData),
                'exported_at' => now()->toISOString(),
            ],
        ]);
    }
}
