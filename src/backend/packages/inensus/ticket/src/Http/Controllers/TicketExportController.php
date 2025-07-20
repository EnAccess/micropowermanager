<?php

namespace Inensus\Ticket\Http\Controllers;

use Illuminate\Http\Request;
use Inensus\Ticket\Http\Resources\TicketResource;
use Inensus\Ticket\Services\TicketOutsourceReportService;
use Inensus\Ticket\Services\TicketService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TicketExportController {
    public function __construct(
        private TicketOutsourceReportService $ticketOutsourceReportService,
        private TicketService $ticketService,
    ) {}

    /**
     * A list of stored book keeping data.
     *
     * @return TicketResource
     */
    public function index(Request $request): TicketResource {
        $limit = $request->input('per_page');

        return TicketResource::make($this->ticketOutsourceReportService->getAll($limit));
    }

    /**
     * Generates a book keeping file and stores it.
     *
     * @param Request $request
     *
     * @return TicketResource
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
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

    public function download($id): BinaryFileResponse {
        $report = $this->ticketOutsourceReportService->getById($id);

        return response()->download(explode('*', $report->path)[0]);
    }
}
