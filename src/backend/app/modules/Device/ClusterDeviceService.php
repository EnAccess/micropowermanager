<?php

namespace MPM\Device;

use App\Models\Device;
use App\Models\Meter\Meter;
use Illuminate\Support\Collection;

class ClusterDeviceService {
    public function __construct(private Device $device) {}

    public function getCountByClusterId(int $clusterId): int {
        return $this->device->newQuery()
            ->whereHas('address', fn ($q) => $q->whereHas('city', fn ($q) => $q->where('cluster_id', $clusterId)))
            ->count();
    }

    /**
     * @return Collection<int, Device>
     */
    public function getByClusterId(int $clusterId): Collection {
        return $this->device->newQuery()
            ->with('device')
            ->whereHas('address', fn ($q) => $q->whereHas('city', fn ($q) => $q->where('cluster_id', $clusterId)))
            ->get();
    }

    /**
     * @return Collection<int, Device>
     */
    public function getMetersByClusterId(int $clusterId): Collection {
        return $this->device->newQuery()
            ->whereHasMorph(
                'device',
                Meter::class
            )
            ->whereHas('address', fn ($q) => $q->whereHas('city', fn ($q) => $q->where('cluster_id', $clusterId)))
            ->get();
    }
}
