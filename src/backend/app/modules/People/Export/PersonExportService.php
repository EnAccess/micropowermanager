<?php

namespace App\People\Export;

use App\Services\AbstractExportService;

class PersonExportService extends AbstractExportService {
    private $peopleData;

    public function writePeopleData(): void {
        $this->setActivatedSheet('Sheet1');

        foreach ($this->exportingData as $key => $value) {
            $this->worksheet->setCellValue('A'.($key + 2), $value[0]);
            $this->worksheet->setCellValue('B'.($key + 2), $value[1]);
            $this->worksheet->setCellValue('C'.($key + 2), $value[2]);
            $this->worksheet->setCellValue('D'.($key + 2), $value[3]);
            $this->worksheet->setCellValue('E'.($key + 2), $value[4]);
            $this->worksheet->setCellValue('F'.($key + 2), $value[5]);
            $this->worksheet->setCellValue('G'.($key + 2), $value[6]);
            $this->worksheet->setCellValue('H'.($key + 2), $value[7]);
            $this->worksheet->setCellValue('I'.($key + 2), $value[8]);
            $this->worksheet->setCellValue('J'.($key + 2), $value[9]);
        }

        foreach ($this->worksheet->getColumnIterator() as $column) {
            $this->worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
    }

    public function setExportingData(): void {
        $this->exportingData = $this->peopleData->map(function ($person) {
            $primaryAddress = $person->addresses->first();
            $device = $person->devices->first();
            $agent = optional($person->agent_sold_appliance?->assigned_appliance?->agent);

            return [
                $person->title,
                $person->name,
                $person->surname,
                $person->birth_date,
                $person->sex === '1' ? 'Male' : 'Female',
                optional($primaryAddress)->email,
                optional($primaryAddress)->phone,
                optional($primaryAddress?->city)->name,
                optional($device)->device_serial,
                $agent->person->name ?? '',
            ];
        });
    }

    public function setPeopleData($peopleData): void {
        $this->peopleData = $peopleData;
    }

    public function getTemplatePath(): string {
        return storage_path('people/export_people_template.xlsx');
    }

    public function getPrefix(): string {
        return 'CustomerExport';
    }
}
