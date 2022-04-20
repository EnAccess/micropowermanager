<?php

namespace App\Services;

use App\Models\Meter\MeterToken;
use App\Models\Revenue;
use App\Models\Transaction\Transaction;

class MeterRevenueService
{
    public function __construct(
        private SessionService $sessionService,
        private MeterToken $meterToken,
        private Transaction $transaction
    ) {
        $this->sessionService->setModel($meterToken);
        $this->sessionService->setModel($transaction);
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
}