<?php

namespace App\People\Export;

use App\Models\Person\Person;
use App\Services\AbstractExportService;
use Illuminate\Support\Collection;

class PersonExportService extends AbstractExportService {
    /** @var Collection<int, Person> */
    private Collection $peopleData;

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
        $this->exportingData = $this->peopleData->map(function (Person $person): array {
            $primaryAddress = $person->addresses->first();
            $devices = $person->devices->pluck('device_serial')->filter()->implode(', ');
            $agent = optional($person->agentSoldAppliance?->assignedAppliance?->agent);

            return [
                $person->title,
                $person->name,
                $person->surname,
                $person->birth_date,
                $person->sex === '1' ? 'Male' : 'Female',
                optional($primaryAddress)->email,
                optional($primaryAddress)->phone,
                optional($primaryAddress?->city)->name,
                $devices,
                $agent->person->name ?? '',
            ];
        });
    }

    /**
     * @param Collection<int, Person> $peopleData
     */
    public function setPeopleData(Collection $peopleData): void {
        $this->peopleData = $peopleData;
    }

    public function getTemplatePath(): string {
        return resource_path('templates/export_people_template.xlsx');
    }

    public function getPrefix(): string {
        return 'CustomerExport';
    }
}
