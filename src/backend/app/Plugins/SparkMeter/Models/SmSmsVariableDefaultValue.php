<?php

namespace App\Plugins\SparkMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $variable
 * @property string      $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SmSmsVariableDefaultValue extends BaseModel {
    protected $table = 'sm_sms_variable_default_values';
}
