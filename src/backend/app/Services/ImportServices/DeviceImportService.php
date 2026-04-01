<?php

namespace App\Services\ImportServices;

use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\ConnectionGroup;
use App\Models\ConnectionType;
use App\Models\Device;
use App\Models\EBike;
use App\Models\GeographicalInformation;
use App\Models\MainSettings;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterType;
use App\Models\Person\Person;
use App\Models\SolarHomeSystem;
use App\Models\Tariff;
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

            $allFailed = count($imported) === 0 && count($failed) > 0;
            $partitioned = $this->partitionResults($imported);

            return [
                'success' => !$allFailed,
                'message' => $allFailed ? 'All device imports failed' : 'Devices imported successfully',
                'imported_count' => count($imported),
                'added_count' => $partitioned['added_count'],
                'modified_count' => $partitioned['modified_count'],
                'failed_count' => count($failed),
                'added' => $partitioned['added'],
                'modified' => $partitioned['modified'],
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
                    'type' => $this->mapDeviceTypeToManufacturerType($deviceType),
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

            $this->updateSpecificDevice($existingDevice, $manufacturer, $deviceInfo);
            $this->handleDeviceGeo($existingDevice, $deviceData);

            return [
                'success' => true,
                'action' => 'modified',
                'device' => [
                    'id' => $existingDevice->id,
                    'serial_number' => $existingDevice->device_serial,
                    'type' => $existingDevice->device_type,
                ],
            ];
        }

        // Create the type-specific device model
        $specificDevice = $this->createSpecificDevice($deviceType, $serialNumber, $manufacturer, $deviceInfo);

        // Create the polymorphic Device record
        $device = Device::query()->create([
            'person_id' => $personId,
            'device_type' => $deviceType,
            'device_id' => $specificDevice->id,
            'device_serial' => $serialNumber,
        ]);

        $this->handleDeviceGeo($device, $deviceData);

        return [
            'success' => true,
            'action' => 'added',
            'device' => [
                'id' => $device->id,
                'serial_number' => $device->device_serial,
                'type' => $device->device_type,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $deviceData
     */
    private function handleDeviceGeo(Device $device, array $deviceData): void {
        $geo = $deviceData['geo'] ?? null;
        if (!is_array($geo) || empty($geo['points'])) {
            return;
        }

        $points = $geo['points'];
        $existingGeo = $device->geo;

        if ($existingGeo instanceof GeographicalInformation) {
            $existingGeo->update(['points' => $points]);
        } else {
            $device->geo()->create(['points' => $points]);
        }
    }

    /**
     * @param array<string, mixed> $deviceInfo
     */
    private function updateSpecificDevice(Device $device, ?Manufacturer $manufacturer, array $deviceInfo): void {
        $specificDevice = $device->device;

        $updates = [];
        if ($manufacturer instanceof Manufacturer) {
            $updates['manufacturer_id'] = $manufacturer->id;
        }

        if ($specificDevice instanceof Meter) {
            if (!empty($deviceInfo['connection_type'])) {
                $updates['connection_type_id'] = $this->resolveConnectionTypeId($deviceInfo['connection_type']);
            }
            if (!empty($deviceInfo['connection_group'])) {
                $updates['connection_group_id'] = $this->resolveConnectionGroupId($deviceInfo['connection_group']);
            }
            if (!empty($deviceInfo['tariff'])) {
                $updates['tariff_id'] = $this->resolveTariffId($deviceInfo['tariff']);
            }
            if (isset($deviceInfo['meter_type'])) {
                $updates['meter_type_id'] = $this->resolveMeterTypeId($deviceInfo['meter_type']);
            }
        } elseif ($specificDevice instanceof SolarHomeSystem) {
            if (!empty($deviceInfo['appliance'])) {
                $updates['appliance_id'] = $this->resolveApplianceId($deviceInfo['appliance']);
            }
        } elseif (!empty($deviceInfo['appliance'])) {
            $updates['appliance_id'] = $this->resolveApplianceId($deviceInfo['appliance']);
        }

        if ($updates !== []) {
            $specificDevice->update($updates);
        }
    }

    /**
     * @param array<string, mixed> $deviceInfo
     */
    private function createSpecificDevice(string $type, string $serialNumber, ?Manufacturer $manufacturer, array $deviceInfo): Meter|SolarHomeSystem|EBike {
        $manufacturerId = $manufacturer?->id;

        $applianceId = in_array($type, ['solar_home_system', 'e_bike'])
            ? $this->resolveApplianceId($deviceInfo['appliance'] ?? '')
            : null;

        return match ($type) {
            'solar_home_system' => SolarHomeSystem::query()->create([
                'serial_number' => $serialNumber,
                'manufacturer_id' => $manufacturerId,
                'appliance_id' => $applianceId,
            ]),
            'e_bike' => EBike::query()->create([
                'serial_number' => $serialNumber,
                'manufacturer_id' => $manufacturerId,
                'appliance_id' => $applianceId,
            ]),
            default => Meter::query()->create([
                'serial_number' => $serialNumber,
                'manufacturer_id' => $manufacturerId,
                'in_use' => true,
                'meter_type_id' => $this->resolveMeterTypeId($deviceInfo['meter_type'] ?? null),
                'connection_type_id' => $this->resolveConnectionTypeId($deviceInfo['connection_type'] ?? ''),
                'connection_group_id' => $this->resolveConnectionGroupId($deviceInfo['connection_group'] ?? ''),
                'tariff_id' => $this->resolveTariffId($deviceInfo['tariff'] ?? ''),
            ]),
        };
    }

    /**
     * @param array<string, mixed>|null $meterTypeData
     */
    private function resolveMeterTypeId(?array $meterTypeData): int {
        if ($meterTypeData !== null) {
            $meterType = MeterType::query()->firstOrCreate([
                'online' => $meterTypeData['online'] ?? false,
                'phase' => $meterTypeData['phase'] ?? 1,
                'max_current' => $meterTypeData['max_current'] ?? 10,
            ]);

            return $meterType->id;
        }

        $meterType = MeterType::query()->first();
        if ($meterType === null) {
            $meterType = MeterType::query()->create([
                'online' => false,
                'phase' => 1,
                'max_current' => 10,
            ]);
        }

        return $meterType->id;
    }

    private function resolveConnectionTypeId(string $name): int {
        if ($name !== '') {
            $connectionType = ConnectionType::query()->where('name', $name)->first();
            if ($connectionType !== null) {
                return $connectionType->id;
            }
        }

        $connectionType = ConnectionType::query()->first();
        if ($connectionType === null) {
            $connectionType = ConnectionType::query()->create(['name' => $name !== '' ? $name : 'Default']);
        }

        return $connectionType->id;
    }

    private function resolveConnectionGroupId(string $name): int {
        if ($name !== '') {
            $connectionGroup = ConnectionGroup::query()->where('name', $name)->first();
            if ($connectionGroup !== null) {
                return $connectionGroup->id;
            }
        }

        $connectionGroup = ConnectionGroup::query()->first();
        if ($connectionGroup === null) {
            $connectionGroup = ConnectionGroup::query()->create(['name' => $name !== '' ? $name : 'Default']);
        }

        return $connectionGroup->id;
    }

    private function resolveTariffId(string $name): int {
        if ($name !== '') {
            $tariff = Tariff::query()->where('name', $name)->first();
            if ($tariff !== null) {
                return $tariff->id;
            }
        }

        $tariff = Tariff::query()->first();
        if ($tariff === null) {
            $mainSettings = MainSettings::query()->first();
            $currency = $mainSettings !== null ? $mainSettings->currency : '€';

            $tariff = Tariff::query()->create([
                'name' => $name !== '' ? $name : 'Default',
                'price' => 0,
                'currency' => $currency,
            ]);
        }

        return $tariff->id;
    }

    private function mapDeviceTypeToManufacturerType(string $deviceType): string {
        return match ($deviceType) {
            'solar_home_system' => 'shs',
            'e_bike' => 'e-bike',
            default => 'meter',
        };
    }

    private function resolveApplianceId(string $name): int {
        if ($name !== '') {
            $appliance = Appliance::query()->where('name', $name)->first();
            if ($appliance !== null) {
                return $appliance->id;
            }
        }

        $appliance = Appliance::query()->first();
        if ($appliance === null) {
            $applianceType = ApplianceType::query()->first();
            $applianceTypeId = $applianceType !== null ? $applianceType->id : ApplianceType::query()->create(['name' => 'Solar Home System'])->id;

            $appliance = Appliance::query()->create([
                'name' => $name !== '' ? $name : 'Default Appliance',
                'appliance_type_id' => $applianceTypeId,
                'price' => 0,
            ]);
        }

        return $appliance->id;
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
