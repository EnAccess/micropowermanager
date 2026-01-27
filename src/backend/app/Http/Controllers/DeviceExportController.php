<?php

namespace App\Http\Controllers;

use App\Services\DeviceService;
use App\Services\ExportServices\DeviceExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeviceExportController extends Controller {
    public function __construct(
        private DeviceService $deviceService,
        private DeviceExportService $deviceExportService,
    ) {}

    public function download(Request $request): StreamedResponse|JsonResponse {
        $format = $request->get('format', 'excel');

        if ($format === 'csv') {
            return $this->downloadCsv($request);
        }

        if ($format === 'json') {
            return $this->downloadJson($request);
        }

        return $this->downloadExcel($request);
    }

    public function downloadExcel(Request $request): StreamedResponse {
        $miniGridName = $request->get('miniGrid');
        $villageName = $request->get('village');
        $deviceType = $request->get('deviceType');
        $manufacturerName = $request->get('manufacturer');

        $devices = $this->deviceService->getAllForExport($miniGridName, $villageName, $deviceType, $manufacturerName);
        $this->deviceExportService->createSpreadSheetFromTemplate($this->deviceExportService->getTemplatePath());
        $this->deviceExportService->setDeviceData($devices);
        $this->deviceExportService->setExportingData();
        $this->deviceExportService->writeDeviceData();
        $pathToSpreadSheet = $this->deviceExportService->saveSpreadSheet();

        return Storage::download($pathToSpreadSheet, 'device_export_'.now()->format('Ymd_His').'.xlsx');
    }

    public function downloadCsv(Request $request): StreamedResponse {
        $miniGridName = $request->get('miniGrid');
        $villageName = $request->get('village');
        $deviceType = $request->get('deviceType');
        $manufacturerName = $request->get('manufacturer');

        $devices = $this->deviceService->getAllForExport($miniGridName, $villageName, $deviceType, $manufacturerName);

        $this->deviceExportService->setDeviceData($devices);
        $this->deviceExportService->setExportingData();
        $headers = ['Device Serial', 'Device Type', 'Customer', 'Address', 'Manufacturer', 'Created At', 'Updated At'];
        $csvPath = $this->deviceExportService->saveCsv($headers);

        return Storage::download($csvPath, 'device_export_'.now()->format('Ymd_His').'.csv');
    }

    public function downloadJson(Request $request): JsonResponse {
        $miniGridName = $request->get('miniGrid');
        $villageName = $request->get('village');
        $deviceType = $request->get('deviceType');
        $manufacturerName = $request->get('manufacturer');

        $devices = $this->deviceService->getAllForExport($miniGridName, $villageName, $deviceType, $manufacturerName);

        $this->deviceExportService->setDeviceData($devices);
        $jsonData = $this->deviceExportService->exportDataToArray();

        return response()->json([
            'data' => $jsonData,
            'meta' => [
                'total' => count($jsonData),
                'filters' => [
                    'mini_grid' => $miniGridName,
                    'village' => $villageName,
                    'device_type' => $deviceType,
                    'manufacturer' => $manufacturerName,
                ],
                'exported_at' => now()->toISOString(),
            ],
        ]);
    }
}
