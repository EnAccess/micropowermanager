<?php

namespace MPM\Transaction;

use App\Models\Meter\Meter;
use App\Models\Transaction\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MeterTransactionService
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
    ): LengthAwarePaginator {

        $query = $this->transaction->newQuery()->with('originalTransaction')->whereHas(
            'device',
            fn($q) => $q->whereHasMorph('device', Meter::class));

        if ($serialNumber) {
            $query->where('message', 'LIKE', '%' . $serialNumber . '%');
            $whereApplied = true;
        }

        if ($tariffId) {
            if ($whereApplied) {
                $query->orWhereHas(
                    'device',
                    fn($q) => $q->whereHasMorph('device', Meter::class, fn($q) => $q->where('tariff_id', $tariffId)));
            } else {
                $whereApplied = true;
                $query->whereHas(
                    'device',
                    fn($q) => $q->whereHasMorph('device', Meter::class, fn($q) => $q->where('tariff_id', $tariffId)));
            }
        }
        if ($transactionProvider) {
            $query->with($transactionProvider)->where(fn($q) => $q->whereHas($transactionProvider, fn($q) => $q->whereNotNull('id')));
        }

        if ($status) {
            if ($transactionProvider && $transactionProvider !== '-1') {
                $query->where(fn ($q) => $q->whereHas($transactionProvider, fn ($q) => $q->where('status', $status)));
            } else {
                $query->whereHasMorph('originalTransaction', '*', fn ($q) => $q->where('status', $status))->get();
            }
        }

        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('created_at', '<=', $toDate);
        }

        return $query->latest()->paginate($limit);
    }
}
