<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 12.07.18
 * Time: 17:14
 */

namespace App\Models\Transaction;

interface IRawTransaction
{
    //returns the filtered transaction
    // which is been used by the system
    // to process the payment
    public function transaction();

    public function manufacturerTransaction();
}
