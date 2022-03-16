<?php

namespace App\Models\Transaction;

interface ISubTransaction
{
    public function vodacomTransaction();
    public function airtelTransaction();
    public function agentTransaction();
    public function thirdPartyTransaction();
}
