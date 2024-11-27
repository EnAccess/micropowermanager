<?php

namespace App\Services;

use App\Models\PaymentHistory;
use App\Models\Person\Person;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<PaymentHistory, Person>
 */
class PersonPaymentHistoryService implements IAssignationService {
    private PaymentHistory $paymentHistory;
    private Person $person;

    public function setAssigned($paymentHistory): void {
        $this->paymentHistory = $paymentHistory;
    }

    public function setAssignee($person): void {
        $this->person = $person;
    }

    public function assign(): PaymentHistory {
        $this->paymentHistory->payer()->associate($this->person);

        return $this->paymentHistory;
    }
}
