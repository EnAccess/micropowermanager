<?php

namespace App\Plugins\ChintMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ChintTransaction extends BaseModel {
    protected $table = 'chint_transactions';
}
