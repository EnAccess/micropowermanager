<?php

namespace App\Services\ExportServices;

use App\Models\Cluster;
use Illuminate\Support\Collection;

class ClusterExportService extends AbstractExportService {
    /** @var Collection<int, Cluster> */
    private Collection $clusterData;

    public function writeClusterData(): void {
        $this->setActivatedSheet('Sheet1');

        foreach ($this->exportingData as $key => $value) {
            $this->worksheet->setCellValue('A'.($key + 2), $value[0]);
            $this->worksheet->setCellValue('B'.($key + 2), $value[1]);
            $this->worksheet->setCellValue('C'.($key + 2), $value[2]);
            $this->worksheet->setCellValue('D'.($key + 2), $value[3]);
            $this->worksheet->setCellValue('E'.($key + 2), $value[4]);
            $this->worksheet->setCellValue('F'.($key + 2), $value[5]);
        }

        foreach ($this->worksheet->getColumnIterator() as $column) {
            $this->worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
    }

    public function setExportingData(): void {
        $this->exportingData = $this->clusterData->map(function (Cluster $cluster): array {
            $miniGridCount = $cluster->miniGrids->count();
            $cityCount = $cluster->cities->count();
            $managerName = optional($cluster->manager)->name ?? '';

            return [
                $cluster->name,
                $managerName,
                $miniGridCount,
                $cityCount,
                $this->convertUtcDateToTimezone($cluster->created_at),
                $this->convertUtcDateToTimezone($cluster->updated_at),
            ];
        });
    }

    /**
     * @param Collection<int, Cluster> $clusterData
     */
    public function setClusterData(Collection $clusterData): void {
        $this->clusterData = $clusterData;
    }

    public function getTemplatePath(): string {
        return resource_path('templates/export_clusters_template.xlsx');
    }

    public function getPrefix(): string {
        return 'ClusterExport';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function exportDataToArray(): array {
        if ($this->clusterData->isEmpty()) {
            return [];
        }
        // TODO: support some form of pagination to limit the data to be exported using json
        // transform exporting data to JSON structure for cluster export
        $jsonDataTransform = $this->clusterData->map(function (Cluster $cluster): array {
            $miniGrids = $cluster->miniGrids->pluck('name')->filter()->implode(', ');
            $cities = $cluster->cities->pluck('name')->filter()->implode(', ');
            $managerName = optional($cluster->manager)->name ?? '';

            return [
                'cluster_name' => $cluster->name,
                'manager' => $managerName,
                'mini_grids' => $miniGrids,
                'villages' => $cities,
                'created_at' => $this->convertUtcDateToTimezone($cluster->created_at),
                'updated_at' => $this->convertUtcDateToTimezone($cluster->updated_at),
            ];
        });

        return $jsonDataTransform->toArray();
    }
}
