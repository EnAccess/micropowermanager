<?php

namespace Inensus\StronMeter\Models;

use App\Models\Transaction\BaseManufacturerTransaction;

class StronTransaction extends BaseManufacturerTransaction {
    protected $table = 'stron_transactions';
}
