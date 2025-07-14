<?php

namespace App\Services;

use App\Models\Transaction\Transaction;

class ClusterTransactionService {
    public function __construct(private Transaction $transaction) {}

    /**
     * Get total transaction amount by cluster ID within a date range.
     *
     * @param int                $clusterId
     * @param array<int, string> $range
     *
     * @return float
     */
    public function getById(int $clusterId, array $range): float {
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
