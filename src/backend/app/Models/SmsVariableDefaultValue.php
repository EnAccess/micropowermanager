<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Database\Factories\SmsVariableDefaultValueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $variable
 * @property string      $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SmsVariableDefaultValue extends BaseModel {
    /** @use HasFactory<SmsVariableDefaultValueFactory> */
    use HasFactory;
    protected $table = 'sms_variable_default_values';
}
