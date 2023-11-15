<?php

namespace App\Services;

use App\Models\AccessRate\AccessRate;
use App\Models\PaymentHistory;

class AccessRatePaymentHistoryService implements IAssignationService
{
    private AccessRate $accessRate;
    private PaymentHistory $paymentHistory;

    public function setAssigned($paymentHistory)
    {
        $this->paymentHistory = $paymentHistory;
    }

    public function setAssignee($accessRate)
    {
        $this->accessRate = $accessRate;
    }

    public function assign()
    {
        $this->paymentHistory->paidFor()->associate($this->accessRate);

        return $this->paymentHistory;
    }
}
