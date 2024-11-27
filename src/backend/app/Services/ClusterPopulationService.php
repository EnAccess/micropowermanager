<?php

namespace App\Services;

use App\Models\Cluster;
use App\Models\Person\Person;

class ClusterPopulationService {
    public function __construct(private Person $person, private Cluster $cluster) {}

    public function getById($clusterId, $onlyCustomers = true): int {
        if ($onlyCustomers) {
            $population = $this->person->newQuery()
                ->where('is_customer', 1)
                ->whereHas(
                    'addresses',
                    function ($q) use ($clusterId) {
                        $q->where('is_primary', 1)->whereHas(
                            'city',
                            function ($q) use ($clusterId) {
                                $q->where('cluster_id', $clusterId);
                            }
                        );
                    }
                )->count();
        } else {
            $population = $this->person->newQuery()->whereHas(
                'addresses',
                function ($q) use ($clusterId) {
                    $q->where('is_primary', 1)->whereHas(
                        'city',
                        function ($q) use ($clusterId) {
                            $q->where('cluster_id', $clusterId);
                        }
                    );
                }
            )->count();
        }

        return $population;
    }
}
