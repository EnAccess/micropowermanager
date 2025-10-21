<?php

namespace Inensus\GomeLongMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class GomeLongTransaction extends BaseModel {
    protected $table = 'gome_long_transactions';
}
