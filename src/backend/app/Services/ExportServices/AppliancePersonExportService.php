<?php

namespace App\Services\ExportServices;

use App\Models\AppliancePerson;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class AppliancePersonExportService extends AbstractExportService {
    public const HEADERS = [
        'Customer Name',
        'Customer Surname',
        'Appliance',
        'Appliance Type',
        'Device Serial',
        'Payment Type',
        'Total Cost',
        'Rate Count',
        'Down Payment',
        'First Payment Date',
        'Minimum Payable Amount',
        'Price Per Day',
        'Registered Date',
    ];

    /** @var Collection<int, AppliancePerson> */
    private Collection $appliancePeopleData;

    public function writeData(): void {
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
        $this->exportingData = $this->appliancePeopleData->map(fn (AppliancePerson $appliancePerson): array => array_values($this->toRow($appliancePerson)));
    }

    /**
     * @param Collection<int, AppliancePerson> $appliancePeopleData
     */
    public function setAppliancePeopleData(Collection $appliancePeopleData): void {
        $this->appliancePeopleData = $appliancePeopleData;
    }

    public function getTemplatePath(): string {
        return resource_path('templates/export_appliance_people_template.xlsx');
    }

    public function getPrefix(): string {
        return 'AppliancePersonExport';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function exportDataToArray(): array {
        return $this->appliancePeopleData
            ->map(fn (AppliancePerson $appliancePerson): array => $this->toRow($appliancePerson))
            ->all();
    }

    /**
     * Monetary fields are exported as raw numbers (no currency symbol or
     * thousands separator) so the file round-trips straight back into the
     * import endpoint without a parsing step.
     *
     * @return array<string, mixed>
     */
    private function toRow(AppliancePerson $appliancePerson): array {
        return [
            'customer_name' => $appliancePerson->person?->name,
            'customer_surname' => $appliancePerson->person?->surname,
            'appliance_name' => $appliancePerson->appliance?->name,
            'appliance_type' => $appliancePerson->appliance?->applianceType?->name,
            'device_serial' => $appliancePerson->device_serial,
            'payment_type' => $appliancePerson->payment_type,
            'total_cost' => $appliancePerson->total_cost,
            'rate_count' => $appliancePerson->rate_count,
            'down_payment' => $appliancePerson->down_payment,
            'first_payment_date' => $appliancePerson->first_payment_date !== null
                ? Carbon::parse($appliancePerson->first_payment_date)->format('Y-m-d')
                : null,
            'minimum_payable_amount' => $appliancePerson->minimum_payable_amount,
            'price_per_day' => $appliancePerson->price_per_day,
            'registered_date' => $appliancePerson->created_at?->format('Y-m-d'),
        ];
    }
}
