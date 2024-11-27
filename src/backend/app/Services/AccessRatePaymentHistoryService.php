<?php

namespace App\Services;

use App\Models\AccessRate\AccessRate;
use App\Models\PaymentHistory;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<PaymentHistory, AccessRate>
 */
class AccessRatePaymentHistoryService implements IAssignationService {
    private PaymentHistory $paymentHistory;
    private AccessRate $accessRate;

    public function setAssigned($paymentHistory): void {
        $this->paymentHistory = $paymentHistory;
    }

    public function setAssignee($accessRate): void {
        $this->accessRate = $accessRate;
    }

    public function assign(): PaymentHistory {
        $this->paymentHistory->paidFor()->associate($this->accessRate);

        return $this->paymentHistory;
    }
}
