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
        'Street',
        'Geographical Information (Lat, Long)',
        'Device Serial',
        'Agent Name',
        'Last Payment',
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
        $this->exportingData = $this->peopleData->map(fn (Person $person): array => array_values($this->mapPerson($person)));
    }

    /**
     * @return array<string, mixed>
     */
    private function mapPerson(Person $person): array {
        $primaryAddress = $person->addresses->first();
        [$latitude, $longitude] = $primaryAddress?->geo?->latitudeLongitude() ?? [null, null];
        $geographicalInformation = $latitude !== null && $longitude !== null ? "{$latitude}, {$longitude}" : null;

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
            'street' => $primaryAddress?->street,
            'geographical_information' => $geographicalInformation,
            'devices' => $person->devices->pluck('device_serial')->filter()->implode(', '),
            'agent' => $person->agentSoldAppliance?->assignedAppliance?->agent?->person->name ?? '',
            'last_payment' => $person->latestPayment?->created_at?->format('d/m/Y'),
        ];
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
        return $this->peopleData->map(fn (Person $person): array => $this->mapPerson($person))->all();
    }
}
