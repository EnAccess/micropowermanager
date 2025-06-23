<?php

namespace MPM\Device;

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
            ->whereHas('address', fn ($q) => $q->whereHas('city', fn ($q) => $q->where('mini_grid_id', $miniGridId)))
            ->get()->pluck('device');
    }

    /**
     * @return Collection<int, Device>
     */
    public function getDevicesByMiniGridId(int $miniGridId): Collection {
        return $this->device->newQuery()
            ->with(['device', 'address.geo'])
            ->whereHasMorph(
                'device',
                '*'
            )
            ->whereHas('address', fn ($q) => $q->whereHas('city', fn ($q) => $q->where('mini_grid_id', $miniGridId)))
            ->get();
    }
}
