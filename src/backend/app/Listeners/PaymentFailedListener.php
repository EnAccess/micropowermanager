<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;

class PaymentFailedListener {
    public function onPaymentFailed(): void {
        Log::debug('payment failed event');
    }

    public function handle(): void {
        $this->onPaymentFailed();
    }
}
