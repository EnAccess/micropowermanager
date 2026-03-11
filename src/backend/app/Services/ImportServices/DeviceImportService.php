<?php

namespace App\Services\ImportServices;

use App\Models\Device;
use App\Models\EBike;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Person\Person;
use App\Models\SolarHomeSystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeviceImportService extends AbstractImportService {
    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function import(array $data): array {
        $importData = $data;
        if (isset($data['data']) && is_array($data['data'])) {
            $importData = $data['data'];
        }

        $errors = $this->validate($importData);
        if ($errors !== []) {
            return [
                'success' => false,
                'errors' => $errors,
            ];
        }

        $imported = [];
        $failed = [];

        DB::connection('tenant')->beginTransaction();

        try {
            foreach ($importData as $deviceData) {
                try {
                    $result = $this->importDevice($deviceData);
                    if ($result['success']) {
                        $imported[] = $result['device'];
                    } else {
                        $serialNumber = $deviceData['device_info']['serial_number'] ?? 'unknown';
                        $failed[] = [
                            'serial_number' => $serialNumber,
                            'errors' => $result['errors'],
                        ];
                    }
                } catch (\Exception $e) {
                    $serialNumber = $deviceData['device_info']['serial_number'] ?? 'unknown';
                    Log::error('Error importing device', [
                        'serial_number' => $serialNumber,
                        'error' => $e->getMessage(),
                    ]);
                    $failed[] = [
                        'serial_number' => $serialNumber,
                        'errors' => ['import' => $e->getMessage()],
                    ];
                }
            }

            DB::connection('tenant')->commit();

            return [
                'success' => true,
                'message' => 'Devices imported successfully',
                'imported_count' => count($imported),
                'failed_count' => count($failed),
                'imported' => $imported,
                'failed' => $failed,
            ];
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            Log::error('Error during device import transaction', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'errors' => ['transaction' => 'Failed to import devices: '.$e->getMessage()],
            ];
        }
    }

    /**
     * @param array<string, mixed> $deviceData
     *
     * @return array<string, mixed>
     */
    private function importDevice(array $deviceData): array {
        $deviceInfo = $deviceData['device_info'];
        $serialNumber = $deviceInfo['serial_number'];
        $deviceType = $deviceInfo['type'] ?? 'meter';
        $manufacturerName = $deviceInfo['manufacturer'] ?? null;

        // Resolve manufacturer
        $manufacturer = null;
        if ($manufacturerName !== null && $manufacturerName !== '') {
            $manufacturer = Manufacturer::query()->where('name', $manufacturerName)->first();
            if ($manufacturer === null) {
                $manufacturer = Manufacturer::query()->create([
                    'name' => $manufacturerName,
                    'type' => $deviceType,
                ]);
            }
        }

        // Resolve customer by full name if provided
        $personId = null;
        if (!empty($deviceData['customer'])) {
            $nameParts = explode(' ', $deviceData['customer'], 2);
            $name = $nameParts[0];
            $surname = $nameParts[1] ?? '';

            $person = Person::query()
                ->where('name', $name)
                ->where('surname', $surname)
                ->first();

            if ($person !== null) {
                $personId = $person->id;
            }
        }

        // Check if device already exists by serial number
        $existingDevice = Device::query()->where('device_serial', $serialNumber)->first();
        if ($existingDevice !== null) {
            if ($personId !== null) {
                $existingDevice->update(['person_id' => $personId]);
            }

            return [
                'success' => true,
                'device' => [
                    'id' => $existingDevice->id,
                    'serial_number' => $existingDevice->device_serial,
                    'type' => $existingDevice->device_type,
                ],
            ];
        }

        // Create the type-specific device model
        $specificDevice = $this->createSpecificDevice($deviceType, $serialNumber, $manufacturer);

        // Create the polymorphic Device record
        $device = Device::query()->create([
            'person_id' => $personId,
            'device_type' => $deviceType,
            'device_id' => $specificDevice->id,
            'device_serial' => $serialNumber,
        ]);

        return [
            'success' => true,
            'device' => [
                'id' => $device->id,
                'serial_number' => $device->device_serial,
                'type' => $device->device_type,
            ],
        ];
    }

    private function createSpecificDevice(string $type, string $serialNumber, ?Manufacturer $manufacturer): Meter|SolarHomeSystem|EBike {
        $manufacturerId = $manufacturer?->id;

        return match ($type) {
            'solar_home_system' => SolarHomeSystem::query()->create([
                'serial_number' => $serialNumber,
                'manufacturer_id' => $manufacturerId,
            ]),
            'e_bike' => EBike::query()->create([
                'serial_number' => $serialNumber,
                'manufacturer_id' => $manufacturerId,
            ]),
            default => Meter::query()->create([
                'serial_number' => $serialNumber,
                'manufacturer_id' => $manufacturerId,
                'in_use' => true,
            ]),
        };
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, string>
     */
    public function validate(array $data): array {
        $errors = [];

        foreach ($data as $index => $deviceData) {
            if (!is_array($deviceData)) {
                $errors["device_{$index}"] = 'Device data must be an array';
                continue;
            }

            if (!isset($deviceData['device_info']) || !is_array($deviceData['device_info'])) {
                $errors["device_{$index}.device_info"] = 'Device info is required and must be an array';
                continue;
            }

            if (empty($deviceData['device_info']['serial_number'])) {
                $errors["device_{$index}.device_info.serial_number"] = 'Serial number is required';
            }
        }

        return $errors;
    }
}
