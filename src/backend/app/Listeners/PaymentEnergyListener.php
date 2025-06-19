<?php

namespace App\Listeners;

use App\Models\Meter\Meter;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use MPM\Transaction\Provider\ITransactionProvider;

class PaymentEnergyListener {
    public function onEnergyPayment(ITransactionProvider $transactionProvider): void {
        $transaction = $transactionProvider->getTransaction();
        // get meter preferences
        try {
            $meter = Meter::query()->where('meter_id', $transaction->message)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            Log::critical('Unkown meterId', ['meter_id' => $transaction->message, 'amount' => $transaction->amount]);
            event('transaction.failed', $transactionProvider);
        }
    }

    public function handle($transactionProvider): void {
        $this->onEnergyPayment($transactionProvider);
    }
}
