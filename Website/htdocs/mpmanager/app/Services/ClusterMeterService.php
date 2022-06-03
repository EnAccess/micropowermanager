<?php

namespace App\Services;

use App\Models\Cluster;
use App\Models\Meter\Meter;

class ClusterMeterService
{
    public function __construct(private Cluster $cluster,private Meter $meter)
    {

    }

    public function getCountById($clusterId): int
    {
        return $this->meter->newQuery()->whereHas(
            'meterParameter',
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
