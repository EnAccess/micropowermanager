<?php

namespace App\Services;

use App\Models\Cluster;
use App\Models\Transaction\Transaction;

class ClusterTransactionService
{
    public function __construct(private Cluster $cluster, private Transaction $transaction)
    {
    }

    public function getById($clusterId, array $range)
    {
        return $this->transaction->newQuery()->whereHas(
            'device',
            function ($q) use ($clusterId) {
                $q->whereHas('address', fn ($q) => $q->whereHas('city', fn ($q) => $q->where('cluster_id', $clusterId)));
            }
        )->whereHasMorph(
            'originalTransaction',
            '*',
            static function ($q) {
                $q->where('status', 1);
            }
        )
            ->whereDate('created_at', '>=', $range[0])
            ->whereDate('created_at', '<=', $range[1])
            ->sum('amount');
    }
}
