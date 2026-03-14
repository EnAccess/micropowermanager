<?php

namespace App\Services;

use App\Models\Meter\Meter;
use App\Models\Person\Person;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * @implements IBaseService<Meter>
 */
class MeterService implements IBaseService {
    public function __construct(
        private Meter $meter,
    ) {}

    public function getBySerialNumber(string $serialNumber): ?Meter {
        return $this->meter->newQuery()->with([
            'tariff',
            'device.person',
            'meterType',
            'connectionType',
            'connectionGroup',
            'manufacturer',
            'tokens.transaction',
        ])->where('serial_number', $serialNumber)->first();
    }

    public function getPersonByDeviceSerialNumber(string $serialNumber): ?Person {
        $meter = $this->meter->newQuery()->with(
            ['device.person']
        )->where('serial_number', $serialNumber)->first();

        return $meter?->device->person;
    }

    /**
     * @return LengthAwarePaginator<int, Meter>
     */
    public function search(string $term, int $paginate): LengthAwarePaginator {
        return $this->meter->newQuery()->with(['meterType', 'tariff'])
            ->whereHas('tariff', fn ($q) => $q->where('name', 'LIKE', '%'.$term.'%'))
            ->orWhere(
                'serial_number',
                'LIKE',
                '%'.$term.'%'
            )->paginate($paginate);
    }

    public function getMeterWithAllRelations(int $meterId): ?Meter {
        return $this->meter->newQuery()->with([
            'tariff',
            'device.geo',
            'meterType',
        ])->find($meterId);
    }

    /**
     * @return Collection<int, Meter>
     */
    public function getUsedMetersGeoWithAccessRatePayments(): Collection {
        return $this->meter->newQuery()->with(['device.geo', 'accessRatePayment'])->where('in_use', 1)->get();
    }

    /**
     * @param array<int> $villages
     *
     * @return Collection<int, Meter>
     */
    public function getUsedMetersGeoWithAccessRatePaymentsInVillages(array $villages): Collection {
        return $this->meter->newQuery()->with(['device.geo', 'accessRatePayment'])
            ->whereHas(
                'device',
                fn ($q) => $q->whereHas(
                    'address',
                    function ($q) use ($villages) {
                        $q->whereIn('village_id', $villages);
                    }
                )
            )
            ->where('in_use', 1)->get();
    }

    /**
     * @param array<string, mixed> $meterData
     */
    public function create(array $meterData): Meter {
        return $this->meter->newQuery()->create([
            'serial_number' => $meterData['serial_number'],
            'meter_type_id' => $meterData['meter_type_id'],
            'in_use' => $meterData['in_use'],
            'manufacturer_id' => $meterData['manufacturer_id'],
            'connection_group_id' => $meterData['connection_group_id'],
            'connection_type_id' => $meterData['connection_type_id'],
            'tariff_id' => $meterData['tariff_id'],
        ]);
    }

    public function getById(int $meterId): ?Meter {
        return $this->meter->newQuery()->with([
            'tariff',
            'device',
            'meterType',
            'connectionType',
            'connectionGroup',
            'manufacturer',
        ])->find($meterId);
    }

    public function delete($meter): ?bool {
        return $meter->delete();
    }

    /**
     * @return LengthAwarePaginator<int, Meter>
     */
    public function getAll(?int $limit = null, ?bool $inUse = null): LengthAwarePaginator {
        $query = $this->meter->newQuery()->with(['meterType', 'tariff']);

        if ($inUse !== null) {
            $query->where('in_use', $inUse);
        }

        return $query->paginate($limit);
    }

    public function update($meter, array $meterData): Meter {
        $meter->update($meterData);
        $meter->fresh();

        return $meter;
    }

    /**
     * @return Collection<int, Meter>
     */
    public function getNumberOfConnectionTypes(): Collection {
        return $this->meter->newQuery()
            ->join('connection_types', 'meters.connection_type_id', '=', 'connection_types.id')
            ->select('connection_type_id', DB::raw('count(*) as total'))
            ->groupBy('connection_type_id')
            ->get();
    }
}
