<?php

namespace App\DTO;

use App\Models\Cluster;

/**
 * Data container for computed cluster dashboard fields.
 * This separates cached/computed data from the actual model to prevent
 * inconsistencies between cached and fresh model instances.
 */
class ClusterDashboardData {
    public function __construct(
        public Cluster $cluster,
        public int $deviceCount = 0,
        public int $meterCount = 0,
        public float $revenue = 0.0,
        public int $population = 0,
        /** @var array<array-key, mixed> */
        public array $citiesRevenue = [],
        /** @var array<array-key, mixed> */
        public array $revenueAnalysis = [],
        /** @var array<array-key, mixed> */
        public array $period = [],
        /** @var array<array-key, mixed> */
        public array $periodWeekly = [],
        public float $totalRevenue = 0.0,
    ) {}

    /**
     * Convert to array for API responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array {
        return [
            'id' => $this->cluster->id,
            'name' => $this->cluster->name,
            'manager_id' => $this->cluster->manager_id,
            'created_at' => $this->cluster->created_at,
            'updated_at' => $this->cluster->updated_at,
            'deviceCount' => $this->deviceCount,
            'meterCount' => $this->meterCount,
            'revenue' => $this->revenue,
            'population' => $this->population,
            'citiesRevenue' => $this->citiesRevenue,
            'revenueAnalysis' => $this->revenueAnalysis,
            'clusterData' => $this->cluster,
            'period' => $this->period,
            'periodWeekly' => $this->periodWeekly,
            'totalRevenue' => $this->totalRevenue,
        ];
    }
}
