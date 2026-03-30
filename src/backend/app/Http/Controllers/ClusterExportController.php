<?php

namespace App\Http\Controllers;

use App\Services\ClusterService;
use App\Services\ExportServices\ClusterExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClusterExportController extends Controller {
    public function __construct(
        private ClusterService $clusterService,
        private ClusterExportService $clusterExportService,
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
        $clusters = $this->clusterService->getAllForExport();
        $this->clusterExportService->createSpreadSheetFromTemplate($this->clusterExportService->getTemplatePath());
        $this->clusterExportService->setClusterData($clusters);
        $this->clusterExportService->setExportingData();
        $this->clusterExportService->writeClusterData();
        $pathToSpreadSheet = $this->clusterExportService->saveSpreadSheet();

        return Storage::download($pathToSpreadSheet, 'cluster_export_'.now()->format('Ymd_His').'.xlsx');
    }

    public function downloadCsv(Request $request): StreamedResponse {
        $clusters = $this->clusterService->getAllForExport();

        $this->clusterExportService->setClusterData($clusters);
        $this->clusterExportService->setExportingData();
        $headers = ['Cluster Name', 'Manager', 'Mini Grids Count', 'Cities Count', 'Created At', 'Updated At'];
        $csvPath = $this->clusterExportService->saveCsv($headers);

        return Storage::download($csvPath, 'cluster_export_'.now()->format('Ymd_His').'.csv');
    }

    public function downloadJson(Request $request): JsonResponse {
        $clusters = $this->clusterService->getAllForExport();

        $this->clusterExportService->setClusterData($clusters);
        $jsonData = $this->clusterExportService->exportDataToArray();

        return response()->json([
            'data' => $jsonData,
            'meta' => [
                'total' => count($jsonData),
                'exported_at' => now()->toISOString(),
            ],
        ]);
    }
}
