<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeviceExportRequest;
use App\Services\DeviceService;
use App\Services\ExportServices\AbstractExportService;
use App\Services\ExportServices\DeviceExportService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Group('Export', 'Export data as Excel, CSV, or JSON.', weight: 11)]
class DeviceExportController extends Controller {
    public function __construct(
        private DeviceService $deviceService,
        private DeviceExportService $deviceExportService,
    ) {}

    /**
     * Export devices.
     *
     * Downloads devices as an Excel or CSV file, or returns them as JSON.
     *
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    public function download(DeviceExportRequest $request): StreamedResponse|JsonResponse {
        $format = $request->input('format', 'excel');

        if ($format === 'csv') {
            return $this->downloadCsv($request);
        }

        if ($format === 'json') {
            return $this->downloadJson($request);
        }

        return $this->downloadExcel($request);
    }

    public function downloadExcel(DeviceExportRequest $request): StreamedResponse {
        $miniGridName = $request->input('miniGrid');
        $villageName = $request->input('village');
        $deviceType = $request->input('deviceType');
        $manufacturerName = $request->input('manufacturer');

        $devices = $this->deviceService->getAllForExport($miniGridName, $villageName, $deviceType, $manufacturerName);
        $this->deviceExportService->createSpreadSheetFromTemplate($this->deviceExportService->getTemplatePath());
        $this->deviceExportService->setDeviceData($devices);
        $this->deviceExportService->setExportingData();
        $this->deviceExportService->writeDeviceData();
        $pathToSpreadSheet = $this->deviceExportService->saveSpreadSheet();

        return Storage::download($pathToSpreadSheet, 'device_export_'.now()->format('Ymd_His').'.xlsx', ['Content-Type' => AbstractExportService::XLSX_CONTENT_TYPE]);
    }

    public function downloadCsv(DeviceExportRequest $request): StreamedResponse {
        $miniGridName = $request->input('miniGrid');
        $villageName = $request->input('village');
        $deviceType = $request->input('deviceType');
        $manufacturerName = $request->input('manufacturer');

        $devices = $this->deviceService->getAllForExport($miniGridName, $villageName, $deviceType, $manufacturerName);

        $this->deviceExportService->setDeviceData($devices);
        $this->deviceExportService->setExportingData();
        $headers = ['Device Serial', 'Device Type', 'Customer', 'Address', 'Manufacturer', 'Created At', 'Updated At'];
        $csvPath = $this->deviceExportService->saveCsv($headers);

        return Storage::download($csvPath, 'device_export_'.now()->format('Ymd_His').'.csv', ['Content-Type' => AbstractExportService::CSV_CONTENT_TYPE]);
    }

    public function downloadJson(DeviceExportRequest $request): JsonResponse {
        $miniGridName = $request->input('miniGrid');
        $villageName = $request->input('village');
        $deviceType = $request->input('deviceType');
        $manufacturerName = $request->input('manufacturer');

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
