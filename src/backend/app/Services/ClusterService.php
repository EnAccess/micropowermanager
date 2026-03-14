<?php

namespace App\Services;

use App\DTO\ClusterDashboardData;
use App\Models\Cluster;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * @implements IBaseService<Cluster>
 */
class ClusterService implements IBaseService {
    public function __construct(
        private Cluster $cluster,
    ) {}

    /**
     * Creates a cluster dashboard data container with computed fields.
     * This method does not mutate the cluster model.
     */
    public function getClusterWithComputedData(
        Cluster $cluster,
        int $meterCount,
        float $totalTransactionsAmount,
        int $populationCount,
    ): ClusterDashboardData {
        return new ClusterDashboardData(
            cluster: $cluster,
            meterCount: $meterCount,
            revenue: $totalTransactionsAmount,
            population: $populationCount,
        );
    }

    public function getClusterCities(int $clusterId): ?Cluster {
        return Cluster::query()->with(['villages', 'location'])->find($clusterId);
    }

    public function getClusterMiniGrids(int $clusterId): ?Cluster {
        return Cluster::query()->with(['miniGrids', 'location'])->find($clusterId);
    }

    public function getGeoLocationById(int $clusterId): mixed {
        $cluster = $this->cluster->newQuery()->with('location')->findOrFail($clusterId);

        return $cluster->geo_json;
    }

    /**
     * @return array<int, string>
     */
    public function getDateRangeFromRequest(?string $startDate, ?string $endDate): array {
        $dateRange = [];

        if ($startDate !== null && $endDate !== null) {
            $dateRange[0] = $startDate;
            $dateRange[1] = $endDate;
        } else {
            $dateRange[0] = date('Y-m-d', strtotime('today - 31 days'));
            $dateRange[1] = date('Y-m-d', strtotime('today - 1 days'));
        }

        return $dateRange;
    }

    public function getById(int $clusterId): Cluster {
        return $this->cluster->newQuery()->with(['miniGrids.location', 'villages', 'location'])->find($clusterId);
    }

    /**
     * @param array<string, mixed> $clusterData
     */
    public function create(array $clusterData): Cluster {
        return DB::connection('tenant')->transaction(function () use ($clusterData): Cluster {
            $geoJson = $clusterData['geo_json'] ?? null;

            // Keep legacy write only while clusters.geo_json exists.
            if (! Schema::connection('tenant')->hasColumn('clusters', 'geo_json')) {
                unset($clusterData['geo_json']);
            }

            $cluster = $this->cluster->newQuery()->create($clusterData);

            if ($geoJson !== null) {
                $cluster->location()->create([
                    'points' => '',
                    'geo_json' => $geoJson,
                ]);
            }

            return $cluster->fresh('location');
        });
    }

    /**
     * @return Collection<int, Cluster>|LengthAwarePaginator<int, Cluster>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        if ($limit !== null) {
            return $this->cluster->newQuery()->with(['miniGrids', 'location'])->limit($limit)->get();
        }

        return $this->cluster->newQuery()->with(['miniGrids', 'location'])->get();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update($model, array $data): Cluster {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    /**
     * @return Collection<int, Cluster>
     */
    public function getAllForExport(): Collection {
        return $this->cluster->newQuery()->with([
            'miniGrids',
            'villages',
            'manager',
            'location',
        ])->get();
    }
}
