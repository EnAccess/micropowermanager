<?php

namespace MPM\Transaction;

use App\Models\SolarHomeSystem;
use App\Models\Transaction\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SolarHomeSystemTransactionService
{
    public function __construct(private Transaction $transaction)
    {
    }

    public function search(
        string $serialNumber = null,
        int $tariffId = null,
        string $transactionProvider = null,
        int $status = null,
        string $fromDate = null,
        string $toDate = null,
        int $limit = null,
        bool $whereApplied = false
    ) {

        $query = $this->transaction->newQuery()->with('originalTransaction')->whereHas(
            'device',
            fn($q) => $q->whereHasMorph('device', SolarHomeSystem::class)
        );

        if ($serialNumber) {
            $query->where('message', 'LIKE', '%' . request('serial_number') . '%');
        }

        if ($transactionProvider) {
            $query->with($transactionProvider)->where(fn($q) => $q->whereHas(
                $transactionProvider,
                fn($q) => $q->whereNotNull('id')
            ));
        }

        if ($status) {
            if ($transactionProvider && $transactionProvider !== '-1') {
                $query->where(fn($q) => $q->whereHas($transactionProvider, fn($q) => $q->where('status', $status)));
            } else {
                $query->whereHasMorph('originalTransaction', '*', fn($q) => $q->where('status', $status))->get();
            }
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
