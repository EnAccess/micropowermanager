<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class OutsourceReportGenerator extends AbstractSharedCommand {
    protected $signature = 'reports:outsource {--start-date=}';
    protected $description = 'Create outsources reports';

    public function __construct() {
        parent::__construct();
    }

    public function handle(): void {
        if ($this->option('start-date') !== '') {
            $toDay = Carbon::parse($this->option('start-date'))->format('Y-m-d');
        } else {
            $toDay = Carbon::now()->subDays(1)->format('Y-m-d');
        }
        $startDay = Carbon::parse($toDay)->modify('first day of this month')->format('Y-m-d');
        // TODO: fix for Saas.

        /*$this->reports->outsourceWithJob($startDay, $toDay);
        try {
            $tickets = $this->ticket
                ::with('outsource', 'owner', 'category')
                ->whereHas('category', static function ($q) {
                    $q->where('out_source', 1);
                })
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();
            //create excel sheet
            $fileName = 'Outsourcing-' . $startDate . '-' . $endDate . '.xlsx';

            $sheet = $this->spreadsheet->getActiveSheet();
            $sheet->setTitle('payments - ' . date('Y-m', strtotime($startDate)));

            $sheet->setCellValue('A1', 'Name');
            $sheet->setCellValue('B1', 'Date');
            $sheet->setCellValue('C1', 'Amount');
            $sheet->setCellValue('D1', 'Category');

            $row = 3;
            foreach ($tickets as $t) {
                $owner = $t->owner !== null ? $t->owner->name .' '.$t->owner->surname:"No assigned user found, please check your history reports";
                $sheet->setCellValue('A' . $row, $owner);
                $sheet->setCellValue('B' . $row, $t->created_at);
                $sheet->setCellValue('C' . $row, $t->outsource->amount);
                $sheet->setCellValue('D' . $row, $t->category->label_name);
            }
            $writer = new Xlsx($this->spreadsheet);
            $dirPath = storage_path('./outsourcing');
            if (!file_exists($dirPath)) {
                mkdir($dirPath, 0774, true);
            }
            try {
                $writer->save(storage_path('./outsourcing/' . $fileName));
            } catch (Exception $e) {
                echo 'error' . $e->getMessage();
            }

            $this->outsourceReport->date = $startDate . '---' . $endDate;
            $this->outsourceReport->path = storage_path('./outsourcing/' . $fileName);
            $this->outsourceReport->save();

        }catch (\Exception $e){
            Log::critical('Outsource report job failed.',
                ['Exception' => $e->getMessage()]
            );
        }*/
    }
}
