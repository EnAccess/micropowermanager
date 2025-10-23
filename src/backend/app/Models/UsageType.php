<?php

namespace App\Models;

use App\Models\Base\BaseModelCentral;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $name
 * @property string      $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class UsageType extends BaseModelCentral {
    protected $table = 'usage_types';
}
