<?php

namespace App\Services;

use App\Models\Meter\Meter;

class ClusterMeterService {
    public function __construct(private Meter $meter) {}

    public function getCountById(int $clusterId): int {
        return $this->meter->newQuery()
            ->whereHas('device.person.addresses.village', function ($q) use ($clusterId) {
                $q->whereHas('miniGrid', function ($miniGridQuery) use ($clusterId) {
                    $miniGridQuery->where('cluster_id', $clusterId);
                });
            })
            ->count();
    }
}
