<?php

namespace Inensus\DemoMeterManufacturer\Models;

use App\Models\Transaction\BaseManufacturerTransaction;

class DemoMeterTransaction extends BaseManufacturerTransaction {
    protected $table = 'demo_meter_transactions';
}
