<?php

namespace MPM\Device;

use App\Models\Device;
use App\Models\Meter\Meter;
use App\Models\MiniGrid;
use Illuminate\Support\Collection;

class MiniGridDeviceService
{
    public function __construct(private Device $device, private MiniGrid $miniGrid)
    {
    }


    public function getMetersByMiniGridId($miniGridId)
    {
        return $this->device->newQuery()
            ->with('device')
            ->whereHasMorph(
                'device',
                Meter::class
            )
            ->whereHas('address', fn($q) => $q->whereHas('city', fn($q) => $q->where('mini_grid_id', $miniGridId)))
            ->get()->pluck('device');
    }

    public function getDevicesByMiniGridId($miniGridId): Collection
    {
        return $this->device->newQuery()
            ->with(['device','address.geo'])
            ->whereHasMorph(
                'device',
                '*'
            )
            ->whereHas('address', fn($q) => $q->whereHas('city', fn($q) => $q->where('mini_grid_id', $miniGridId)))
            ->get();
    }
}
