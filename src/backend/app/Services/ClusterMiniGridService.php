<?php

namespace App\Services;

use App\Models\Cluster;

class ClusterMiniGridService {
    public function __construct(private Cluster $cluster) {}

    public function getClustersWithMiniGrids(): mixed {
        return $this->cluster->newQuery()->with('miniGrids')->get();
    }
}
