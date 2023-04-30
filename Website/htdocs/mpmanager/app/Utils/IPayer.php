<?php

namespace App\Utils;

use App\Misc\TransactionDataContainer;

interface IPayer
{
    public function initialize(TransactionDataContainer $transactionData);

    public function pay();

    public function consumeAmount();
}
