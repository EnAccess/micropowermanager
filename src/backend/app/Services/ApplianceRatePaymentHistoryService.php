<?php

namespace App\Services;

use App\Models\AssetRate;
use App\Models\PaymentHistory;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<PaymentHistory, AssetRate>
 */
class ApplianceRatePaymentHistoryService implements IAssignationService {
    private PaymentHistory $paymentHistory;
    private AssetRate $assetRate;

    public function setAssigned($paymentHistory): void {
        $this->paymentHistory = $paymentHistory;
    }

    public function setAssignee($assetRate): void {
        $this->assetRate = $assetRate;
    }

    public function assign(): PaymentHistory {
        $this->paymentHistory->paidFor()->associate($this->assetRate);

        return $this->paymentHistory;
    }
}
