<?php

namespace App\Models\Transaction;

interface ManufacturerTransactionInterface {
    public function agentTransaction();

    public function thirdPartyTransaction();

    public function swiftaTransaction();

    public function mesombTransaction();

    public function waveMoneyTransaction();
}
