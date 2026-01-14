<?php

namespace Inensus\BulkRegistration\Services;

use App\Models\Cluster;
use Inensus\BulkRegistration\Exceptions\ClusterNotFoundException;

class ClusterService extends CreatorService {
    public function __construct(Cluster $cluster) {
        parent::__construct($cluster);
    }

    /**
     * @param array<string, mixed> $csvData
     */
    public function resolveCsvDataFromComingRow(array $csvData) {
        $clusterConfig = config('bulk-registration.csv_fields.cluster');

        if (!$csvData[$clusterConfig['name']]) {
            throw new ClusterNotFoundException('Cluster Name is required');
        }
        $registeredCluster = Cluster::query()->where('name', $csvData[$clusterConfig['name']])->first();

        if (!$registeredCluster) {
            $message = 'There is no cluster registered for '.$csvData[$clusterConfig['name']].
                '. Please add the Cluster first.';

            throw new ClusterNotFoundException($message);
        }

        $clusterConfig = config('bulk-registration.csv_fields.cluster');
        $clusterData = [
            'name' => $csvData[$clusterConfig['name']],
        ];

        return $this->createRelatedDataIfDoesNotExists($clusterData);
    }
}
