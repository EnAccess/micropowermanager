<?php

namespace App\Services;

use App\Models\Token;
use App\Models\Transaction\Transaction;
use Illuminate\Support\Carbon;

class MiniGridRevenueService {
    public function __construct(
        private Transaction $transaction,
        private Token $token,
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
        $soldEnergy = $this->token->newQuery()
            ->selectRaw('COUNT(id) as amount, SUM(load) as load')
            ->whereHas('device', function ($query) use ($miniGridMeters) {
                $query->where('device_type', 'meter')
                    ->whereIn('device_id', $miniGridMeters->pluck('id'));
            })
            ->whereBetween('created_at', [$startDate, Carbon::parse($endDate)->endOfDay()])->get();
        $energy = 0;

        if ($soldEnergy) {
            $energy = round($soldEnergy[0]->load, 3);
        }

        return ['data' => $energy];
    }
}
