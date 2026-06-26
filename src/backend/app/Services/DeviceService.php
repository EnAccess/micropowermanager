<?php

namespace App\Services;

use App\Models\Device;
use App\Models\EBike;
use App\Models\GeographicalInformation;
use App\Models\Meter\Meter;
use App\Models\SolarHomeSystem;
use App\Services\Interfaces\IAssociative;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<Device>
 * @implements IAssociative<Device>
 */
class DeviceService implements IBaseService, IAssociative {
    /** @use HasCrudOperations<Device> */
    use HasCrudOperations;

    public function __construct(
        private Device $device,
    ) {}

    protected function crudModel(): Device {
        return $this->device;
    }

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

    /**
     * @param array<string, mixed> $filters
     *
     * @return Collection<int, Device>|LengthAwarePaginator<int, Device>
     */
    public function getAll(?int $limit = null, array $filters = []): Collection|LengthAwarePaginator {
        $query = $this->device->newQuery()
            ->with(['person', 'device'])
            ->when($filters['device_type'] ?? null, fn ($q, $type) => $q->where('device_type', $type))
            ->when($filters['serial'] ?? null, fn ($q, $serial) => $q->where('device_serial', 'LIKE', "%{$serial}%"))
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
     * Unassigned devices (no owner yet) of the given morph class whose
     * underlying unit belongs to the given appliance.
     *
     * @param class-string $deviceClass one of SolarHomeSystem::class or EBike::class
     *
     * @return Collection<int, Device>
     */
    public function getUnassignedByAppliance(int $applianceId, string $deviceClass): Collection {
        /** @var array<string, \Closure|string> $relations */
        $relations = [
            'device' => function (MorphTo $morphTo) use ($deviceClass): void {
                $morphTo->morphWith([$deviceClass => ['manufacturer', 'appliance']]);
            },
        ];

        return $this->device->newQuery()
            ->with($relations)
            ->whereNull('person_id')
            ->whereHasMorph(
                'device',
                [$deviceClass],
                fn ($morph) => $morph->where('appliance_id', $applianceId),
            )
            ->latest()
            ->get();
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
            'geo',
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
        $this->assignLocation($device, GeographicalInformation::makePoint((float) $addressData['lat'], (float) $addressData['lon']));

        return $device->fresh();
    }

    public function assignLocation(Device $device, ?object $geoJson): void {
        if ($geoJson === null) {
            return;
        }

        $device->geo()->updateOrCreate([], ['geo_json' => $geoJson]);
    }
}
