<?php

namespace App\Services;

use App\Models\Meter\MeterToken;
use App\Models\Transaction\Transaction;
use Illuminate\Support\Carbon;

class MiniGridRevenueService {
    public function __construct(
        private Transaction $transaction,
        private MeterToken $meterToken,
    ) {}

    public function getById($miniGridId, $startDate, $endDate, $miniGridDeviceService) {
        $startDate = $startDate ?? date('Y-01-01');
        $endDate = $endDate ?? date('Y-m-t');
        $miniGridMeters = $miniGridDeviceService->getMetersByMiniGridId($miniGridId);

        return $this->transaction->newQuery()
            ->selectRaw('COUNT(id) as amount, SUM(amount) as revenue')
            ->whereHasMorph(
                'originalTransaction',
                '*',
                static function ($q) {
                    return $q->where('status', 1);
                }
            )
            ->whereIn('message', $miniGridMeters->pluck('serial_number'))
            ->whereBetween('created_at', [$startDate, Carbon::parse($endDate)->endOfDay()])->get();
    }

    public function getSoldEnergyById($miniGridId, $startDate, $endDate, $miniGridDeviceService) {
        $startDate = $startDate ?? date('Y-01-01');
        $endDate = $endDate ?? date('Y-m-t');
        $miniGridMeters = $miniGridDeviceService->getMetersByMiniGridId($miniGridId);
        $soldEnergy = $this->meterToken->newQuery()
            ->selectRaw('COUNT(id) as amount, SUM(energy) as energy')
            ->whereIn('meter_id', $miniGridMeters->pluck('id'))
            ->whereBetween('created_at', [$startDate, Carbon::parse($endDate)->endOfDay()])->get();
        $energy = 0;

        if ($soldEnergy) {
            $energy = round($soldEnergy[0]->energy, 3);
        }

        return ['data' => $energy];
    }
}
