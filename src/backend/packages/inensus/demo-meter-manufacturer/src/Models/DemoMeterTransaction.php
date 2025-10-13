<?php

namespace Inensus\DemoMeterManufacturer\Models;

use App\Models\Transaction\BaseManufacturerTransaction;
use Carbon\Carbon;

/**
 * @property int    $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class DemoMeterTransaction extends BaseManufacturerTransaction {
    protected $table = 'demo_meter_transactions';
}
