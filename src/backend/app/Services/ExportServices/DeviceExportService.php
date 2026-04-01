<?php

namespace App\Services\ExportServices;

use App\Models\Device;
use App\Models\Meter\Meter;
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
            $personName = $device->person->name ?? '';
            $personSurname = $device->person->surname ?? '';
            $fullName = trim($personName.' '.$personSurname);
            $primaryAddress = $device->person?->addresses->where('is_primary', 1)->first();
            $address = $primaryAddress?->city->name ?? '';

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
            $personName = $device->person->name ?? '';
            $personSurname = $device->person->surname ?? '';
            $fullName = trim($personName.' '.$personSurname);
            $manufacturer = $device->device->manufacturer->name ?? '';

            // Get device details
            $deviceDetails = [
                'type' => $device->device_type,
                'manufacturer' => $manufacturer,
                'serial_number' => $device->device_serial,
            ];

            // Include meter-specific details
            if ($device->device_type === 'meter' && $device->device instanceof Meter) {
                $meter = $device->device;
                $meterType = $meter->meterType;
                if ($meterType !== null) {
                    $deviceDetails['meter_type'] = [
                        'online' => $meterType->online,
                        'phase' => $meterType->phase,
                        'max_current' => $meterType->max_current,
                    ];
                }
                $deviceDetails['connection_type'] = $meter->connectionType->name ?? '';
                $deviceDetails['connection_group'] = $meter->connectionGroup->name ?? '';
                $deviceDetails['tariff'] = $meter->tariff->name ?? '';
            }

            // Include appliance name for SHS and e-bikes
            if (in_array($device->device_type, ['solar_home_system', 'e_bike'])) {
                $deviceDetails['appliance'] = $device->device->appliance->name ?? '';
            }

            // Get tokens
            $tokens = $device->tokens->map(fn ($token): array => [
                'token' => $token->token ?? '',
                'amount' => $token->token_amount ?? 0,
                'type' => $token->token_type ?? '',
                'unit' => $token->token_unit ?? '',
                'created_at' => $this->convertUtcDateToTimezone($token->created_at),
            ])->all();

            // Get geo information
            $geo = null;
            if ($device->geo !== null) {
                $geo = [
                    'points' => $device->geo->points,
                ];
            }

            return [
                'id' => $device->device_id,
                'customer' => $fullName,
                'device_info' => $deviceDetails,
                'tokens' => $tokens,
                'geo' => $geo,
                'created_at' => $this->convertUtcDateToTimezone($device->created_at),
                'updated_at' => $this->convertUtcDateToTimezone($device->updated_at),
            ];
        });

        return $jsonDataTransform->all();
    }
}
