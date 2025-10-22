<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $variable
 * @property string      $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SteamaSmsVariableDefaultValue extends BaseModel {
    protected $table = 'steama_sms_variable_default_values';
}
