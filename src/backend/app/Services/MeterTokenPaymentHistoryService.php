<?php

namespace App\Services;

use App\Models\Meter\MeterToken;
use App\Models\PaymentHistory;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<PaymentHistory, MeterToken>
 */
class MeterTokenPaymentHistoryService implements IAssignationService
{
    private PaymentHistory $paymentHistory;
    private MeterToken $meterToken;

    public function setAssigned($paymentHistory): void
    {
        $this->paymentHistory = $paymentHistory;
    }

    public function setAssignee($meterToken): void
    {
        $this->meterToken = $meterToken;
    }

    public function assign(): PaymentHistory
    {
        $this->paymentHistory->paidFor()->associate($this->meterToken);

        return $this->paymentHistory;
    }
}
