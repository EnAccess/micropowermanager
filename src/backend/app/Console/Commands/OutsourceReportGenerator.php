<?php

namespace App\Console\Commands;

use App\Models\Person\Person;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inensus\Ticket\Models\TicketOutsourceReport;
use Inensus\Ticket\Services\TicketService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class OutsourceReportGenerator extends AbstractSharedCommand {
    protected $signature = 'reports:outsource {--start-date=}';
    protected $description = 'Create outsource reports';

    public function __construct(
        private TicketService $ticketService,
        private Spreadsheet $spreadsheet,
        private TicketOutsourceReport $outsourceReport,
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
            $tickets = $this->ticketService->getForOutsourceReportForGeneration($startDay, $toDay);
            $fileName = "Outsourcing-{$startDay}-{$toDay}.xlsx";
            $relativePath = "outsourcing/{$fileName}";

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

            Storage::put($relativePath, file_get_contents($tempFile));

            unlink($tempFile);

            $this->outsourceReport->date = "{$startDay}---{$toDay}";
            $this->outsourceReport->path = $relativePath;
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
