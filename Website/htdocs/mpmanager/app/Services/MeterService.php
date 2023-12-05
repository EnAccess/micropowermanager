<?php

namespace App\Services;

use App\Http\Requests\MeterRequest;
use App\Models\City;
use App\Models\Meter\Meter;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

use function count;

class MeterService implements IBaseService
{
    public function __construct(private Meter $meter)
    {
    }

    public function getBySerialNumber($serialNumber)
    {
        return $this->meter->newQuery()->with([
            'tariff',
            'device.person',
            'meterType',
            'connectionType',
            'connectionGroup',
            'manufacturer',
            'tokens.transaction'
        ])->where('serial_number', $serialNumber)->first();
    }

    public function search($term, $paginate): LengthAwarePaginator
    {
        return $this->meter->newQuery()->with(['meterType', 'tariff'])
            ->whereHas('tariff', fn($q) => $q->where('name', 'LIKE', '%' . $term . '%'))
            ->orWhere(
                'serial_number',
                'LIKE',
                '%' . $term . '%'
            )->paginate($paginate);
    }

    public function getMeterWithAllRelations(int $meterId)
    {
        return $this->meter->newQuery()->with([
            'tariff',
            'device.geo',
            'meterType'
        ])->find($meterId);
    }

    public function getUsedMetersGeoWithAccessRatePayments(): Collection|array
    {
        return $this->meter->newQuery()->with(['device.geo', 'accessRatePayment'])->where('in_use', 1)->get();
    }

    public function getUsedMetersGeoWithAccessRatePaymentsInCities($cities): Collection|array
    {
        return $this->meter->newQuery()->with(['device.geo', 'accessRatePayment'])
            ->whereHas(
                'device',
                fn($q) => $q->whereHas(
                    'address',
                    function ($q) use ($cities) {
                        $q->whereIn('city_id', $cities);
                    }
                )

            )
            ->where('in_use', 1)->get();
    }

    public function create($meterData)
    {
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

    public function getById($meterId)
    {
        return $this->meter->newQuery()->with([
            'tariff',
            'device',
            'meterType',
            'connectionType',
            'connectionGroup',
            'manufacturer'
        ])->find($meterId);
    }

    public function delete($meter)
    {
        return $meter->delete();
    }

    public function getAll($limit = null, $inUse = true)
    {
        if (isset($inUse)) {
            return $this->meter->newQuery()->with(['meterType', 'tariff'])->where(
                'in_use',
                $inUse
            )->paginate($limit);
        }
        return $this->meter->newQuery()->with(['meterType', 'tariff'])->paginate($limit);
    }

    public function update($meter, $meterData)
    {
        $meter->update($meterData);
        $meter->fresh();

        return $meter;
    }
}
