<?php

namespace App\Plugins\CalinMeterV2\Models;

use App\Models\Transaction\BaseManufacturerTransaction;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CalinV2Transaction extends BaseManufacturerTransaction {
    protected $table = 'calin_v2_transactions';
}
