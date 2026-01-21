<?php

namespace App\Services\ExportServices;

use App\Models\Appliance;
use Illuminate\Support\Collection;

class ApplianceExportService extends AbstractExportService {
    /** @var Collection<int, Appliance> */
    private Collection $applianceData;

    public function writeApplianceData(): void {
        $this->setActivatedSheet('Sheet1');

        foreach ($this->exportingData as $key => $value) {
            $this->worksheet->setCellValue('A'.($key + 2), $value[0]);
            $this->worksheet->setCellValue('B'.($key + 2), $value[1]);
            $this->worksheet->setCellValue('C'.($key + 2), $value[2]);
            $this->worksheet->setCellValue('D'.($key + 2), $value[3]);
            $this->worksheet->setCellValue('E'.($key + 2), $value[4]);
            $this->worksheet->setCellValue('F'.($key + 2), $value[5]);
            $this->worksheet->setCellValue('G'.($key + 2), $value[6]);
        }

        foreach ($this->worksheet->getColumnIterator() as $column) {
            $this->worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
    }

    public function setExportingData(): void {
        $this->exportingData = $this->applianceData->map(function (Appliance $appliance): array {
            $applianceTypeName = $appliance->applianceType->name ?? '';
            $totalSold = $appliance->agentAssignedAppliance->sum('sold_count') ?? 0;
            $totalRates = $appliance->rates->count();

            return [
                $appliance->name,
                $applianceTypeName,
                $this->readable($appliance->price).$this->currency,
                $totalSold,
                $totalRates,
                $this->convertUtcDateToTimezone($appliance->created_at),
                $this->convertUtcDateToTimezone($appliance->updated_at),
            ];
        });
    }

    /**
     * @param Collection<int, Appliance> $applianceData
     */
    public function setApplianceData(Collection $applianceData): void {
        $this->applianceData = $applianceData;
    }

    public function getTemplatePath(): string {
        return resource_path('templates/export_appliances_template.xlsx');
    }

    public function getPrefix(): string {
        return 'ApplianceExport';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function exportDataToArray(): array {
        if ($this->applianceData->isEmpty()) {
            return [];
        }
        // TODO: support some form of pagination to limit the data to be exported using json
        // transform exporting data to JSON structure for appliance export
        $jsonDataTransform = $this->applianceData->map(function (Appliance $appliance): array {
            $applianceTypeName = $appliance->applianceType->name ?? '';
            $totalSold = $appliance->agentAssignedAppliance->sum('sold_count') ?? 0;
            $paymentPlans = $appliance->rates->map(fn ($rate): array => [
                'total_cost' => $this->readable($rate->total_cost),
                'rate_count' => $rate->rate_count,
                'down_payment' => $this->readable($rate->down_payment ?? 0),
            ])->all();

            return [
                'appliance_name' => $appliance->name,
                'appliance_type' => $applianceTypeName,
                'price' => $this->readable($appliance->price),
                'currency' => $this->currency,
                'total_sold' => $totalSold,
                'payment_plans' => $paymentPlans,
                'created_at' => $this->convertUtcDateToTimezone($appliance->created_at),
                'updated_at' => $this->convertUtcDateToTimezone($appliance->updated_at),
            ];
        });

        return $jsonDataTransform->all();
    }
}
