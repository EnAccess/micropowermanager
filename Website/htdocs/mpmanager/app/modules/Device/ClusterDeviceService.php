<?php

namespace MPM\Device;

use App\Models\Cluster;
use App\Models\Device;
use App\Models\Meter\Meter;
use App\Services\IBaseService;

class ClusterDeviceService
{
    public function __construct(private Cluster $cluster, private Device $device)
    {
    }

    public function getCountByClusterId($clusterId): int
    {
        return $this->device->newQuery()
            ->whereHas('address', fn ($q) => $q->whereHas('city', fn ($q) => $q->where('cluster_id', $clusterId)))
            ->count();
    }


    public function getByClusterId($clusterId)
    {
        return $this->device->newQuery()
            ->with('device')
            ->whereHas('address', fn ($q) => $q->whereHas('city', fn ($q) => $q->where('cluster_id', $clusterId)))
            ->get();
    }

    public function getMetersByClusterId($clusterId)
    {
        return $this->device->newQuery()
            ->whereHasMorph(
                'device',
                Meter::class
            )
            ->whereHas('address', fn ($q) => $q->whereHas('city', fn ($q) => $q->where('cluster_id', $clusterId)))
            ->get();
    }
}
