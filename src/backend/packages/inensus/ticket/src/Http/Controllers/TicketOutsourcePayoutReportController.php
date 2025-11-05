<?php

namespace Inensus\Ticket\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inensus\Ticket\Http\Resources\TicketResource;
use Inensus\Ticket\Services\TicketOutsourcePayoutReportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketOutsourcePayoutReportController {
    public function __construct(
        private TicketOutsourcePayoutReportService $TicketOutsourcePayoutReportService,
    ) {}

    /**
     * A list of stored Ticket Outsource Payout reports.
     */
    public function index(Request $request): TicketResource {
        $limit = $request->input('per_page');

        return TicketResource::make($this->TicketOutsourcePayoutReportService->getAll($limit));
    }

    public function download(int $id): StreamedResponse {
        $report = $this->TicketOutsourcePayoutReportService->getById($id);
        $relativePath = $report->path;

        if (!Storage::exists($relativePath)) {
            abort(404, 'Report file not found.');
        }

        return Storage::download($relativePath);
    }
}
