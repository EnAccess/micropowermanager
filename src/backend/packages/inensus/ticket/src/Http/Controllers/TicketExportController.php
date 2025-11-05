<?php

namespace Inensus\Ticket\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inensus\Ticket\Http\Resources\TicketResource;
use Inensus\Ticket\Services\TicketOutsourceReportService;
use Inensus\Ticket\Services\TicketService;
use PhpOffice\PhpSpreadsheet\Exception;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketExportController {
    public function __construct(
        private TicketOutsourceReportService $ticketOutsourceReportService,
        private TicketService $ticketService,
    ) {}

    /**
     * A list of stored book keeping data.
     */
    public function index(Request $request): TicketResource {
        $limit = $request->input('per_page');

        return TicketResource::make($this->ticketOutsourceReportService->getAll($limit));
    }

    /**
     * Generates a book keeping file and stores it.
     *
     * @throws Exception
     */
    public function outsource(Request $request): TicketResource {
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        $tickets = $this->ticketService->getForOutsourceReport($startDate, $endDate);

        $filePath = $this->ticketOutsourceReportService->createExcelSheet($startDate, $endDate, $tickets);

        $ticketOutsourceReportData = [
            'date' => date('Y-m', strtotime($startDate)),
            'path' => $filePath,
        ];

        return TicketResource::make(
            $this->ticketOutsourceReportService->create($ticketOutsourceReportData)
        );
    }

    public function download(int $id): StreamedResponse {
        $report = $this->ticketOutsourceReportService->getById($id);
        $relativePath = $report->path;

        if (!Storage::exists($relativePath)) {
            abort(404, 'Report file not found.');
        }

        return Storage::download($relativePath);
    }
}
