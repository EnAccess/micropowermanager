<?php

namespace App\Services;

use App\Models\ApplianceRate;
use App\Models\PaymentHistory;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<PaymentHistory, ApplianceRate>
 */
class ApplianceRatePaymentHistoryService implements IAssignationService {
    private PaymentHistory $paymentHistory;
    private ApplianceRate $applianceRate;

    public function setAssigned($paymentHistory): void {
        $this->paymentHistory = $paymentHistory;
    }

    public function setAssignee($applianceRate): void {
        $this->applianceRate = $applianceRate;
    }

    public function assign(): PaymentHistory {
        $this->paymentHistory->paidFor()->associate($this->applianceRate);

        return $this->paymentHistory;
    }
}
