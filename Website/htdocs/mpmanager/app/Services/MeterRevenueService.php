<?php

namespace App\Services;

use App\Models\Meter\MeterToken;
use App\Models\Revenue;
use App\Models\Transaction\Transaction;
use Illuminate\Support\Facades\DB;

class MeterRevenueService
{
    public function __construct(
        private MeterToken $meterToken,
        private Transaction $transaction
    ) {
    }

    public function getBySerialNumber(string $serialNumber)
    {
        $tokens = $this->meterToken->newQuery()->whereHas(
            'meter',
            function ($q) use ($serialNumber) {
                $q->where('serial_number', $serialNumber);
            }
        )->pluck('transaction_id');

        return $this->transaction->newQuery()->whereIn('id', $tokens)->sum('amount');
    }

    public function getTransactionsForWeeklyPeriod($meters, $period)
    {
        return $this->transaction->newQuery()
            ->selectRaw('DATE_FORMAT(created_at,\'%Y-%m\') as period ,DATE_FORMAT(created_at,\'%Y-%u\') ' .
                ' as week, SUM(amount) as revenue')
            ->whereHasMorph(
                'originalTransaction',
                '*',
                static function ($q) {
                    $q->where('status', 1);
                }
            )
            ->whereIn('message', $meters->pluck('serial_number'))
            ->whereBetween('created_at', $period)
            ->groupBy(DB::raw('DATE_FORMAT(created_at,\'%Y-%m\'),WEEKOFYEAR(created_at)'))->get();
    }
}
