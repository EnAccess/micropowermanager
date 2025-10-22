<?php

namespace Inensus\Ticket\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

        $filePath = $this->ticketOutsourceReportService->createExcelSheet($startDate, $endDate, $tickets);

        $ticketOutsourceReportData = [
            'date' => date('Y-m', strtotime($startDate)),
            'path' => $filePath,
        ];

        return TicketResource::make(
            $this->ticketOutsourceReportService->create($ticketOutsourceReportData)
        );
    }

    public function download(int $id): BinaryFileResponse|\Illuminate\Http\Response {
        $report = $this->ticketOutsourceReportService->getById($id);
        $disk = config('filesystems.default');
        $relativePath = $report->path;

        if (!Storage::exists($relativePath)) {
            abort(404, 'Report file not found.');
        }

        if ($disk === 'local') {
            $localPath = Storage::disk('local')->path($relativePath);

            return response()->download($localPath);
        }

        $fileContent = Storage::get($relativePath);
        $fileName = basename($relativePath);

        return response($fileContent)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="'.$fileName.'"')
            ->header('Content-Length', strlen($fileContent));
    }
}
