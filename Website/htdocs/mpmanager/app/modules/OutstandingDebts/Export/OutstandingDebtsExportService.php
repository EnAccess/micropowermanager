<?php

namespace MPM\OutstandingDebts\Export;

use App\Services\AbstractExportService;

class OutstandingDebtsExportService extends AbstractExportService
{
    private $path = __DIR__ . "/export_outstanding_debts_template.xlsx";
    private $outstandingDebtsData;
    public function writeOutstandingDebtsData()
    {
        $this->setActivatedSheet('Sheet1');

        foreach ($this->exportingData as $key => $value) {
            $this->worksheet->setCellValue('A' . ($key + 2), $value[0]);
            $this->worksheet->setCellValue('B' . ($key + 2), $value[1]);
            $this->worksheet->setCellValue('C' . ($key + 2), $value[2]);
            $this->worksheet->setCellValue('D' . ($key + 2), $value[3]);
            $this->worksheet->setCellValue('E' . ($key + 2), $value[4]);
        }

        foreach ($this->worksheet->getColumnIterator() as $column) {
            $this->worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
    }

    public function setExportingData()
    {
        $this->exportingData = $this->outstandingDebtsData->map(function ($applianceRate) {
            return [
                $applianceRate->assetPerson->person->name . ' ' . $applianceRate->assetPerson->person->surname,
                $applianceRate->assetPerson->asset->name,
                $applianceRate->assetPerson->device_serial,
                $applianceRate->due_date,
                $applianceRate->remaining
            ];
        });
    }

    public function setOutstandingDebtsData($outstandingDebtsData)
    {
        $this->outstandingDebtsData = $outstandingDebtsData;
    }

    public function getTemplatePath()
    {
        return $this->path;
    }
}
