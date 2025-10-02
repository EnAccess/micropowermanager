<?php

namespace Inensus\BulkRegistration\Services;

use App\Models\Cluster;
use App\Models\User;
use Inensus\BulkRegistration\Exceptions\ClusterNotFoundException;

class ClusterService extends CreatorService {
    public function __construct(Cluster $cluster) {
        parent::__construct($cluster);
    }

    public function resolveCsvDataFromComingRow($csvData) {
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
        $user = User::query()->first();
        $clusterData = [
            'name' => $csvData[$clusterConfig['name']],
            'manager_id' => $user->id,
        ];

        return $this->createRelatedDataIfDoesNotExists($clusterData);
    }
}
