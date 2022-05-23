<?php

namespace App\Services;

use App\Models\AssetRate;
use App\Models\PaymentHistory;

class ApplianceRatePaymentHistoryService implements IAssignationService
{
    private AssetRate $assetRate;
    private PaymentHistory $paymentHistory;

    public function setAssigned($paymentHistory)
    {
        $this->paymentHistory = $paymentHistory;
    }

    public function setAssigner($assetRate)
    {
        $this->assetRate = $assetRate;
    }

    public function assign()
    {
        $this->paymentHistory->paidFor()->associate($this->assetRate);

        return $this->paymentHistory;
    }
}