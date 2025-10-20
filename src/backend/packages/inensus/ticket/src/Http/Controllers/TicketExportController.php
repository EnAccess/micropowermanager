<?php

namespace Inensus\Ticket\Http\Controllers;

use App\Support\AppStorage;
use Illuminate\Http\RedirectResponse;
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

        $filePath = $this->ticketOutsourceReportService->createExcelSheet($startDate, $endDate, $tickets);

        $ticketOutsourceReportData = [
            'date' => date('Y-m', strtotime($startDate)),
            'path' => $filePath,
        ];

        return TicketResource::make(
            $this->ticketOutsourceReportService->create($ticketOutsourceReportData)
        );
    }

    public function download(int $id): BinaryFileResponse|RedirectResponse {
        $report = $this->ticketOutsourceReportService->getById($id);
        $disk = config('filesystems.default');
        $relativePath = $report->path;

        if (!AppStorage::exists($relativePath)) {
            abort(404, 'Report file not found.');
        }

        if ($disk === 'local') {
            return response()->download(AppStorage::url($relativePath));
        }

        $temporaryUrl = AppStorage::temporaryUrl(
            $relativePath,
            now()->addMinutes(5)
        );

        return redirect()->away($temporaryUrl);
    }
}
