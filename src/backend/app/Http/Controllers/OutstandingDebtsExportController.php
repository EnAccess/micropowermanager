<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use MPM\OutstandingDebts\OutstandingDebtsExportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OutstandingDebtsExportController {
    public function __construct(
        private OutstandingDebtsExportService $outstandingDebtsExportService,
    ) {}

    public function download(): BinaryFileResponse {
        $pathToSpreadSheet = $this->outstandingDebtsExportService->createReport(CarbonImmutable::now());

        $path = Storage::disk('local')->path($pathToSpreadSheet);

        return response()->download($path, 'outstanding_debts_export_'.now()->format('Ymd_His').'.xlsx');
    }
}
