<?php

namespace Inensus\Ticket\Services;

use App\Services\BaseService;
use App\Services\IBaseService;
use Inensus\Ticket\Models\TicketOutsourceReport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TicketOutsourceReportService  implements IBaseService
{
    public function __construct(
        private TicketOutsourceReport $ticketOutsourceReport,
        private Spreadsheet $spreadsheet)
    {
    }

    public function getAll($limit = null)
    {
        if ($limit) {
            return $this->ticketOutsourceReport->newQuery()->paginate($limit);
        }

        return $this->ticketOutsourceReport->newQuery()->get();
    }

    public function createExcelSheet($startDate,$endDate,$tickets)
    {
        $fileName = 'Outsourcing-' . $startDate . '-' . $endDate . '.xlsx';

        $sheet = $this->spreadsheet->getActiveSheet();
        $sheet->setTitle('payments - ' . date('Y-m', strtotime($startDate)));

        $sheet->setCellValue('A1', 'Name');
        $sheet->setCellValue('B1', 'Date');
        $sheet->setCellValue('C1', 'Amount');
        $sheet->setCellValue('D1', 'Category');

        $row = 3;
        foreach ($tickets as $t) {
            $sheet->setCellValue('A' . $row, $t->assignedTo->user_name);
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

        return $fileName;
    }

    public function create($ticketOutsourceReportData)
    {
        return $this->ticketOutsourceReport->newQuery()->create($ticketOutsourceReportData);
    }

    public function getById($outsourceReportId)
    {
        return  $this->ticketOutsourceReport->newQuery()->find($outsourceReportId);
    }



    public function update($model, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }


}
