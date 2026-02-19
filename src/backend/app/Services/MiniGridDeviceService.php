<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Meter\Meter;
use Illuminate\Support\Collection;

class MiniGridDeviceService {
    public function __construct(private Device $device) {}

    /**
     * @return Collection<int, mixed>
     */
    public function getMetersByMiniGridId(int $miniGridId) {
        return $this->device->newQuery()
            ->with('device')
            ->whereHasMorph(
                'device',
                Meter::class
            )
            ->whereHas('person', function ($q) use ($miniGridId) {
                $q->whereHas('addresses', function ($q) use ($miniGridId) {
                    $q->where('is_primary', 1)
                        ->whereHas('city', function ($q) use ($miniGridId) {
                            $q->where('mini_grid_id', $miniGridId);
                        });
                });
            })
            ->get()->pluck('device');
    }

    /**
     * @return Collection<int, Device>
     */
    public function getDevicesByMiniGridId(int $miniGridId): Collection {
        return $this->device->newQuery()
            ->with(['device', 'geo', 'person.addresses.city'])
            ->whereHasMorph(
                'device',
                '*'
            )
            ->whereHas('person', function ($q) use ($miniGridId) {
                $q->whereHas('addresses', function ($q) use ($miniGridId) {
                    $q->where('is_primary', 1)
                        ->whereHas('city', function ($q) use ($miniGridId) {
                            $q->where('mini_grid_id', $miniGridId);
                        });
                });
            })
            ->get();
    }
}
