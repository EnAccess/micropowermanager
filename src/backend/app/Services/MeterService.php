<?php

namespace App\Services;

use App\Models\Meter\Meter;
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

    /**
     * @return LengthAwarePaginator<Meter>
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
            'device.device.geo',
            'meterType',
        ])->find($meterId);
    }

    /**
     * @return Collection<int, Meter>
     */
    public function getUsedMetersGeoWithAccessRatePayments(): Collection {
        return $this->meter->newQuery()->with(['device.device.geo', 'accessRatePayment'])->where('in_use', 1)->get();
    }

    /**
     * @param array<int> $cities
     *
     * @return Collection<int, Meter>
     */
    public function getUsedMetersGeoWithAccessRatePaymentsInCities(array $cities): Collection {
        return $this->meter->newQuery()->with(['device.device.geo', 'accessRatePayment'])
            ->whereHas(
                'device',
                fn ($q) => $q->whereHas(
                    'address',
                    function ($q) use ($cities) {
                        $q->whereIn('city_id', $cities);
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
     * @return LengthAwarePaginator<Meter>
     */
    public function getAll(?int $limit = null, ?bool $inUse = true): LengthAwarePaginator {
        if ($inUse !== null) {
            return $this->meter->newQuery()->with(['meterType', 'tariff'])->where(
                'in_use',
                $inUse
            )->paginate($limit);
        }

        return $this->meter->newQuery()->with(['meterType', 'tariff'])->paginate($limit);
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
        return $this->meter->newQuery()->join('connection_types', 'meters.connection_type_id', '=', 'connection_types.id')
            ->select('connection_type_id', DB::raw('count(*) as total'))
            ->groupBy('connection_type_id')
            ->get();
    }
}
