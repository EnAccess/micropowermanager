<?php

namespace App\Services\ExportServices;

use App\Models\Person\Person;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class PersonExportService extends AbstractExportService {
    public const HEADERS = [
        'Title',
        'Name',
        'Surname',
        'Registered Date',
        'Birth Date',
        'Gender',
        'Email',
        'Phone',
        'City',
        'Device Serial',
        'Agent Name',
    ];

    /** @var Collection<int, Person> */
    private Collection $peopleData;

    public function writePeopleData(): void {
        $this->setActivatedSheet('Sheet1');

        foreach ($this->exportingData as $key => $value) {
            foreach (array_values($value) as $columnIndex => $cellValue) {
                $this->worksheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex + 1).($key + 2), $cellValue);
            }
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
                $person->created_at?->format('d/m/Y'),
                $person->birth_date,
                $person->gender,
                $primaryAddress?->email,
                $primaryAddress?->phone,
                $primaryAddress?->city?->name,
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

    /**
     * @return array<int, array<string, mixed>>
     */
    public function exportDataToArray(): array {
        if ($this->peopleData->isEmpty()) {
            return [];
        }
        // TODO: support some form of pagination to limit the data to be exported using json
        // transform exporting data to JSON structure for person export
        $jsonDataTransform = $this->peopleData->map(function (Person $person): array {
            $primaryAddress = $person->addresses->first();
            $devices = $person->devices->pluck('device_serial')->filter()->implode(', ');
            $agent = optional($person->agentSoldAppliance?->assignedAppliance?->agent);

            return [
                'title' => $person->title,
                'name' => $person->name,
                'surname' => $person->surname,
                'registered_date' => $person->created_at?->format('d/m/Y'),
                'birth_date' => $person->birth_date,
                'gender' => $person->gender,
                'email' => $primaryAddress?->email,
                'phone' => $primaryAddress?->phone,
                'city' => $primaryAddress?->city?->name,
                'devices' => $devices,
                'agent' => $agent->person->name ?? '',
            ];
        });

        return $jsonDataTransform->all();
    }
}
