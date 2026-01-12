<?php

namespace App\Services\ExportServices;

use App\Models\Device;
use Illuminate\Support\Collection;

class DeviceExportService extends AbstractExportService {
    /** @var Collection<int, Device> */
    private Collection $deviceData;

    public function writeDeviceData(): void {
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
        $this->exportingData = $this->deviceData->map(function (Device $device): array {
            $personName = $device->person?->name ?? '';
            $personSurname = $device->person?->surname ?? '';
            $fullName = trim($personName.' '.$personSurname);
            $address = $device->address?->city->name ?? '';

            return [
                $device->device_serial,
                $device->device_type,
                $fullName,
                $address,
                $device->device->manufacturer->name ?? '',
                $this->convertUtcDateToTimezone($device->created_at),
                $this->convertUtcDateToTimezone($device->updated_at),
            ];
        });
    }

    /**
     * @param Collection<int, Device> $deviceData
     */
    public function setDeviceData(Collection $deviceData): void {
        $this->deviceData = $deviceData;
    }

    public function getTemplatePath(): string {
        return resource_path('templates/export_devices_template.xlsx');
    }

    public function getPrefix(): string {
        return 'DeviceExport';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function exportDataToArray(): array {
        if ($this->deviceData->isEmpty()) {
            return [];
        }
        // TODO: support some form of pagination to limit the data to be exported using json
        // transform exporting data to JSON structure for device export
        $jsonDataTransform = $this->deviceData->map(function (Device $device): array {
            $personName = $device->person?->name ?? '';
            $personSurname = $device->person?->surname ?? '';
            $fullName = trim($personName.' '.$personSurname);
            $manufacturer = $device->device->manufacturer->name ?? '';

            // Get device details
            $deviceDetails = [
                'type' => $device->device_type,
                'manufacturer' => $manufacturer,
                'serial_number' => $device->device_serial,
            ];

            // Get tokens
            $tokens = $device->tokens->map(fn ($token): array => [
                'token' => $token->token ?? '',
                'amount' => $token->token_amount ?? 0,
                'type' => $token->token_type ?? '',
                'unit' => $token->token_unit ?? '',
                'created_at' => $this->convertUtcDateToTimezone($token->created_at),
            ])->all();

            // Get address details
            $address = null;
            if ($device->address) {
                $address = [
                    'city' => $device->address->city->name ?? '',
                    'street' => $device->address->street ?? '',
                ];
            }

            return [
                'id' => $device->device_id,
                'customer' => $fullName,
                'device_info' => $deviceDetails,
                'tokens' => $tokens,
                'address' => $address,
                'created_at' => $this->convertUtcDateToTimezone($device->created_at),
                'updated_at' => $this->convertUtcDateToTimezone($device->updated_at),
            ];
        });

        return $jsonDataTransform->all();
    }
}
