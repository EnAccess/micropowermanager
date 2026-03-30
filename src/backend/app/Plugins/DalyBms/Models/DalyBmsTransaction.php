<?php

namespace App\Plugins\DalyBms\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DalyBmsTransaction extends BaseModel {
    protected $table = 'daly_bms_transactions';
}
