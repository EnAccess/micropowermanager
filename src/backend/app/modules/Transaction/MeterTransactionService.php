<?php

namespace MPM\Transaction;

use App\Models\Meter\Meter;
use App\Models\Transaction\Transaction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MeterTransactionService {
    public function __construct(private Transaction $transaction) {}

    /**
     * @return Collection<int, Transaction>|LengthAwarePaginator<Transaction>
     */
    public function search(
        ?string $serialNumber = null,
        ?int $tariffId = null,
        ?string $transactionProvider = null,
        ?int $status = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?int $limit = null,
        bool $whereApplied = false,
    ) {
        $query = $this->transaction->newQuery()->with('originalTransaction')->whereHas(
            'device',
            fn ($q) => $q->whereHasMorph('device', Meter::class)
        );

        if ($serialNumber) {
            $query->where('message', 'LIKE', '%'.$serialNumber.'%');
            $whereApplied = true;
        }

        if ($tariffId) {
            if ($whereApplied) {
                $query->orWhereHas(
                    'device',
                    fn ($q) => $q->whereHasMorph('device', Meter::class, fn ($q) => $q->where('tariff_id', $tariffId))
                );
            } else {
                $whereApplied = true;
                $query->whereHas(
                    'device',
                    fn ($q) => $q->whereHasMorph('device', Meter::class, fn ($q) => $q->where('tariff_id', $tariffId))
                );
            }
        }
        if ($transactionProvider) {
            $query->where(fn ($q) => $q->whereHasMorph('originalTransaction', $transactionProvider, fn ($q) => $q->whereNotNull('id')));
        }

        if ($status) {
            $query->whereHasMorph('originalTransaction', ($transactionProvider !== '-1') ? $transactionProvider : '*', fn ($q) => $q->where('status', $status));
        }

        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('created_at', '<=', $toDate);
        }

        if ($limit) {
            return $query->latest()->paginate($limit);
        }

        return $query->get();
    }
}
