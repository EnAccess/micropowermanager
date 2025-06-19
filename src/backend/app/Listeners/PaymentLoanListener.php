<?php

namespace App\Listeners;

class PaymentLoanListener {
    public function onLoanPayment(string $customer_id, int $amount): void {}

    public function handle(
        string $customer_id,
        int $amount,
    ): void {
        $this->onLoanPayment($customer_id, $amount);
    }
}
