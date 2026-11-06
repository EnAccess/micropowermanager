<?php

namespace App\Services\ImportServices;

use App\Enums\DeviceType;
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

/**
 * @extends AbstractImportService<DeviceImportItem>
 */
class DeviceImportService extends AbstractImportService {
    /**
     * @param list<DeviceImportItem> $data
     */
    public function import(array $data): ImportResult {
        $imported = [];
        $failed = [];

        DB::connection('tenant')->beginTransaction();

        try {
            foreach ($data as $item) {
                try {
                    $result = $this->importDevice($item);
                    if ($result['success']) {
                        $imported[] = $result['device'];
                    } else {
                        $failed[] = [
                            'serial_number' => $item->deviceInfo->serialNumber,
                            'errors' => $result['errors'],
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('Error importing device', [
                        'serial_number' => $item->deviceInfo->serialNumber,
                        'error' => $e->getMessage(),
                    ]);
                    $failed[] = [
                        'serial_number' => $item->deviceInfo->serialNumber,
                        'errors' => ['import' => $e->getMessage()],
                    ];
                }
            }

            DB::connection('tenant')->commit();

            $allFailed = count($imported) === 0 && count($failed) > 0;
            $partitioned = $this->partitionResults($imported);

            return new ImportResult(
                message: $allFailed ? 'All device imports failed' : 'Devices imported successfully',
                added: $partitioned['added'],
                modified: $partitioned['modified'],
                failed: $failed,
            );
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            $this->throwTransactionFailure('devices', $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function importDevice(DeviceImportItem $item): array {
        $info = $item->deviceInfo;
        $serialNumber = $info->serialNumber;
        $deviceType = $info->type;

        // Resolve manufacturer
        $manufacturer = null;
        if ($info->manufacturer !== null && $info->manufacturer !== '') {
            $manufacturer = Manufacturer::query()->where('name', $info->manufacturer)->first();
            if ($manufacturer === null) {
                $manufacturer = Manufacturer::query()->create([
                    'name' => $info->manufacturer,
                    'type' => $this->mapDeviceTypeToManufacturerType($deviceType),
                ]);
            }
        }

        // Resolve customer by full name if provided
        $personId = null;
        if ($item->customer !== null && $item->customer !== '') {
            $nameParts = explode(' ', $item->customer, 2);
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

            $this->updateSpecificDevice($existingDevice, $manufacturer, $info);
            $this->handleDeviceGeo($existingDevice, $item->geoJson);

            return [
                'success' => true,
                'action' => 'modified',
                'device' => [
                    'id' => $existingDevice->id,
                    'serial_number' => $existingDevice->device_serial,
                    'type' => $existingDevice->device_type,
                    'action' => 'modified',
                ],
            ];
        }

        // Create the type-specific device model
        $specificDevice = $this->createSpecificDevice($deviceType, $serialNumber, $manufacturer, $info);

        // Create the polymorphic Device record
        $device = Device::query()->create([
            'person_id' => $personId,
            'device_type' => $deviceType,
            'device_id' => $specificDevice->id,
            'device_serial' => $serialNumber,
        ]);

        $this->handleDeviceGeo($device, $item->geoJson);

        return [
            'success' => true,
            'action' => 'added',
            'device' => [
                'id' => $device->id,
                'serial_number' => $device->device_serial,
                'type' => $device->device_type,
                'action' => 'added',
            ],
        ];
    }

    /**
     * @param array<string, mixed>|null $geoJson
     */
    private function handleDeviceGeo(Device $device, ?array $geoJson): void {
        if ($geoJson === null || $geoJson === []) {
            return;
        }

        $attributes = ['geo_json' => $geoJson];
        $existingGeo = $device->geo;

        if ($existingGeo instanceof GeographicalInformation) {
            $existingGeo->update($attributes);
        } else {
            $device->geo()->create($attributes);
        }
    }

    private function updateSpecificDevice(Device $device, ?Manufacturer $manufacturer, DeviceInfoItem $info): void {
        $specificDevice = $device->device;

        $updates = [];
        if ($manufacturer instanceof Manufacturer) {
            $updates['manufacturer_id'] = $manufacturer->id;
        }

        if ($specificDevice instanceof Meter) {
            if ($info->connectionType !== null && $info->connectionType !== '') {
                $updates['connection_type_id'] = $this->resolveConnectionTypeId($info->connectionType);
            }
            if ($info->connectionGroup !== null && $info->connectionGroup !== '') {
                $updates['connection_group_id'] = $this->resolveConnectionGroupId($info->connectionGroup);
            }
            if ($info->tariff !== null && $info->tariff !== '') {
                $updates['tariff_id'] = $this->resolveTariffId($info->tariff);
            }
            if ($info->meterType instanceof MeterTypeItem) {
                $updates['meter_type_id'] = $this->resolveMeterTypeId($info->meterType);
            }
        } elseif ($specificDevice instanceof SolarHomeSystem) {
            if ($info->appliance !== null && $info->appliance !== '') {
                $updates['appliance_id'] = $this->resolveApplianceId($info->appliance);
            }
        } elseif ($info->appliance !== null && $info->appliance !== '') {
            $updates['appliance_id'] = $this->resolveApplianceId($info->appliance);
        }

        if ($updates !== []) {
            $specificDevice->update($updates);
        }
    }

    private function createSpecificDevice(string $type, string $serialNumber, ?Manufacturer $manufacturer, DeviceInfoItem $info): Meter|SolarHomeSystem|EBike {
        $manufacturerId = $manufacturer?->id;
        // Unknown types fall back to meter, matching the pre-enum import behavior.
        $deviceType = DeviceType::tryFrom($type) ?? DeviceType::Meter;

        $applianceId = in_array($deviceType, [DeviceType::SolarHomeSystem, DeviceType::EBike], true)
            ? $this->resolveApplianceId($info->appliance ?? '')
            : null;

        return match ($deviceType) {
            DeviceType::SolarHomeSystem => SolarHomeSystem::query()->create([
                'serial_number' => $serialNumber,
                'manufacturer_id' => $manufacturerId,
                'appliance_id' => $applianceId,
            ]),
            DeviceType::EBike => EBike::query()->create([
                'serial_number' => $serialNumber,
                'manufacturer_id' => $manufacturerId,
                'appliance_id' => $applianceId,
            ]),
            DeviceType::Meter => Meter::query()->create([
                'serial_number' => $serialNumber,
                'manufacturer_id' => $manufacturerId,
                'in_use' => true,
                'meter_type_id' => $this->resolveMeterTypeId($info->meterType),
                'connection_type_id' => $this->resolveConnectionTypeId($info->connectionType ?? ''),
                'connection_group_id' => $this->resolveConnectionGroupId($info->connectionGroup ?? ''),
                'tariff_id' => $this->resolveTariffId($info->tariff ?? ''),
            ]),
        };
    }

    private function resolveMeterTypeId(?MeterTypeItem $meterTypeItem): int {
        if ($meterTypeItem instanceof MeterTypeItem) {
            $meterType = MeterType::query()->firstOrCreate([
                'online' => $meterTypeItem->online,
                'phase' => $meterTypeItem->phase,
                'max_current' => $meterTypeItem->maxCurrent,
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
        return match (DeviceType::tryFrom($deviceType)) {
            DeviceType::SolarHomeSystem => 'shs',
            DeviceType::EBike => 'e-bike',
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
}
