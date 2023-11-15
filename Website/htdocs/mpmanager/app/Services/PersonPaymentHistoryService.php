<?php

namespace App\Services;

use App\Models\PaymentHistory;
use App\Models\Person\Person;

class PersonPaymentHistoryService implements IAssignationService
{
    private PaymentHistory $paymentHistory;
    private Person $person;


    public function setAssigned($paymentHistory)
    {
        $this->paymentHistory = $paymentHistory;
    }

    public function setAssignee($person)
    {
        $this->person = $person;
    }

    public function assign()
    {
        $this->paymentHistory->payer()->associate($this->person);

        return $this->paymentHistory;
    }
}
