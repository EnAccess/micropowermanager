<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ApplianceRateService;
use App\Services\ExportServices\OutstandingDebtsExportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OutstandingDebtsExportController {
    public function __construct(
        private OutstandingDebtsExportService $outstandingDebtsExportService,
        private ApplianceRateService $applianceRateService,
    ) {}

    public function download(Request $request): StreamedResponse|JsonResponse {
        $format = $request->get('format', 'excel');

        if ($format === 'json') {
            return $this->downloadJson($request);
        }

        return $this->downloadExcel($request);
    }

    public function downloadExcel(Request $request): StreamedResponse {
        $pathToSpreadSheet = $this->outstandingDebtsExportService->createReport(CarbonImmutable::now());

        return Storage::download($pathToSpreadSheet, 'outstanding_debts_export_'.now()->format('Ymd_His').'.xlsx');
    }

    public function downloadJson(Request $request): JsonResponse {
        $toDate = CarbonImmutable::now();
        $currency = $this->applianceRateService->getCurrencyFromMainSettings();
        $data = $this->applianceRateService->queryOutstandingDebtsByApplianceRates($toDate)->get();

        $this->outstandingDebtsExportService->setCurrency($currency);
        $this->outstandingDebtsExportService->setOutstandingDebtsData($data);
        $jsonData = $this->outstandingDebtsExportService->exportDataToArray();

        return response()->json([
            'data' => $jsonData,
            'meta' => [
                'total' => count($jsonData),
                'currency' => $currency,
                'report_date' => $toDate->toISOString(),
                'exported_at' => now()->toISOString(),
            ],
        ]);
    }
}
