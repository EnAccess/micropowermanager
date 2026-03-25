<?php

namespace App\Services;

use App\Models\Device;
use App\Models\EBike;
use App\Models\Meter\Meter;
use App\Models\SolarHomeSystem;
use App\Services\Interfaces\IAssociative;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<Device>
 * @implements IAssociative<Device>
 */
class DeviceService implements IBaseService, IAssociative {
    public function __construct(
        private Device $device,
    ) {}

    public function make(mixed $deviceData): Device {
        return $this->device->newQuery()->make([
            'person_id' => $deviceData['person_id'],
            'device_serial' => $deviceData['device_serial'],
        ]);
    }

    public function getBySerialNumber(string $serialNumber): ?Device {
        return $this->device->newQuery()
            ->with(['geo', 'device.manufacturer', 'person.addresses.city'])
            ->where('device_serial', $serialNumber)
            ->first();
    }

    public function save($device): bool {
        return $device->save();
    }

    public function getById(int $id): Device {
        throw new \Exception('Method getById() not yet implemented.');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Device {
        throw new \Exception('Method create() not yet implemented.');
    }

    /**
     * @param Device               $device
     * @param array<string, mixed> $deviceData
     */
    public function update($device, array $deviceData): Device {
        $device->update($deviceData);
        $device->fresh();

        return $device;
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return Collection<int, Device>|LengthAwarePaginator<int, Device>
     */
    public function getAll(?int $limit = null, array $filters = []): Collection|LengthAwarePaginator {
        $query = $this->device->newQuery()
            ->with(['person', 'device'])
            ->when($filters['device_type'] ?? null, fn ($q, $type) => $q->where('device_type', $type))
            ->when($filters['appliance_id'] ?? null, fn ($q, $applianceId) => $q->whereHasMorph(
                'device',
                [SolarHomeSystem::class, EBike::class],
                fn ($morph) => $morph->where('appliance_id', $applianceId),
            ))
            ->unless(empty($filters['unassigned']), fn ($q) => $q->whereNull('person_id'))
            ->latest();

        return $limit ? $query->paginate($limit) : $query->get();
    }

    /**
     * @return Collection<int, Device>
     */
    public function getAllForExport(?string $miniGridName = null, ?string $villageName = null, ?string $deviceType = null, ?string $manufacturerName = null): Collection {
        /** @var array<string, \Closure|string> $relations */
        $relations = [
            'person',
            'device' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    Meter::class => ['manufacturer', 'meterType', 'connectionType', 'connectionGroup', 'tariff'],
                    SolarHomeSystem::class => ['manufacturer', 'appliance'],
                    EBike::class => ['manufacturer', 'appliance'],
                ]);
            },
            'person.addresses.city',
            'tokens',
            'appliance.applianceType',
        ];
        $query = $this->device->newQuery()->with($relations);

        if ($miniGridName) {
            $query->whereHas('person', function ($q) use ($miniGridName) {
                $q->whereHas('addresses', function ($q) use ($miniGridName) {
                    $q->where('is_primary', 1)
                        ->whereHas('city', function ($q) use ($miniGridName) {
                            $q->whereHas('miniGrid', function ($q) use ($miniGridName) {
                                $q->where('name', 'LIKE', '%'.$miniGridName.'%');
                            });
                        });
                });
            });
        }

        if ($villageName) {
            $query->whereHas('person', function ($q) use ($villageName) {
                $q->whereHas('addresses', function ($q) use ($villageName) {
                    $q->where('is_primary', 1)
                        ->whereHas('city', function ($q) use ($villageName) {
                            $q->where('name', 'LIKE', '%'.$villageName.'%');
                        });
                });
            });
        }

        if ($deviceType) {
            $query->where('device_type', $deviceType);
        }

        if ($manufacturerName) {
            $query->whereHasMorph('device', '*', function ($q) use ($manufacturerName) {
                $q->whereHas('manufacturer', function ($q) use ($manufacturerName) {
                    $q->where('name', 'LIKE', '%'.$manufacturerName.'%');
                });
            });
        }

        return $query->get();
    }

    /**
     * @param array{lat: float|string, lon: float|string} $addressData
     */
    public function updateGeoInformation(Device $device, array $addressData): Device {
        $points = $addressData['lat'].','.$addressData['lon'];
        $device->geo()->update([
            'points' => $points,
        ]);

        return $device->fresh();
    }
}
