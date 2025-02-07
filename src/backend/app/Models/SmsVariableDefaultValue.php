<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmsVariableDefaultValue extends BaseModel {
    use HasFactory;
    protected $table = 'sms_variable_default_values';
}
