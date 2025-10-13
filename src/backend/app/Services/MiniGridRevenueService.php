<?php

namespace App\Services;

use App\Models\Token;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use MPM\Device\MiniGridDeviceService;

class MiniGridRevenueService {
    public function __construct(
        private Transaction $transaction,
        private Token $token,
    ) {}

    /**
     * @return Collection<int, Transaction>
     */
    public function getById(
        int $miniGridId,
        ?string $startDate,
        ?string $endDate,
        MeterService|MiniGridDeviceService $miniGridDeviceService,
    ): Collection {
        $startDate ??= date('Y-01-01');
        $endDate ??= date('Y-m-t');
        $miniGridMeters = $miniGridDeviceService->getMetersByMiniGridId($miniGridId);

        return $this->transaction->newQuery()
            ->selectRaw('COUNT(id) as amount, SUM(amount) as revenue')
            ->whereHasMorph(
                'originalTransaction',
                '*',
                static fn ($q) => $q->where('status', 1)
            )
            ->whereIn('message', $miniGridMeters->pluck('serial_number'))
            ->whereBetween('created_at', [$startDate, Carbon::parse($endDate)->endOfDay()])->get();
    }

    /**
     * @return array{data: float}
     */
    public function getSoldEnergyById(
        int $miniGridId,
        ?string $startDate,
        ?string $endDate,
        MeterService|MiniGridDeviceService $miniGridDeviceService,
    ): array {
        $startDate ??= date('Y-01-01');
        $endDate ??= date('Y-m-t');
        $miniGridMeters = $miniGridDeviceService->getMetersByMiniGridId($miniGridId);
        $soldEnergy = $this->token->newQuery()
            ->selectRaw('COUNT(id) as amount, SUM(token_amount) as token_amount')
            ->whereHas('device', function ($query) use ($miniGridMeters) {
                $query->where('device_type', 'meter')
                    ->whereIn('device_id', $miniGridMeters->pluck('id'));
            })
            ->whereBetween('created_at', [$startDate, Carbon::parse($endDate)->endOfDay()])->get();
        $energy = 0;

        if ($soldEnergy->isNotEmpty() && isset($soldEnergy[0]->token_amount)) {
            $energy = round($soldEnergy[0]->token_amount, 3);
        }

        return ['data' => $energy];
    }
}
