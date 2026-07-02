<?php

namespace App\Services\ImportServices;

use App\Models\City;
use App\Models\Cluster;
use App\Models\Country;
use App\Models\MiniGrid;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @extends AbstractImportService<ClusterImportItem>
 */
class ClusterImportService extends AbstractImportService {
    /**
     * @param list<ClusterImportItem> $data
     */
    public function import(array $data): ImportResult {
        $imported = [];
        $failed = [];

        DB::connection('tenant')->beginTransaction();

        try {
            foreach ($data as $item) {
                try {
                    $result = $this->importCluster($item);
                    if ($result['success']) {
                        $imported[] = $result['cluster'];
                    } else {
                        $failed[] = [
                            'name' => $item->clusterName,
                            'errors' => $result['errors'],
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('Error importing cluster', [
                        'name' => $item->clusterName,
                        'error' => $e->getMessage(),
                    ]);
                    $failed[] = [
                        'name' => $item->clusterName,
                        'errors' => ['import' => $e->getMessage()],
                    ];
                }
            }

            DB::connection('tenant')->commit();

            $allFailed = count($imported) === 0 && count($failed) > 0;
            $partitioned = $this->partitionResults($imported);

            return new ImportResult(
                message: $allFailed ? 'All cluster imports failed' : 'Clusters imported successfully',
                added: $partitioned['added'],
                modified: $partitioned['modified'],
                failed: $failed,
            );
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            $this->throwTransactionFailure('clusters', $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function importCluster(ClusterImportItem $item): array {
        // Resolve manager by name, fall back to the authenticated user
        $managerId = auth('api')->user()->id;
        if ($item->manager !== null && $item->manager !== '') {
            $manager = User::query()->where('name', $item->manager)->first();
            if ($manager !== null) {
                $managerId = $manager->id;
            }
        }

        $cluster = Cluster::query()->where('name', $item->clusterName)->first();
        $isNew = $cluster === null;

        if ($isNew) {
            $cluster = Cluster::query()->create([
                'name' => $item->clusterName,
                'manager_id' => $managerId,
                'geo_json' => $item->geoJson ?? '{}',
            ]);
        } else {
            $cluster->update(['manager_id' => $managerId]);
        }

        // Parse and create mini-grids (comma-separated string from export)
        if ($item->miniGrids !== null && $item->miniGrids !== '') {
            $miniGridNames = array_map(trim(...), explode(',', $item->miniGrids));
            foreach ($miniGridNames as $miniGridName) {
                if ($miniGridName === '') {
                    continue;
                }
                MiniGrid::query()->firstOrCreate(
                    ['name' => $miniGridName, 'cluster_id' => $cluster->id],
                );
            }
        }

        // Parse and create villages/cities (comma-separated string from export)
        if ($item->villages !== null && $item->villages !== '') {
            $villageNames = array_map(trim(...), explode(',', $item->villages));
            $miniGrid = MiniGrid::query()->where('cluster_id', $cluster->id)->first();
            $country = Country::query()->first();

            if ($miniGrid === null || $country === null) {
                Log::warning('Skipping village import for cluster: missing mini-grid or country', [
                    'cluster' => $item->clusterName,
                    'has_mini_grid' => $miniGrid !== null,
                    'has_country' => $country !== null,
                ]);
            } else {
                foreach ($villageNames as $villageName) {
                    if ($villageName === '') {
                        continue;
                    }
                    City::query()->firstOrCreate(
                        ['name' => $villageName, 'mini_grid_id' => $miniGrid->id],
                        ['country_id' => $country->id, 'cluster_id' => $cluster->id],
                    );
                }
            }
        }

        return [
            'success' => true,
            'action' => $isNew ? 'added' : 'modified',
            'cluster' => [
                'id' => $cluster->id,
                'name' => $cluster->name,
                'action' => $isNew ? 'added' : 'modified',
            ],
        ];
    }
}
