<?php

namespace Inensus\KelinMeter\Models;

use App\Models\Transaction\BaseManufacturerTransaction;

class KelinTransaction extends BaseManufacturerTransaction {
    protected $table = 'kelin_transactions';
}
