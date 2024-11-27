<?php

namespace App\Services;

use App\Models\Cluster;
use App\Models\MiniGrid;

class ClusterMiniGridService {
    public function __construct(private Cluster $cluster, private MiniGrid $miniGrid) {}

    public function getClustersWithMiniGrids() {
        return $this->cluster->newQuery()->with('miniGrids')->get();
    }
}
