<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\OutstandingDebtsExportService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OutstandingDebtsExportController {
    public function __construct(
        private OutstandingDebtsExportService $outstandingDebtsExportService,
    ) {}

    public function download(): StreamedResponse {
        $pathToSpreadSheet = $this->outstandingDebtsExportService->createReport(CarbonImmutable::now());

        return Storage::download($pathToSpreadSheet, 'outstanding_debts_export_'.now()->format('Ymd_His').'.xlsx');
    }
}
