<?php

namespace App\Services\ExportServices;

use App\Models\MainSettings;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class SettingsExportService extends AbstractExportService {
    private ?MainSettings $settingsData = null;

    public function writeSettingsData(): void {
        $this->setActivatedSheet('Sheet1');

        if (!$this->settingsData instanceof MainSettings) {
            return;
        }

        $settings = [
            $this->settingsData->site_title,
            $this->settingsData->company_name,
            $this->settingsData->currency,
            $this->settingsData->country,
            $this->settingsData->language,
            $this->readable($this->settingsData->vat_energy ?? 0),
            $this->readable($this->settingsData->vat_appliance ?? 0),
            $this->settingsData->usage_type ?? '',
            $this->settingsData->sms_gateway_id ?? '',
            $this->convertUtcDateToTimezone($this->settingsData->created_at),
            $this->convertUtcDateToTimezone($this->settingsData->updated_at),
        ];

        foreach ($settings as $key => $value) {
            $columnLetter = Coordinate::stringFromColumnIndex($key + 1);
            $this->worksheet->setCellValue($columnLetter.'2', $value);
        }

        foreach ($this->worksheet->getColumnIterator() as $column) {
            $this->worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
    }

    public function setExportingData(): void {
        if (!$this->settingsData instanceof MainSettings) {
            $this->exportingData = collect([]);

            return;
        }

        $this->exportingData = collect([
            [
                $this->settingsData->site_title,
                $this->settingsData->company_name,
                $this->settingsData->currency,
                $this->settingsData->country,
                $this->settingsData->language,
                $this->readable($this->settingsData->vat_energy ?? 0),
                $this->readable($this->settingsData->vat_appliance ?? 0),
                $this->settingsData->usage_type ?? '',
                $this->settingsData->sms_gateway_id ?? '',
                $this->convertUtcDateToTimezone($this->settingsData->created_at),
                $this->convertUtcDateToTimezone($this->settingsData->updated_at),
            ],
        ]);
    }

    public function setSettingsData(?MainSettings $settingsData): void {
        $this->settingsData = $settingsData;
    }

    public function getTemplatePath(): string {
        return resource_path('templates/export_settings_template.xlsx');
    }

    public function getPrefix(): string {
        return 'SettingsExport';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function exportDataToArray(): array {
        if (!$this->settingsData instanceof MainSettings) {
            return [];
        }

        return [
            [
                'site_title' => $this->settingsData->site_title,
                'company_name' => $this->settingsData->company_name,
                'currency' => $this->settingsData->currency,
                'country' => $this->settingsData->country,
                'language' => $this->settingsData->language,
                'vat_energy' => $this->settingsData->vat_energy,
                'vat_appliance' => $this->settingsData->vat_appliance,
                'usage_type' => $this->settingsData->usage_type,
                'sms_gateway_id' => $this->settingsData->sms_gateway_id,
                'created_at' => $this->convertUtcDateToTimezone($this->settingsData->created_at),
                'updated_at' => $this->convertUtcDateToTimezone($this->settingsData->updated_at),
            ],
        ];
    }
}
