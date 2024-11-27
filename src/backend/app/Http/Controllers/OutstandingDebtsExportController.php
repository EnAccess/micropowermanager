<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use MPM\OutstandingDebts\OutstandingDebtsExportService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OutstandingDebtsExportController {
    public function __construct(
        private OutstandingDebtsExportService $outstandingDebtsExportService,
    ) {}

    public function download(): BinaryFileResponse {
        $path = $this->outstandingDebtsExportService->createReport(CarbonImmutable::now());

        return response()->download($path);
    }
}
