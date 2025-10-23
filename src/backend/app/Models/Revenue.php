<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Revenue extends BaseModel {
    protected $table = 'revenues';
}
