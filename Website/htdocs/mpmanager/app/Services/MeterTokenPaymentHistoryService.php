<?php

namespace App\Services;

use App\Models\Meter\MeterToken;
use App\Models\PaymentHistory;

class MeterTokenPaymentHistoryService implements IAssignationService
{
    private MeterToken $meterToken;
    private PaymentHistory $paymentHistory;

    public function setAssigned($paymentHistory)
    {
        $this->paymentHistory = $paymentHistory;
    }

    public function setAssignee($meterToken)
    {
        $this->meterToken = $meterToken;
    }

    public function assign()
    {
        $this->paymentHistory->paidFor()->associate($this->meterToken);

        return $this->paymentHistory;
    }
}
