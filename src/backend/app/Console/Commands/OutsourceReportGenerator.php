<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Inensus\Ticket\Models\TicketOutsourceReport;
use Inensus\Ticket\Services\TicketService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class OutsourceReportGenerator extends AbstractSharedCommand {
    protected $signature = 'reports:outsource {--start-date=}';
    protected $description = 'Create outsources reports';

    public function __construct(
        private TicketService $ticketService,
        private Spreadsheet $spreadsheet,
        private TicketOutsourceReport $outsourceReport,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        if ($this->option('start-date') !== '') {
            $toDay = Carbon::parse($this->option('start-date'))->format('Y-m-d');
        } else {
            $toDay = Carbon::now()->subDays(1)->format('Y-m-d');
        }
        $startDay = Carbon::parse($toDay)->modify('first day of this month')->format('Y-m-d');

        try {
            $tickets = $this->ticketService->getForOutsourceReportForGeneration($startDay, $toDay);
            // create excel sheet
            $fileName = 'Outsourcing-'.$startDay.'-'.$toDay.'.xlsx';

            $sheet = $this->spreadsheet->getActiveSheet();
            $sheet->setTitle('payments - '.date('Y-m', strtotime($startDay)));

            $sheet->setCellValue('A1', 'Name');
            $sheet->setCellValue('B1', 'Date');
            $sheet->setCellValue('C1', 'Amount');
            $sheet->setCellValue('D1', 'Category');

            $row = 2;
            foreach ($tickets as $t) {
                $owner = $t->owner !== null ? $t->owner->person->name.' '.$t->owner->person->surname : 'No assigned user found, please check your history reports';
                $sheet->setCellValue('A'.$row, $owner);
                $sheet->setCellValue('B'.$row, $t->created_at);
                $sheet->setCellValue('C'.$row, $t->outsource->amount);
                $sheet->setCellValue('D'.$row, $t->category->label_name);
                ++$row;
            }
            $writer = new Xlsx($this->spreadsheet);
            $dirPath = storage_path('outsourcing');
            if (!file_exists($dirPath)) {
                mkdir($dirPath, 0774, true);
            }
            try {
                $writer->save(storage_path('outsourcing/'.$fileName));
            } catch (Exception $e) {
                echo 'error'.$e->getMessage();
            }

            $this->outsourceReport->date = $startDay.'---'.$toDay;
            $this->outsourceReport->path = storage_path('outsourcing/'.$fileName);
            $this->outsourceReport->save();
        } catch (\Exception $e) {
            Log::critical(
                'Outsource report job failed.',
                ['Exception' => $e->getMessage()]
            );
        }
    }
}
