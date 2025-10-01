<?php

namespace Inensus\Ticket\Http\Controllers;

use Illuminate\Http\Request;
use Inensus\Ticket\Http\Resources\TicketResource;
use Inensus\Ticket\Services\TicketOutsourceReportService;
use Inensus\Ticket\Services\TicketService;
use PhpOffice\PhpSpreadsheet\Exception;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        $fileName = $this->ticketOutsourceReportService->createExcelSheet($startDate, $endDate, $tickets);
        $ticketOutsourceReportData = [
            'date' => date('Y-m', strtotime($startDate)),
            'path' => storage_path('./outsourcing/'.$fileName),
        ];

        return TicketResource::make($this->ticketOutsourceReportService->create($ticketOutsourceReportData));
    }

    public function download(int $id): BinaryFileResponse {
        $report = $this->ticketOutsourceReportService->getById($id);

        return response()->download(explode('*', $report->path)[0]);
    }
}
