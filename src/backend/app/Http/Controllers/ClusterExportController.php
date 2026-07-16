<?php

namespace App\Http\Controllers;

use App\Services\ClusterService;
use App\Services\ExportServices\AbstractExportService;
use App\Services\ExportServices\ClusterExportService;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Group('Export', 'Export data as Excel, CSV, or JSON.', weight: 11)]
class ClusterExportController extends Controller {
    public function __construct(
        private ClusterService $clusterService,
        private ClusterExportService $clusterExportService,
    ) {}

    /**
     * Export clusters.
     *
     * Downloads clusters as an Excel or CSV file, or returns them as JSON.
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
        $clusters = $this->clusterService->getAllForExport();
        $this->clusterExportService->createSpreadSheetFromTemplate($this->clusterExportService->getTemplatePath());
        $this->clusterExportService->setClusterData($clusters);
        $this->clusterExportService->setExportingData();
        $this->clusterExportService->writeClusterData();
        $pathToSpreadSheet = $this->clusterExportService->saveSpreadSheet();

        return Storage::download($pathToSpreadSheet, 'cluster_export_'.now()->format('Ymd_His').'.xlsx', ['Content-Type' => AbstractExportService::XLSX_CONTENT_TYPE]);
    }

    public function downloadCsv(): StreamedResponse {
        $clusters = $this->clusterService->getAllForExport();

        $this->clusterExportService->setClusterData($clusters);
        $this->clusterExportService->setExportingData();
        $headers = ['Cluster Name', 'Manager', 'Mini Grids Count', 'Cities Count', 'Created At', 'Updated At'];
        $csvPath = $this->clusterExportService->saveCsv($headers);

        return Storage::download($csvPath, 'cluster_export_'.now()->format('Ymd_His').'.csv', ['Content-Type' => AbstractExportService::CSV_CONTENT_TYPE]);
    }

    public function downloadJson(): JsonResponse {
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
