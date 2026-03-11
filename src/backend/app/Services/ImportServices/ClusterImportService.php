<?php

namespace App\Services\ImportServices;

use App\Models\City;
use App\Models\Cluster;
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

            return [
                'success' => true,
                'message' => 'Clusters imported successfully',
                'imported_count' => count($imported),
                'failed_count' => count($failed),
                'imported' => $imported,
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

        // Resolve manager by name if provided
        $managerId = null;
        if (!empty($clusterData['manager'])) {
            $manager = User::query()->where('name', $clusterData['manager'])->first();
            if ($manager !== null) {
                $managerId = $manager->id;
            }
        }

        $cluster = Cluster::query()->where('name', $clusterName)->first();

        if ($cluster === null) {
            $cluster = Cluster::query()->create([
                'name' => $clusterName,
                'manager_id' => $managerId,
            ]);
        } elseif ($managerId !== null) {
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
            foreach ($villageNames as $villageName) {
                if ($villageName === '') {
                    continue;
                }
                // Assign village to the first mini-grid of this cluster
                $miniGrid = MiniGrid::query()->where('cluster_id', $cluster->id)->first();
                if ($miniGrid !== null) {
                    City::query()->firstOrCreate(
                        ['name' => $villageName, 'mini_grid_id' => $miniGrid->id],
                    );
                }
            }
        }

        return [
            'success' => true,
            'cluster' => [
                'id' => $cluster->id,
                'name' => $cluster->name,
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
