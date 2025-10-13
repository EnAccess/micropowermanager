<?php

namespace Inensus\CalinSmartMeter\Models;

use App\Models\Transaction\BaseManufacturerTransaction;

class CalinSmartTransaction extends BaseManufacturerTransaction {
    protected $table = 'calin_smart_transactions';
}
