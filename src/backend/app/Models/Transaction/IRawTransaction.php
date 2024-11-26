<?php

namespace App\Models\Transaction;

interface IRawTransaction {
    // returns the filtered transaction
    // which is been used by the system
    // to process the payment
    public function transaction();

    public function manufacturerTransaction();
}
