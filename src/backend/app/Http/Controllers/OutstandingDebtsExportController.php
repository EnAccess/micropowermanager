<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ApplianceRateService;
use App\Services\ExportServices\AbstractExportService;
use App\Services\ExportServices\OutstandingDebtsExportService;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Group('Export', 'Export data as Excel, CSV, or JSON.', weight: 11)]
class OutstandingDebtsExportController {
    public function __construct(
        private OutstandingDebtsExportService $outstandingDebtsExportService,
        private ApplianceRateService $applianceRateService,
    ) {}

    /**
     * Export outstanding debts.
     *
     * Downloads outstanding appliance debts as an Excel file, or returns them as JSON.
     *
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    #[QueryParameter('format', description: 'Export format.', type: "'excel'|'json'", default: 'excel')]
    public function download(Request $request): StreamedResponse|JsonResponse {
        $format = $request->input('format', 'excel');

        if ($format === 'json') {
            return $this->downloadJson();
        }

        return $this->downloadExcel();
    }

    public function downloadExcel(): StreamedResponse {
        $pathToSpreadSheet = $this->outstandingDebtsExportService->createReport(CarbonImmutable::now());

        return Storage::download($pathToSpreadSheet, 'outstanding_debts_export_'.now()->format('Ymd_His').'.xlsx', ['Content-Type' => AbstractExportService::XLSX_CONTENT_TYPE]);
    }

    public function downloadJson(): JsonResponse {
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
