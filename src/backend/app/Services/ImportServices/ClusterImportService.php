<?php

namespace App\Services\ImportServices;

use App\Models\City;
use App\Models\Cluster;
use App\Models\Country;
use App\Models\MiniGrid;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClusterImportService extends AbstractImportService {
    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function import(array $data): array {
        $importData = $data;
        if (isset($data['data']) && is_array($data['data'])) {
            $importData = $data['data'];
        }

        $errors = $this->validate($importData);
        if ($errors !== []) {
            return [
                'success' => false,
                'errors' => $errors,
            ];
        }

        $imported = [];
        $failed = [];

        DB::connection('tenant')->beginTransaction();

        try {
            foreach ($importData as $clusterData) {
                try {
                    $result = $this->importCluster($clusterData);
                    if ($result['success']) {
                        $imported[] = $result['cluster'];
                    } else {
                        $failed[] = [
                            'name' => $clusterData['cluster_name'] ?? 'unknown',
                            'errors' => $result['errors'],
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('Error importing cluster', [
                        'name' => $clusterData['cluster_name'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                    $failed[] = [
                        'name' => $clusterData['cluster_name'] ?? 'unknown',
                        'errors' => ['import' => $e->getMessage()],
                    ];
                }
            }

            DB::connection('tenant')->commit();

            $allFailed = count($imported) === 0 && count($failed) > 0;
            $partitioned = $this->partitionResults($imported);

            return [
                'success' => !$allFailed,
                'message' => $allFailed ? 'All cluster imports failed' : 'Clusters imported successfully',
                'imported_count' => count($imported),
                'added_count' => $partitioned['added_count'],
                'modified_count' => $partitioned['modified_count'],
                'failed_count' => count($failed),
                'added' => $partitioned['added'],
                'modified' => $partitioned['modified'],
                'failed' => $failed,
            ];
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            Log::error('Error during cluster import transaction', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'errors' => ['transaction' => 'Failed to import clusters: '.$e->getMessage()],
            ];
        }
    }

    /**
     * @param array<string, mixed> $clusterData
     *
     * @return array<string, mixed>
     */
    private function importCluster(array $clusterData): array {
        $clusterName = $clusterData['cluster_name'];

        // Resolve manager by name, fall back to the authenticated user
        $managerId = auth('api')->user()->id;
        if (!empty($clusterData['manager'])) {
            $manager = User::query()->where('name', $clusterData['manager'])->first();
            if ($manager !== null) {
                $managerId = $manager->id;
            }
        }

        $cluster = Cluster::query()->where('name', $clusterName)->first();
        $isNew = $cluster === null;

        if ($isNew) {
            $geoJson = $clusterData['geo_json'] ?? '{}';

            $cluster = Cluster::query()->create([
                'name' => $clusterName,
                'manager_id' => $managerId,
                'geo_json' => $geoJson,
            ]);
        } else {
            $cluster->update(['manager_id' => $managerId]);
        }

        // Parse and create mini-grids (comma-separated string from export)
        if (!empty($clusterData['mini_grids'])) {
            $miniGridNames = array_map(trim(...), explode(',', $clusterData['mini_grids']));
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
        if (!empty($clusterData['villages'])) {
            $villageNames = array_map(trim(...), explode(',', $clusterData['villages']));
            $miniGrid = MiniGrid::query()->where('cluster_id', $cluster->id)->first();
            $country = Country::query()->first();

            if ($miniGrid === null || $country === null) {
                Log::warning('Skipping village import for cluster: missing mini-grid or country', [
                    'cluster' => $clusterName,
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

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, string>
     */
    public function validate(array $data): array {
        $errors = [];

        foreach ($data as $index => $clusterData) {
            if (!is_array($clusterData)) {
                $errors["cluster_{$index}"] = 'Cluster data must be an array';
                continue;
            }

            if (empty($clusterData['cluster_name'])) {
                $errors["cluster_{$index}.cluster_name"] = 'Cluster name is required';
            }
        }

        return $errors;
    }
}
