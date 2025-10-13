<?php

namespace Inensus\DemoShsManufacturer\Models;

use App\Models\Transaction\BaseManufacturerTransaction;
use Carbon\Carbon;

/**
 * @property int    $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class DemoShsTransaction extends BaseManufacturerTransaction {
    protected $table = 'demo_shs_transactions';
}
