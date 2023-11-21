<?php


namespace Inensus\Ticket\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inensus\Ticket\Http\Resources\TicketResource;
use Inensus\Ticket\Models\TicketCategory;
use Inensus\Ticket\Models\TicketOutsourceReport;
use Inensus\Ticket\Models\Ticket;
use Inensus\Ticket\Services\TicketOutsourceReportService;
use Inensus\Ticket\Services\TicketService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TicketExportController
{
    public function __construct(
       private TicketOutsourceReportService $ticketOutsourceReportService,
       private TicketService $ticketService
    ) {
    }

    /**
     * A list of stored book keeping data
     *
     * @return TicketResource
     */
    public function index(Request $request): TicketResource
    {
        $limit = $request->input('per_page');

        return  TicketResource::make($this->ticketOutsourceReportService->getAll($limit));
    }

    /**
     * Generates a book keeping file and stores it
     *
     * @param Request $request
     *
     * @return TicketResource
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function outsource(Request $request): TicketResource
    {
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $tickets = $this->ticketService->getForOutsourceReport($startDate, $endDate);
        $fileName = $this->ticketOutsourceReportService->createExcelSheet($startDate, $endDate,$tickets);
        $ticketOutsourceReportData = [
            'date'=> date('Y-m', strtotime($startDate)),
            'path' => storage_path('./outsourcing/' . $fileName)
        ];

        return  TicketResource::make($this->ticketOutsourceReportService->create($ticketOutsourceReportData));
    }


    public function download($id): BinaryFileResponse
    {
        return response()->download($this->ticketOutsourceReportService->getById($id));
    }


}
