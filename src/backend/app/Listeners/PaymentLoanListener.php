<?php

namespace App\Listeners;

use App\Models\AccessRate\AccessRate;
use App\Models\Asset;
use App\Models\AssetRate;
use App\Models\Meter\Meter;
use App\Models\Token;
use App\Services\AccessRatePaymentHistoryService;
use App\Services\ApplianceRatePaymentHistoryService;
use App\Services\PaymentHistoryService;
use App\Services\PersonPaymentHistoryService;
use App\Services\TransactionPaymentHistoryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use MPM\Transaction\Provider\ITransactionProvider;

class PaymentLoanListener {
    public function onLoanPayment(string $customer_id, int $amount): void {}

    public function handle(
        string $customer_id,
        int $amount,
    ): void {
        $this->onLoanPayment($customer_id, $amount);
    }
}
