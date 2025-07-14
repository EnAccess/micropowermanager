<?php

namespace App\Services;

use App\Models\Meter\Meter;

class ClusterMeterService {
    public function __construct(private Meter $meter) {}

    public function getCountById(int $clusterId): int {
        return $this->meter->newQuery()->whereHas(
            'device',
            function ($q) use ($clusterId) {
                $q->whereHas(
                    'address',
                    function ($q) use ($clusterId) {
                        $q->whereHas(
                            'city',
                            function ($q) use ($clusterId) {
                                $q->where('cluster_id', $clusterId);
                            }
                        );
                    }
                );
            }
        )->count();
    }
}
