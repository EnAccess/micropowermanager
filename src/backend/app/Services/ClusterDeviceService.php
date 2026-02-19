<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Meter\Meter;
use Illuminate\Support\Collection;

class ClusterDeviceService {
    public function __construct(private Device $device) {}

    public function getCountByClusterId(int $clusterId): int {
        return $this->device->newQuery()
            ->whereHas('person', function ($q) use ($clusterId) {
                $q->whereHas('addresses', function ($q) use ($clusterId) {
                    $q->where('is_primary', 1)
                        ->whereHas('city', function ($q) use ($clusterId) {
                            $q->where('cluster_id', $clusterId);
                        });
                });
            })
            ->count();
    }

    /**
     * @return Collection<int, Device>
     */
    public function getByClusterId(int $clusterId): Collection {
        return $this->device->newQuery()
            ->with(['device', 'person.addresses.city'])
            ->whereHas('person', function ($q) use ($clusterId) {
                $q->whereHas('addresses', function ($q) use ($clusterId) {
                    $q->where('is_primary', 1)
                        ->whereHas('city', function ($q) use ($clusterId) {
                            $q->where('cluster_id', $clusterId);
                        });
                });
            })
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
            ->whereHas('person', function ($q) use ($clusterId) {
                $q->whereHas('addresses', function ($q) use ($clusterId) {
                    $q->where('is_primary', 1)
                        ->whereHas('city', function ($q) use ($clusterId) {
                            $q->where('cluster_id', $clusterId);
                        });
                });
            })
            ->get();
    }
}
