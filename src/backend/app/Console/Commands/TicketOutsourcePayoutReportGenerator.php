<?php

namespace App\Console\Commands;

use App\Models\DatabaseProxy;
use App\Models\Person\Person;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Ticket\TicketOutsourcePayoutReport;
use App\Services\TicketService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TicketOutsourcePayoutReportGenerator extends AbstractSharedCommand {
    protected $signature = 'reports:ticket-outsource-payout {--start-date=}';
    protected $description = 'Create Ticket Outsource Payout reports';

    public function __construct(
        private TicketService $ticketService,
        private Spreadsheet $spreadsheet,
        private TicketOutsourcePayoutReport $outsourceReport,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $toDay = $this->option('start-date')
            ? Carbon::parse($this->option('start-date'))->format('Y-m-d')
            : Carbon::now()->subDay()->format('Y-m-d');

        $startDay = Carbon::parse($toDay)
            ->startOfMonth()
            ->format('Y-m-d');

        try {
            $user = User::query()->first();
            $databaseProxy = app(DatabaseProxy::class);
            $companyId = $databaseProxy->findByEmail($user->email)->getCompanyId();

            $fileName = "ticket_outsource_payout_report-{$startDay}-{$toDay}.xlsx";
            $path = "reports/{$companyId}/ticket_outsource_payout/{$fileName}";

            $tickets = $this->ticketService->getForOutsourceReportForGeneration($startDay, $toDay);

            $sheet = $this->spreadsheet->getActiveSheet();
            $sheet->setTitle('payments - '.date('Y-m', strtotime($startDay)));

            // Header row
            $headers = ['A1' => 'Name', 'B1' => 'Date', 'C1' => 'Amount', 'D1' => 'Category'];
            foreach ($headers as $cell => $label) {
                $sheet->setCellValue($cell, $label);
            }

            // Fill data rows
            $row = 2;
            foreach ($tickets as $t) {
                $owner = $t->owner instanceof Person
                    ? "{$t->owner->name} {$t->owner->surname}"
                    : 'No assigned user found, please check your history reports';

                $sheet->setCellValue("A{$row}", $owner);
                $sheet->setCellValue("B{$row}", $t->created_at);
                $sheet->setCellValue("C{$row}", $t->outsource->amount);
                $sheet->setCellValue("D{$row}", $t->category->label_name);
                ++$row;
            }

            // Save Excel file temporarily
            $tempFile = tempnam(sys_get_temp_dir(), 'outsource_').'.xlsx';
            $writer = new Xlsx($this->spreadsheet);
            $writer->save($tempFile);

            Storage::put($path, file_get_contents($tempFile));

            unlink($tempFile);

            $this->outsourceReport->date = "{$startDay}---{$toDay}";
            $this->outsourceReport->path = $path;
            $this->outsourceReport->save();

            $this->info("Outsource report successfully generated: {$fileName}");
        } catch (Exception $e) {
            Log::critical('Outsource report job failed.', [
                'Exception' => $e->getMessage(),
            ]);
            $this->error("Failed to generate outsource report: {$e->getMessage()}");
        }
    }
}
